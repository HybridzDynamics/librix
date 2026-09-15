<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Request Data

$TagId = $_GET["id"] ?? null;
$BookId = $_GET["book_id"] ?? null;


// Single Tag

if ($TagId !== null) {
    $IdError = validatePositiveInteger($TagId, "Tag ID");
    
    if ($IdError !== null) {
        errorResponse($IdError, 400);
    }
    
    try {
        $Stmt = $pdo->prepare(
            "SELECT
                tags.id,
                tags.name,
                tags.created_at,
                COUNT(DISTINCT book_tags.book_id) AS total_books
             FROM tags
             LEFT JOIN book_tags ON tags.id = book_tags.tag_id
             WHERE tags.id = ?
             GROUP BY tags.id
             LIMIT 1"
        );
        
        $Stmt->execute([(int)$TagId]);
        
        $Tag = $Stmt->fetch();
        
    } catch (PDOException $e) {
        errorResponse("Database error", 500);
    }
    
    if (!$Tag) {
        notFoundResponse("Tag not found");
    }
    
    $Tag["total_books"] = (int)$Tag["total_books"];
    
    successResponse($Tag);
}


// Get tags for a specific book

if ($BookId !== null) {
    $IdError = validatePositiveInteger($BookId, "Book ID");
    
    if ($IdError !== null) {
        errorResponse($IdError, 400);
    }
    
    try {
        $Stmt = $pdo->prepare(
            "SELECT
                bt.tag_id,
                t.name,
                bt.count
             FROM book_tags bt
             JOIN tags t ON bt.tag_id = t.id
             WHERE bt.book_id = ?
             ORDER BY bt.count DESC
             LIMIT 20"
        );
        
        $Stmt->execute([(int)$BookId]);
        
        $Tags = $Stmt->fetchAll();
        
    } catch (PDOException $e) {
        errorResponse("Database error", 500);
    }
    
    successResponse($Tags);
}


// All Tags with pagination

$Page = validatePageNumber($_GET["page"] ?? 1);
$Limit = validatePageLimit($_GET["limit"] ?? 50, 20, 200);
$Offset = ($Page - 1) * $Limit;

try {
    $CountSql = "SELECT COUNT(*) as total FROM tags";
    $Stmt = $pdo->query($CountSql);
    $TotalRow = $Stmt->fetch();
    $Total = (int)($TotalRow["total"] ?? 0);
    
    $DataSql = "SELECT
                    tags.id,
                    tags.name,
                    tags.created_at,
                    COUNT(DISTINCT book_tags.book_id) AS total_books
                 FROM tags
                 LEFT JOIN book_tags ON tags.id = book_tags.tag_id
                 GROUP BY tags.id
                 ORDER BY total_books DESC, tags.name ASC
                 LIMIT ? OFFSET ?";
    
    $Stmt = $pdo->prepare($DataSql);
    $Stmt->bindValue(1, (int)$Limit, PDO::PARAM_INT);
    $Stmt->bindValue(2, (int)$Offset, PDO::PARAM_INT);
    $Stmt->execute();
    
    $Tags = $Stmt->fetchAll();
    
    foreach ($Tags as &$Item) {
        $Item["total_books"] = (int)$Item["total_books"];
    }
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

paginatedResponse($Tags, $Page, $Limit, $Total);

?>