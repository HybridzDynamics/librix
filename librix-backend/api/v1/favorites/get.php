<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Authentication

$User = RequireAuth();
$UserId = (int)$User["id"];


// Pagination

$Page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$Limit = isset($_GET["limit"]) ? min(100, max(1, (int)$_GET["limit"])) : 20;
$Offset = ($Page - 1) * $Limit;


// Query Favorites

try {
    $CountStmt = $pdo->prepare("SELECT COUNT(id) FROM favorites WHERE user_id = ?");
    $CountStmt->execute([$UserId]);
    $Total = (int)$CountStmt->fetchColumn();

    $Stmt = $pdo->prepare(
        "SELECT 
            favorites.id AS favorite_id,
            favorites.book_id,
            favorites.created_at AS favorited_at,
            books.title,
            books.isbn,
            books.description,
            books.category,
            books.language,
            books.publisher,
            books.publication_year,
            books.available_copies,
            books.total_copies,
            books.cover_image,
            authors.name AS author_name,
            COALESCE(ROUND(AVG(reviews.rating), 1), 0.0) AS average_rating,
            COUNT(reviews.id) AS total_reviews
         FROM favorites
         INNER JOIN books ON favorites.book_id = books.id
         LEFT JOIN authors ON books.author_id = authors.id
         LEFT JOIN reviews ON books.id = reviews.book_id
         WHERE favorites.user_id = ?
         GROUP BY favorites.id, books.id
         ORDER BY favorites.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $Stmt->bindValue(1, $UserId, PDO::PARAM_INT);
    $Stmt->bindValue(2, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue(3, $Offset, PDO::PARAM_INT);
    $Stmt->execute();

    $Favorites = $Stmt->fetchAll();

    foreach ($Favorites as &$Item) {
        $Item["favorite_id"] = (int)$Item["favorite_id"];
        $Item["book_id"] = (int)$Item["book_id"];
        $Item["available_copies"] = (int)$Item["available_copies"];
        $Item["total_copies"] = (int)$Item["total_copies"];
        $Item["average_rating"] = (float)$Item["average_rating"];
        $Item["total_reviews"] = (int)$Item["total_reviews"];
    }

    PaginatedResponse($Favorites, $Page, $Limit, $Total);

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
