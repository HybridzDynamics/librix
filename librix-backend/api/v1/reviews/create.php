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
$UserId = (int)$User["id"];


// Request Data

$Input = GetJsonInput();

$BookId = $Input["book_id"] ?? null;
$Rating = $Input["rating"] ?? null;
$ReviewText = isset($Input["review_text"]) ? SanitizeString($Input["review_text"]) : null;


// Validation

$Errors = [];

$BookIdError = ValidatePositiveInteger($BookId, "Book ID");
if ($BookIdError !== null) {
    $Errors["book_id"] = $BookIdError;
}

if ($Rating === null || !is_numeric($Rating) || (int)$Rating < 1 || (int)$Rating > 5) {
    $Errors["rating"] = "Rating must be an integer between 1 and 5";
}

if (!empty($Errors)) {
    ErrorResponse("Validation failed", 422, $Errors);
}

$BookId = (int)$BookId;
$Rating = (int)$Rating;


// Check Book Exists

try {
    $BookCheck = $pdo->prepare("SELECT id FROM books WHERE id = ? LIMIT 1");
    $BookCheck->execute([$BookId]);
    if (!$BookCheck->fetch()) {
        NotFoundResponse("Book not found");
    }
} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Insert or Update Review

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO reviews (book_id, user_id, rating, review_text)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            review_text = VALUES(review_text),
            updated_at = CURRENT_TIMESTAMP"
    );

    $Stmt->execute([$BookId, $UserId, $Rating, $ReviewText]);

    // Fetch saved review
    $FetchStmt = $pdo->prepare(
        "SELECT 
            reviews.id,
            reviews.book_id,
            reviews.user_id,
            reviews.rating,
            reviews.review_text,
            reviews.created_at,
            reviews.updated_at,
            users.name AS user_name
         FROM reviews
         INNER JOIN users ON reviews.user_id = users.id
         WHERE reviews.book_id = ? AND reviews.user_id = ?
         LIMIT 1"
    );
    $FetchStmt->execute([$BookId, $UserId]);
    $Review = $FetchStmt->fetch();

    $Review["id"] = (int)$Review["id"];
    $Review["book_id"] = (int)$Review["book_id"];
    $Review["user_id"] = (int)$Review["user_id"];
    $Review["rating"] = (int)$Review["rating"];

    LogAudit($UserId, "create_or_update_review", "reviews", $Review["id"], "User reviewed book #{$BookId}");

    SuccessResponse($Review, "Review saved successfully", 201);

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
