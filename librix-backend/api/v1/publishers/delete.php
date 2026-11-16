<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    methodNotAllowedResponse(["DELETE"]);
}

$User = requireAuth();
requireAdmin($User);
$UserId = (int)$User["id"];

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

try {
    $Stmt = $pdo->prepare("SELECT id, name FROM publishers WHERE id = ? LIMIT 1");
    $Stmt->execute([$PublisherId]);
    $Publisher = $Stmt->fetch();

    if (!$Publisher) {
        notFoundResponse("Publisher not found");
    }

    $BookUpdate = $pdo->prepare("UPDATE books SET publisher_id = NULL WHERE publisher_id = ?");
    $BookUpdate->execute([$PublisherId]);

    $DelStmt = $pdo->prepare("DELETE FROM publishers WHERE id = ?");
    $DelStmt->execute([$PublisherId]);

    logAudit($UserId, "delete_publisher", "publishers", $PublisherId, "Deleted publisher '{$Publisher['name']}'");

    successResponse(null, "Publisher deleted successfully");

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
