<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    MethodNotAllowedResponse(["DELETE"]);
}


// Authentication

$User = RequireAuth();
RequireAdmin($User);


// Request Data

$BookId = $bookId ?? ($parts[3] ?? null);

$IdError = ValidatePositiveInteger($BookId, "Book ID");

if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$BookId = (int)$BookId;


// Check Existing Book

try {
    $Stmt = $pdo->prepare("SELECT id, title FROM books WHERE id = ? LIMIT 1");
    $Stmt->execute([$BookId]);
    $ExistingBook = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

if (!$ExistingBook) {
    NotFoundResponse("Book not found");
}


// Check Active Issues

try {
    $Stmt = $pdo->prepare(
        "SELECT COUNT(*) AS active_count
         FROM book_issues
         WHERE book_id = ? AND status = 'issued'"
    );

    $Stmt->execute([$BookId]);
    $ActiveIssues = $Stmt->fetch();

    if ($ActiveIssues && (int)$ActiveIssues["active_count"] > 0) {
        ErrorResponse("Cannot delete book while copies are currently issued to users", 400);
    }

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Delete Book

try {
    $Stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    $Stmt->execute([$BookId]);

    LogAudit((int)$User["id"], "book_deleted", "books", $BookId, "Deleted book '{$ExistingBook['title']}'");

} catch (PDOException $e) {
    ErrorResponse("Unable to delete book", 500);
}


// Response

SuccessResponse(
    [
        "id" => $BookId,
        "title" => $ExistingBook["title"]
    ],
    "Book deleted successfully"
);

?>
