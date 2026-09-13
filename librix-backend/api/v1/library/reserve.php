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


// Validation

$IdError = ValidatePositiveInteger($BookId, "Book ID");

if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$BookId = (int)$BookId;
$UserId = (int)$User["id"];


// Check Book Exists

try {
    $Stmt = $pdo->prepare("SELECT id, title FROM books WHERE id = ? LIMIT 1");
    $Stmt->execute([$BookId]);
    $Book = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

if (!$Book) {
    NotFoundResponse("Book not found");
}


// Check Existing Active Reservation

try {
    $Stmt = $pdo->prepare(
        "SELECT id FROM reservations
         WHERE book_id = ? AND user_id = ? AND status = 'active'
         LIMIT 1"
    );

    $Stmt->execute([$BookId, $UserId]);

    if ($Stmt->fetch()) {
        ErrorResponse("You already have an active reservation for this book", 400);
    }

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Create Reservation

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO reservations
        (book_id, user_id, status)
        VALUES (?, ?, 'active')"
    );

    $Stmt->execute([
        $BookId,
        $UserId
    ]);

    $ReservationId = (int)$pdo->lastInsertId();

} catch (PDOException $e) {
    ErrorResponse("Unable to reserve book", 500);
}


// Response

SuccessResponse(
    [
        "id" => $ReservationId,
        "book_id" => $BookId,
        "book_title" => $Book["title"],
        "user_id" => $UserId,
        "status" => "active",
        "reserved_at" => CurrentTime()
    ],
    "Book reserved successfully",
    201
);

?>
