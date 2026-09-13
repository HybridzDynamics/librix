<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    MethodNotAllowedResponse(["POST"]);
}


// Authentication

$User = RequireAuth();


// Request Data

$RequestData = GetJsonInput();

$BookId = $RequestData["book_id"] ?? null;
$DueDate = $RequestData["due_date"] ?? date("Y-m-d", strtotime("+14 days"));


// Validation

$Errors = [];

$BookIdError = ValidatePositiveInteger($BookId, "Book ID");

if ($BookIdError !== null) {
    $Errors["book_id"] = $BookIdError;
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", (string)$DueDate)) {
    $Errors["due_date"] = "Due date must be in YYYY-MM-DD format";
}

if (HasValidationErrors($Errors)) {
    ValidationErrorResponse($Errors);
}

$BookId = (int)$BookId;
$UserId = (int)$User["id"];


// Check Book Availability

try {
    $Stmt = $pdo->prepare(
        "SELECT id, title, available_copies
         FROM books
         WHERE id = ?
         LIMIT 1"
    );

    $Stmt->execute([$BookId]);

    $Book = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

if (!$Book) {
    NotFoundResponse("Book not found");
}

if ((int)$Book["available_copies"] <= 0) {
    ErrorResponse("Book is currently not available for issue", 400);
}


// Check User Existing Active Issue

try {
    $Stmt = $pdo->prepare(
        "SELECT id FROM book_issues
         WHERE book_id = ? AND user_id = ? AND status = 'issued'
         LIMIT 1"
    );

    $Stmt->execute([$BookId, $UserId]);

    if ($Stmt->fetch()) {
        ErrorResponse("You have already issued this book", 400);
    }

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Issue Book Transaction

try {
    $pdo->beginTransaction();

    // Decrease Available Copies
    $Stmt = $pdo->prepare(
        "UPDATE books
         SET available_copies = available_copies - 1
         WHERE id = ? AND available_copies > 0"
    );

    $Stmt->execute([$BookId]);

    if ($Stmt->rowCount() === 0) {
        $pdo->rollBack();
        ErrorResponse("Book is no longer available", 400);
    }

    // Create Issue Record
    $Stmt = $pdo->prepare(
        "INSERT INTO book_issues
        (book_id, user_id, due_date, status)
        VALUES (?, ?, ?, 'issued')"
    );

    $Stmt->execute([
        $BookId,
        $UserId,
        $DueDate
    ]);

    $IssueId = (int)$pdo->lastInsertId();

    // Mark Existing Reservation Fulfilled if any
    $Stmt = $pdo->prepare(
        "UPDATE reservations
         SET status = 'fulfilled'
         WHERE book_id = ? AND user_id = ? AND status = 'active'"
    );

    $Stmt->execute([$BookId, $UserId]);

    $pdo->commit();

    LogAudit($UserId, "book_issued", "books", $BookId, "Book #$BookId issued to user #$UserId due $DueDate");

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ErrorResponse("Unable to issue book", 500);
}


// Fetch Created Issue

try {
    $Stmt = $pdo->prepare(
        "SELECT
            book_issues.id,
            book_issues.book_id,
            books.title AS book_title,
            book_issues.user_id,
            book_issues.issued_at,
            book_issues.due_date,
            book_issues.status
         FROM book_issues
         INNER JOIN books
            ON book_issues.book_id = books.id
         WHERE book_issues.id = ?
         LIMIT 1"
    );

    $Stmt->execute([$IssueId]);

    $IssueRecord = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Unable to retrieve issue details", 500);
}


// Response

SuccessResponse(
    $IssueRecord,
    "Book issued successfully",
    201
);

?>
