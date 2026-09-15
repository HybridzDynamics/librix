<?php

// Import Tags and Book Tags from CSV files

require_once __DIR__ . "/librix-backend/config/database.php";

$TagsFile = __DIR__ . "/tags.csv";
$BookTagsFile = __DIR__ . "/book_tags.csv";

echo "Starting tag import...\n";

// Import Tags
if (file_exists($TagsFile)) {
    echo "Importing tags from tags.csv...\n";
    
    $handle = fopen($TagsFile, "r");
    if ($handle) {
        $header = fgetcsv($handle);
        $tagIdMap = [];
        $importedCount = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 2) continue;
            
            $tagId = $data[0];
            $tagName = $data[1];
            
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO tags (id, name) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE name = VALUES(name)"
                );
                $stmt->execute([$tagId, $tagName]);
                $tagIdMap[$tagId] = $tagName;
                $importedCount++;
                
            } catch (PDOException $e) {
                echo "Error importing tag $tagId: " . $e->getMessage() . "\n";
            }
        }
        
        fclose($handle);
        echo "Imported $importedCount tags\n";
    }
} else {
    echo "tags.csv not found\n";
}

// Import Book Tags
if (file_exists($BookTagsFile)) {
    echo "Importing book tags from book_tags.csv...\n";
    
    $handle = fopen($BookTagsFile, "r");
    if ($handle) {
        $header = fgetcsv($handle);
        $importedCount = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 3) continue;
            
            $goodreadsBookId = $data[0];
            $tagId = $data[1];
            $count = $data[2];
            
            // Find the corresponding book_id in our database
            try {
                $stmt = $pdo->prepare(
                    "SELECT id FROM books WHERE isbn = ? OR isbn13 = ? LIMIT 1"
                );
                $stmt->execute([$goodreadsBookId, $goodreadsBookId]);
                $book = $stmt->fetch();
                
                if ($book) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO book_tags (book_id, tag_id, count)
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE count = VALUES(count)"
                    );
                    $stmt->execute([$book["id"], $tagId, $count]);
                    $importedCount++;
                }
                
            } catch (PDOException $e) {
                echo "Error importing book tag for book $goodreadsBookId: " . $e->getMessage() . "\n";
            }
        }
        
        fclose($handle);
        echo "Imported $importedCount book tags\n";
    }
} else {
    echo "book_tags.csv not found\n";
}

echo "Tag import completed!\n";

?>