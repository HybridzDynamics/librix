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
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();


// Request Data

$RequestData = getJsonInput();

$BookId = $RequestData["book_id"] ?? null;
$Rating = $RequestData["rating"] ?? null;
$ReviewText = trim($RequestData["review_text"] ?? "");


// Validation

$Errors = [];

$IdError = validatePositiveInteger($BookId, "Book ID");
if ($IdError !== null) {
    $Errors["book_id"] = $IdError;
}

if ($Rating === null || !is_numeric($Rating) || $Rating < 1 || $Rating > 5) {
    $Errors["rating"] = "Rating must be between 1 and 5";
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}


// Check if book exists

try {
    $Stmt = $pdo->prepare("SELECT id, title FROM books WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$BookId]);
    $Book = $Stmt->fetch();
    
    if (!$Book) {
        notFoundResponse("Book not found");
    }
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Check if user already reviewed this book

try {
    $Stmt = $pdo->prepare(
        "SELECT id FROM reviews WHERE book_id = ? AND user_id = ? LIMIT 1"
    );
    $Stmt->execute([(int)$BookId, $User["id"]]);
    
    if ($Stmt->fetch()) {
        errorResponse("You have already reviewed this book", 400);
    }
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Create review

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO reviews (book_id, user_id, rating, review_text)
        VALUES (?, ?, ?, ?)"
    );
    
    $Stmt->execute([
        (int)$BookId,
        $User["id"],
        (int)$Rating,
        $ReviewText ?: null
    ]);
    
    $ReviewId = (int)$pdo->lastInsertId();
    
    logAudit($User["id"], "review_created", "reviews", $ReviewId, "Reviewed book: {$Book['title']}");
    
} catch (PDOException $e) {
    errorResponse("Failed to create review", 500);
}


// Fetch created review

try {
    $Stmt = $pdo->prepare(
        "SELECT 
            reviews.*,
            users.name AS user_name
         FROM reviews
         INNER JOIN users ON reviews.user_id = users.id
         WHERE reviews.id = ? LIMIT 1"
    );
    
    $Stmt->execute([$ReviewId]);
    $Review = $Stmt->fetch();
    
    $Review["id"] = (int)$Review["id"];
    $Review["book_id"] = (int)$Review["book_id"];
    $Review["user_id"] = (int)$Review["user_id"];
    $Review["rating"] = (int)$Review["rating"];
    
} catch (PDOException $e) {
    errorResponse("Failed to retrieve review", 500);
}

successResponse($Review, "Review submitted successfully", 201);

?>