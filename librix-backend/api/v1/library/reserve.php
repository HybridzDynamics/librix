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


// Validation

$IdError = validatePositiveInteger($BookId, "Book ID");

if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$BookId = (int)$BookId;
$UserId = (int)$User["id"];


// Check Book Exists

try {
    $Stmt = $pdo->prepare("SELECT id, title FROM books WHERE id = ? LIMIT 1");
    $Stmt->execute([$BookId]);
    $Book = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

if (!$Book) {
    notFoundResponse("Book not found");
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
        errorResponse("You already have an active reservation for this book", 400);
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
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
    errorResponse("Unable to reserve book", 500);
}


// Response

successResponse(
    [
        "id" => $ReservationId,
        "book_id" => $BookId,
        "book_title" => $Book["title"],
        "user_id" => $UserId,
        "status" => "active",
        "reserved_at" => currentTime()
    ],
    "Book reserved successfully",
    201
);

?>
