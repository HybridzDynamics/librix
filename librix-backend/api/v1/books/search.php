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


// Query Parameters

$Search = trim($_GET["q"] ?? "");
$Tags = $_GET["tags"] ?? ""; // Comma-separated tag IDs
$Category = trim($_GET["category"] ?? "");
$Author = trim($_GET["author"] ?? "");
$MinRating = isset($_GET["min_rating"]) ? (float)$_GET["min_rating"] : null;
$MaxRating = isset($_GET["max_rating"]) ? (float)$_GET["max_rating"] : null;
$Difficulty = $_GET["difficulty"] ?? ""; // readability difficulty
$Page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$Limit = isset($_GET["limit"]) ? min(100, max(1, (int)$_GET["limit"])) : 20;
$Offset = ($Page - 1) * $Limit;
$SortBy = $_GET["sort"] ?? "relevance"; // relevance, title, rating, date
$SortOrder = $_GET["order"] ?? "desc"; // asc, desc


// Build Query

$WhereConditions = ["1=1"];
$Params = [];
$OrderBy = "b.created_at DESC";


// Search query (title, author, description)

if (!empty($Search)) {
    $WhereConditions[] = "(b.title LIKE ? OR a.name LIKE ? OR b.description LIKE ?)";
    $SearchParam = "%{$Search}%";
    $Params[] = $SearchParam;
    $Params[] = $SearchParam;
    $Params[] = $SearchParam;
}


// Tags filter

if (!empty($Tags)) {
    $TagIds = array_map('trim', explode(',', $Tags));
    $TagIds = array_filter($TagIds, 'is_numeric');
    
    if (!empty($TagIds)) {
        $TagPlaceholders = implode(',', array_fill(0, count($TagIds), '?'));
        $WhereConditions[] = "b.id IN (SELECT book_id FROM book_tags WHERE tag_id IN ({$TagPlaceholders}))";
        $Params = array_merge($Params, $TagIds);
    }
}


// Category filter

if (!empty($Category)) {
    $WhereConditions[] = "b.category LIKE ?";
    $Params[] = "%{$Category}%";
}


// Author filter

if (!empty($Author)) {
    $WhereConditions[] = "a.name LIKE ?";
    $Params[] = "%{$Author}%";
}


// Rating filters

if ($MinRating !== null) {
    $WhereConditions[] = "b.average_rating >= ?";
    $Params[] = $MinRating;
}

if ($MaxRating !== null) {
    $WhereConditions[] = "b.average_rating <= ?";
    $Params[] = $MaxRating;
}


// Difficulty filter (readability)

if (!empty($Difficulty) && in_array($Difficulty, ['very_easy', 'easy', 'fairly_easy', 'standard', 'fairly_difficult', 'difficult', 'very_difficult'])) {
    $WhereConditions[] = "r.difficulty_level = ?";
    $Params[] = $Difficulty;
}


// Sorting

switch ($SortBy) {
    case "title":
        $OrderBy = "b.title {$SortOrder}";
        break;
    case "rating":
        $OrderBy = "b.average_rating {$SortOrder}";
        break;
    case "date":
        $OrderBy = "b.created_at {$SortOrder}";
        break;
    case "relevance":
    default:
        $OrderBy = "b.average_rating DESC";
        break;
}


try {
    // Get total count
    $CountQuery = "
        SELECT COUNT(DISTINCT b.id)
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN readability_analysis r ON b.id = r.book_id
        WHERE " . implode(" AND ", $WhereConditions);
    
    $CountStmt = $pdo->prepare($CountQuery);
    $CountStmt->execute($Params);
    $Total = (int)$CountStmt->fetchColumn();

    // Get books with pagination
    $Query = "
        SELECT 
            b.id,
            b.title,
            b.author_id,
            a.name AS author_name,
            b.isbn,
            b.description,
            b.category,
            b.publisher,
            b.publication_year,
            b.total_copies,
            b.available_copies,
            b.cover_image,
            b.average_rating AS rating,
            b.rating_count,
            b.created_at,
            b.updated_at,
            r.difficulty_level,
            r.flesch_reading_ease
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN readability_analysis r ON b.id = r.book_id
        WHERE " . implode(" AND ", $WhereConditions) . "
        ORDER BY {$OrderBy}
        LIMIT ? OFFSET ?
    ";
    
    $Params[] = $Limit;
    $Params[] = $Offset;
    
    $Stmt = $pdo->prepare($Query);
    $Stmt->execute($Params);
    $Books = $Stmt->fetchAll();

    foreach ($Books as &$Book) {
        $Book["id"] = (int)$Book["id"];
        $Book["author_id"] = (int)$Book["author_id"];
        $Book["total_copies"] = (int)$Book["total_copies"];
        $Book["available_copies"] = (int)$Book["available_copies"];
        $Book["rating"] = (float)$Book["rating"];
        $Book["publication_year"] = $Book["publication_year"] ? (int)$Book["publication_year"] : null;
        $Book["tags"] = [];
        $Book["flesch_reading_ease"] = $Book["flesch_reading_ease"] ? (float)$Book["flesch_reading_ease"] : null;
    }

    $TotalPages = $Limit > 0 ? (int)ceil($Total / $Limit) : 1;

    paginatedResponse($Books, $Page, $Limit, $Total);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>