<?php

// Comprehensive Book Import Script

require_once __DIR__ . "/librix-backend/config/database.php";

$BooksCleanFile = __DIR__ . "/books_clean.csv";
$TagsFile = __DIR__ . "/tags.csv";
$BookTagsFile = __DIR__ . "/book_tags.csv";

echo "Starting comprehensive book import...\n\n";

// Import Tags
if (file_exists($TagsFile)) {
    echo "Step 1: Importing tags from tags.csv...\n";
    
    $handle = fopen($TagsFile, "r");
    if ($handle) {
        $header = @fgetcsv($handle);
        $importedCount = 0;
        $skippedCount = 0;
        
        while (($data = @fgetcsv($handle)) !== false) {
            if (count($data) < 2) continue;
            
            $tagId = $data[0];
            $tagName = trim($data[1]);
            
            // Skip invalid tags
            if (empty($tagName) || $tagName === "-" || strpos($tagName, "--") === 0) {
                $skippedCount++;
                continue;
            }
            
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO tags (id, name) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE name = VALUES(name)"
                );
                $stmt->execute([$tagId, $tagName]);
                $importedCount++;
                
            } catch (PDOException $e) {
                echo "  ⚠ Error importing tag $tagId: " . $e->getMessage() . "\n";
            }
        }
        
        fclose($handle);
        echo "  ✓ Imported $importedCount valid tags\n";
        echo "  ℹ Skipped $skippedCount invalid tags\n\n";
    }
} else {
    echo "  ⚠ tags.csv not found\n\n";
}

// Import Books from books_clean.csv
if (file_exists($BooksCleanFile)) {
    echo "Step 2: Importing books from books_clean.csv...\n";
    
    $handle = fopen($BooksCleanFile, "r");
    if ($handle) {
        $header = @fgetcsv($handle);
        $importedCount = 0;
        $skippedCount = 0;
        $bookIdMap = []; // Map CSV book IDs to database IDs
        
        while (($data = @fgetcsv($handle)) !== false) {
            if (count($data) < 10) continue;
            
            $csvBookId = $data[0];
            $title = trim($data[1]);
            $author = trim($data[2]);
            $rating = $data[3];
            $numRatings = $data[4];
            $genres = trim($data[5]);
            $description = trim($data[6]);
            $languageCode = trim($data[7]);
            $imageUrl = trim($data[8]);
            $content = trim($data[9]);
            
            // Skip invalid entries
            if (empty($title) || empty($author)) {
                $skippedCount++;
                continue;
            }
            
            try {
                // Check if book already exists by title
                $stmt = $pdo->prepare("SELECT id FROM books WHERE title = ? LIMIT 1");
                $stmt->execute([$title]);
                $existingBook = $stmt->fetch();
                
                if ($existingBook) {
                    $bookIdMap[$csvBookId] = $existingBook['id'];
                    $skippedCount++;
                    continue;
                }
                
                // Create author if doesn't exist
                $stmt = $pdo->prepare("SELECT id FROM authors WHERE name = ? LIMIT 1");
                $stmt->execute([$author]);
                $authorObj = $stmt->fetch();
                
                if (!$authorObj) {
                    $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                    $stmt->execute([$author]);
                    $authorId = (int)$pdo->lastInsertId();
                } else {
                    $authorId = (int)$authorObj['id'];
                }
                
                // Handle categories from genres
                $category = "General";
                if (!empty($genres)) {
                    $genreArray = array_map('trim', explode(',', $genres));
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
                    (author_id, title, description, category, language, cover_image, average_rating, rating_count, content, total_copies, available_copies)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)"
                );
                
                $stmt->execute([
                    $authorId,
                    $title,
                    $description ?: null,
                    $category,
                    $languageCode ?: 'English',
                    $imageUrl ?: null,
                    is_numeric($rating) ? (float)$rating : 0.00,
                    is_numeric($numRatings) ? (int)$numRatings : 0,
                    $content ?: null
                ]);
                
                $dbBookId = (int)$pdo->lastInsertId();
                $bookIdMap[$csvBookId] = $dbBookId;
                $importedCount++;
                
            } catch (PDOException $e) {
                echo "  ⚠ Error importing book '$title': " . $e->getMessage() . "\n";
            }
        }
        
        fclose($handle);
        echo "  ✓ Imported $importedCount new books\n";
        echo "  ℹ Skipped $skippedCount existing books\n\n";
    }
} else {
    echo "  ⚠ books_clean.csv not found\n\n";
}

// Import Book Tags
if (file_exists($BookTagsFile) && file_exists($TagsFile)) {
    echo "Step 3: Importing book tags from book_tags.csv...\n";
    
    $handle = fopen($BookTagsFile, "r");
    if ($handle) {
        $header = @fgetcsv($handle);
        $importedCount = 0;
        $skippedCount = 0;
        
        while (($data = @fgetcsv($handle)) !== false) {
            if (count($data) < 3) continue;
            
            $goodreadsBookId = $data[0];
            $tagId = $data[1];
            $count = $data[2];
            
            // Skip invalid tag IDs
            if (!is_numeric($tagId) || $tagId < 0) {
                $skippedCount++;
                continue;
            }
            
            try {
                // Find the corresponding book in our database
                // First try to find by content (which contains the original data)
                $stmt = $pdo->prepare(
                    "SELECT id FROM books WHERE content LIKE ? LIMIT 1"
                );
                $stmt->execute(["%$goodreadsBookId%"]);
                $book = $stmt->fetch();
                
                if ($book) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO book_tags (book_id, tag_id, count)
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE count = VALUES(count)"
                    );
                    $stmt->execute([$book["id"], $tagId, $count]);
                    $importedCount++;
                } else {
                    $skippedCount++;
                }
                
            } catch (PDOException $e) {
                echo "  ⚠ Error importing book tag for book $goodreadsBookId: " . $e->getMessage() . "\n";
            }
        }
        
        fclose($handle);
        echo "  ✓ Imported $importedCount book tags\n";
        echo "  ℹ Skipped $skippedCount book tags (book not found)\n\n";
    }
} else {
    echo "  ⚠ book_tags.csv or tags.csv not found\n\n";
}

echo "🎉 Comprehensive book import completed!\n";
echo "\nSummary:\n";
echo "- Database schema updated with tags and book tags tables\n";
echo "- Books imported with authors, categories, and ratings\n";
echo "- Book-tag relationships established\n";
echo "- Ready for full library operations\n";

?>