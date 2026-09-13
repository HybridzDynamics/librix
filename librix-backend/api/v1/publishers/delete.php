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


// Authentication & Admin Check

$User = RequireAuth();
RequireAdmin($User);
$UserId = (int)$User["id"];


// Publisher ID

$PublisherId = $publisherId ?? ($parts[3] ?? ($_GET["id"] ?? null));
if ($PublisherId === null) {
    $Input = GetJsonInput();
    $PublisherId = $Input["id"] ?? null;
}

$IdError = ValidatePositiveInteger($PublisherId, "Publisher ID");
if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$PublisherId = (int)$PublisherId;


// Find Publisher

try {
    $Stmt = $pdo->prepare("SELECT id, name FROM publishers WHERE id = ? LIMIT 1");
    $Stmt->execute([$PublisherId]);
    $Publisher = $Stmt->fetch();

    if (!$Publisher) {
        NotFoundResponse("Publisher not found");
    }

    // Set books with this publisher_id to NULL
    $BookUpdate = $pdo->prepare("UPDATE books SET publisher_id = NULL WHERE publisher_id = ?");
    $BookUpdate->execute([$PublisherId]);

    // Delete Publisher
    $DelStmt = $pdo->prepare("DELETE FROM publishers WHERE id = ?");
    $DelStmt->execute([$PublisherId]);

    LogAudit($UserId, "delete_publisher", "publishers", $PublisherId, "Deleted publisher '{$Publisher['name']}'");

    SuccessResponse(null, "Publisher deleted successfully");

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
