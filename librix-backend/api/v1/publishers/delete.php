<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";
require __DIR__ . "/../../../middleware/auth.php";
require __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    methodNotAllowedResponse(["DELETE"]);
}


// Authentication & Admin Check

$User = requireAuth();
requireAdmin($User);
$UserId = (int)$User["id"];


// Publisher ID

$PublisherId = $publisherId ?? ($parts[3] ?? ($_GET["id"] ?? null));
if ($PublisherId === null) {
    $Input = getJsonInput();
    $PublisherId = $Input["id"] ?? null;
}

$IdError = validatePositiveInteger($PublisherId, "Publisher ID");
if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$PublisherId = (int)$PublisherId;


// Find Publisher

try {
    $Stmt = $pdo->prepare("SELECT id, name FROM publishers WHERE id = ? LIMIT 1");
    $Stmt->execute([$PublisherId]);
    $Publisher = $Stmt->fetch();

    if (!$Publisher) {
        notFoundResponse("Publisher not found");
    }

    // Set books with this publisher_id to NULL
    $BookUpdate = $pdo->prepare("UPDATE books SET publisher_id = NULL WHERE publisher_id = ?");
    $BookUpdate->execute([$PublisherId]);

    // Delete Publisher
    $DelStmt = $pdo->prepare("DELETE FROM publishers WHERE id = ?");
    $DelStmt->execute([$PublisherId]);

    logAudit($UserId, "delete_publisher", "publishers", $PublisherId, "Deleted publisher '{$Publisher['name']}'");

    successResponse(null, "Publisher deleted successfully");

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
