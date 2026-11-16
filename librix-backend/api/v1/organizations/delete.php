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

$OrgId = $_GET["id"] ?? null;

$IdError = validatePositiveInteger($OrgId, "Organization ID");

if ($IdError !== null) {
    errorResponse($IdError, 400);
}

try {
    $Stmt = $pdo->prepare("SELECT id, name FROM organizations WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$OrgId]);

    $Organization = $Stmt->fetch();

    if (!$Organization) {
        notFoundResponse("Organization not found");
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

try {
    $Stmt = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*) FROM books WHERE org_id = ?) as book_count,
            (SELECT COUNT(*) FROM users WHERE org_id = ?) as user_count"
    );

    $Stmt->execute([(int)$OrgId, (int)$OrgId]);
    $Counts = $Stmt->fetch();

    if ($Counts["book_count"] > 0 || $Counts["user_count"] > 0) {
        errorResponse("Cannot delete organization with existing books or users", 400);
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

try {
    $Stmt = $pdo->prepare("DELETE FROM organizations WHERE id = ?");
    $Stmt->execute([(int)$OrgId]);

    logAudit($User["id"], "organization_deleted", "organizations", $OrgId, "Deleted organization: {$Organization['name']}");

} catch (PDOException $e) {
    errorResponse("Unable to delete organization", 500);
}

successResponse(null, "Organization deleted successfully");

?>
