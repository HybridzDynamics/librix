<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/functions.php";
require __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication

$User = requireAuth();
$UserId = (int)$User["id"];


// Currently Issued Books

try {
    $Stmt = $pdo->prepare(
        "SELECT
            book_issues.id AS issue_id,
            book_issues.book_id,
            books.title,
            books.isbn,
            books.category,
            books.cover_image,
            authors.name AS author_name,
            book_issues.issued_at,
            book_issues.due_date,
            book_issues.status
         FROM book_issues
         INNER JOIN books
            ON book_issues.book_id = books.id
         LEFT JOIN authors
            ON books.author_id = authors.id
         WHERE book_issues.user_id = ?
           AND book_issues.status IN ('issued', 'overdue')
         ORDER BY book_issues.issued_at DESC"
    );

    $Stmt->execute([$UserId]);

    $CurrentlyIssued = $Stmt->fetchAll();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Borrowing History

try {
    $Stmt = $pdo->prepare(
        "SELECT
            book_issues.id AS issue_id,
            book_issues.book_id,
            books.title,
            books.isbn,
            authors.name AS author_name,
            book_issues.issued_at,
            book_issues.due_date,
            book_issues.returned_at,
            book_issues.status
         FROM book_issues
         INNER JOIN books
            ON book_issues.book_id = books.id
         LEFT JOIN authors
            ON books.author_id = authors.id
         WHERE book_issues.user_id = ?
           AND book_issues.status = 'returned'
         ORDER BY book_issues.returned_at DESC"
    );

    $Stmt->execute([$UserId]);

    $History = $Stmt->fetchAll();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Reservations

try {
    $Stmt = $pdo->prepare(
        "SELECT
            reservations.id AS reservation_id,
            reservations.book_id,
            books.title,
            books.isbn,
            authors.name AS author_name,
            reservations.reserved_at,
            reservations.status
         FROM reservations
         INNER JOIN books
            ON reservations.book_id = books.id
         LEFT JOIN authors
            ON books.author_id = authors.id
         WHERE reservations.user_id = ?
         ORDER BY reservations.reserved_at DESC"
    );

    $Stmt->execute([$UserId]);

    $Reservations = $Stmt->fetchAll();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Fines

try {
    $Stmt = $pdo->prepare(
        "SELECT
            id,
            issue_id,
            amount,
            reason,
            status,
            created_at,
            paid_at
         FROM fines
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );

    $Stmt->execute([$UserId]);

    $Fines = $Stmt->fetchAll();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Response

successResponse([
    "currently_issued" => $CurrentlyIssued,
    "history" => $History,
    "reservations" => $Reservations,
    "fines" => $Fines
]);

?>
