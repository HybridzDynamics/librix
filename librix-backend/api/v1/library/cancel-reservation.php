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

$ReservationId = $RequestData["reservation_id"] ?? null;
$BookId = $RequestData["book_id"] ?? null;


// Validation

if ($ReservationId === null && $BookId === null) {
    ErrorResponse("Either reservation_id or book_id is required", 400);
}

$UserId = (int)$User["id"];
$IsAdmin = ($User["role"] === "admin");


// Find Reservation

try {
    if ($ReservationId !== null) {
        $IdError = ValidatePositiveInteger($ReservationId, "Reservation ID");

        if ($IdError !== null) {
            ErrorResponse($IdError, 400);
        }

        $Stmt = $pdo->prepare(
            "SELECT id, book_id, user_id, status
             FROM reservations
             WHERE id = ?
             LIMIT 1"
        );

        $Stmt->execute([(int)$ReservationId]);

    } else {
        $BookIdError = ValidatePositiveInteger($BookId, "Book ID");

        if ($BookIdError !== null) {
            ErrorResponse($BookIdError, 400);
        }

        $Stmt = $pdo->prepare(
            "SELECT id, book_id, user_id, status
             FROM reservations
             WHERE book_id = ? AND user_id = ? AND status = 'active'
             ORDER BY id DESC
             LIMIT 1"
        );

        $Stmt->execute([(int)$BookId, $UserId]);
    }

    $Reservation = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

if (!$Reservation) {
    NotFoundResponse("Reservation not found");
}

if (!$IsAdmin && (int)$Reservation["user_id"] !== $UserId) {
    ForbiddenResponse("You can only cancel your own reservations");
}

if ($Reservation["status"] !== "active") {
    ErrorResponse("Reservation is already " . $Reservation["status"], 400);
}

$TargetReservationId = (int)$Reservation["id"];


// Cancel Reservation

try {
    $Stmt = $pdo->prepare(
        "UPDATE reservations
         SET status = 'cancelled'
         WHERE id = ?"
    );

    $Stmt->execute([$TargetReservationId]);

} catch (PDOException $e) {
    ErrorResponse("Unable to cancel reservation", 500);
}


// Response

SuccessResponse(
    [
        "reservation_id" => $TargetReservationId,
        "status" => "cancelled"
    ],
    "Reservation cancelled successfully"
);

?>
