<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";
require __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();
$UserId = (int)$User["id"];


// Request Data

$Input = getJsonInput();
$BookId = $Input["book_id"] ?? null;

$IdError = validatePositiveInteger($BookId, "Book ID");
if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$BookId = (int)$BookId;


// Check Book Exists

try {
    $BookCheck = $pdo->prepare("SELECT id FROM books WHERE id = ? LIMIT 1");
    $BookCheck->execute([$BookId]);
    if (!$BookCheck->fetch()) {
        notFoundResponse("Book not found");
    }
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Check if already favorited

try {
    $Stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND book_id = ? LIMIT 1");
    $Stmt->execute([$UserId, $BookId]);
    $Existing = $Stmt->fetch();

    if ($Existing) {
        // Remove from favorites
        $DelStmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
        $DelStmt->execute([(int)$Existing["id"]]);

        successResponse([
            "favorited" => false,
            "book_id" => $BookId
        ], "Book removed from favorites");
    } else {
        // Add to favorites
        $AddStmt = $pdo->prepare("INSERT INTO favorites (user_id, book_id) VALUES (?, ?)");
        $AddStmt->execute([$UserId, $BookId]);

        successResponse([
            "favorited" => true,
            "book_id" => $BookId
        ], "Book added to favorites", 201);
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
