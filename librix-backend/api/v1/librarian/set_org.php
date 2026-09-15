<?php

// Set / Switch Active Organization

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/librarian.php";

$User = requireAuth();
requireLibrarian($User);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$Body = getRequestBody();
$OrgId = isset($Body["org_id"]) ? (int)$Body["org_id"] : 0;
$OrgCode = $Body["org_code"] ?? null;

try {
    $Org = null;

    if ($OrgId > 0) {
        $Stmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ?");
        $Stmt->execute([$OrgId]);
        $Org = $Stmt->fetch();
    } else if ($OrgCode) {
        $Stmt = $pdo->prepare("SELECT * FROM organizations WHERE code = ?");
        $Stmt->execute([$OrgCode]);
        $Org = $Stmt->fetch();
    }

    if (!$Org) {
        notFoundResponse("Organization not found");
    }

    // Update user's current org_id in DB
    $UpdateStmt = $pdo->prepare("UPDATE users SET org_id = ? WHERE id = ?");
    $UpdateStmt->execute([(int)$Org["id"], (int)$User["id"]]);

    successResponse([
        "message" => "Active organization successfully updated",
        "organization" => [
            "id" => (int)$Org["id"],
            "name" => $Org["name"],
            "code" => $Org["code"]
        ]
    ]);

} catch (PDOException $e) {
    errorResponse("Database error: " . $e->getMessage(), 500);
}

?>
