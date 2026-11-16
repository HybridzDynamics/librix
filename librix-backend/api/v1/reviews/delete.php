<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    methodNotAllowedResponse(["DELETE"]);
}

$User = requireAuth();
$UserId = (int)$User["id"];
$IsAdmin = ($User["role"] === "admin");

$ReviewId = $reviewId ?? ($parts[3] ?? ($_GET["id"] ?? null));

if ($ReviewId === null) {
    $Input = getJsonInput();
    $ReviewId = $Input["id"] ?? null;
}

$IdError = validatePositiveInteger($ReviewId, "Review ID");
if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$ReviewId = (int)$ReviewId;

try {
    $Stmt = $pdo->prepare("SELECT id, user_id, book_id FROM reviews WHERE id = ? LIMIT 1");
    $Stmt->execute([$ReviewId]);
    $Review = $Stmt->fetch();

    if (!$Review) {
        notFoundResponse("Review not found");
    }

    if (!$IsAdmin && (int)$Review["user_id"] !== $UserId) {
        forbiddenResponse("You are not authorized to delete this review");
    }

    $DelStmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $DelStmt->execute([$ReviewId]);

    logAudit($UserId, "delete_review", "reviews", $ReviewId, "Review #{$ReviewId} deleted");

    successResponse(null, "Review deleted successfully");

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
