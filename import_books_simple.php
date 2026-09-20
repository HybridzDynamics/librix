<?php

// Simple Book Import - Import from existing books_clean.csv

require_once __DIR__ . "/librix-backend/config/database.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting simple book import...\n\n";

$BooksCleanFile = __DIR__ . "/books_clean.csv";

if (!file_exists($BooksCleanFile)) {
    echo "❌ books_clean.csv not found\n";
    exit(1);
}

echo "Step 1: Importing books from books_clean.csv...\n";

$handle = fopen($BooksCleanFile, "r");
if (!$handle) {
    echo "❌ Could not open books_clean.csv\n";
    exit(1);
}

$header = fgetcsv($handle);
$importedCount = 0;
$skippedCount = 0;
$batchSize = 100;
$batch = [];

while (($data = fgetcsv($handle)) !== false) {
    if (count($data) < 4) continue;
    
    $title = trim($data[1] ?? "");
    $author = trim($data[2] ?? "");
    $rating = $data[3] ?? "0";
    $numRatings = $data[4] ?? "0";
    $genres = trim($data[5] ?? "");
    $languageCode = trim($data[7] ?? "English");
    $imageUrl = trim($data[8] ?? "");
    
    if (empty($title) || empty($author)) {
        $skippedCount++;
        continue;
    }
    
    $batch[] = [
        'title' => $title,
        'author' => $author,
        'rating' => is_numeric($rating) ? (float)$rating : 0.00,
        'numRatings' => is_numeric($numRatings) ? (int)$numRatings : 0,
        'genres' => $genres,
        'languageCode' => $languageCode,
        'imageUrl' => $imageUrl
    ];
    
    if (count($batch) >= $batchSize) {
        processBatch($pdo, $batch, $importedCount, $skippedCount);
        $batch = [];
    }
}

// Process remaining
if (!empty($batch)) {
    processBatch($pdo, $batch, $importedCount, $skippedCount);
}

fclose($handle);

echo "\n✅ Simple book import completed!\n";
echo "Summary:\n";
echo "- Imported $importedCount new books\n";
echo "- Skipped $skippedCount existing/invalid books\n";

function processBatch($pdo, $batch, &$importedCount, &$skippedCount) {
    foreach ($batch as $bookData) {
        try {
            // Check if book already exists
            $stmt = $pdo->prepare("SELECT id FROM books WHERE title = ? LIMIT 1");
            $stmt->execute([$bookData['title']]);
            $existingBook = $stmt->fetch();
            
            if ($existingBook) {
                $skippedCount++;
                continue;
            }
            
            // Create author if doesn't exist
            $stmt = $pdo->prepare("SELECT id FROM authors WHERE name = ? LIMIT 1");
            $stmt->execute([$bookData['author']]);
            $authorObj = $stmt->fetch();
            
            if (!$authorObj) {
                $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                $stmt->execute([$bookData['author']]);
                $authorId = (int)$pdo->lastInsertId();
            } else {
                $authorId = (int)$authorObj['id'];
            }
            
            // Handle categories from genres
            $category = "General";
            if (!empty($bookData['genres'])) {
                $genreArray = array_map('trim', explode(',', $bookData['genres']));
                $category = $genreArray[0] ?? "General";
                
                // Create category if doesn't exist
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
                $stmt->execute([$category]);
                $categoryObj = $stmt->fetch();
                
                if (!$categoryObj) {
                    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt->execute([$category]);
                }
            }
            
            // Insert book
            $stmt = $pdo->prepare(
                "INSERT INTO books 
                (author_id, title, category, language, cover_image, average_rating, rating_count, total_copies, available_copies)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)"
            );
            
            $stmt->execute([
                $authorId,
                $bookData['title'],
                $category,
                $bookData['languageCode'],
                $bookData['imageUrl'] ?: null,
                $bookData['rating'],
                $bookData['numRatings']
            ]);
            
            $importedCount++;
            
        } catch (PDOException $e) {
            echo "  ⚠ Error importing book '{$bookData['title']}': " . $e->getMessage() . "\n";
        }
    }
}

?>