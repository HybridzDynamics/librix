<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Authentication

$User = RequireAuth();
RequireAdmin($User);


// Database Query

try {
    $Stmt = $pdo->prepare(
        "SELECT
            books.id,
            books.title,
            books.isbn,
            books.category,
            books.publisher,
            books.publication_year,
            books.total_copies,
            books.available_copies,
            (books.total_copies - books.available_copies) AS issued_copies,
            books.cover_image,
            books.created_at,
            authors.id AS author_id,
            authors.name AS author_name,
            (SELECT COUNT(*) FROM book_issues WHERE book_issues.book_id = books.id AND book_issues.status = 'issued') AS active_issues_count,
            (SELECT COUNT(*) FROM reservations WHERE reservations.book_id = books.id AND reservations.status = 'active') AS active_reservations_count
         FROM books
         LEFT JOIN authors
            ON books.author_id = authors.id
         ORDER BY books.id DESC"
    );

    $Stmt->execute();

    $Books = $Stmt->fetchAll();

    foreach ($Books as &$Item) {
        $Item["id"] = (int)$Item["id"];
        $Item["author_id"] = $Item["author_id"] !== null ? (int)$Item["author_id"] : null;
        $Item["total_copies"] = (int)$Item["total_copies"];
        $Item["available_copies"] = (int)$Item["available_copies"];
        $Item["issued_copies"] = (int)$Item["issued_copies"];
        $Item["active_issues_count"] = (int)$Item["active_issues_count"];
        $Item["active_reservations_count"] = (int)$Item["active_reservations_count"];
    }

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Response

SuccessResponse(
    $Books,
    "Book management list retrieved successfully"
);

?>
