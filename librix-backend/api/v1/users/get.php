<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}

$User = requireAuth();
$UserId = (int)$User["id"];

try {
    $Stmt = $pdo->prepare(
        "SELECT id, name, email, role, status, profile_picture_url, phone, address, bio, org_id, created_at, updated_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $Stmt->execute([$UserId]);

    $UserData = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

if (!$UserData) {
    notFoundResponse("User not found");
}

$UserData["id"] = (int)$UserData["id"];
$UserData["org_id"] = $UserData["org_id"] ? (int)$UserData["org_id"] : null;

if ($UserData["org_id"]) {
    try {
        $OrgStmt = $pdo->prepare(
            "SELECT id, name, code, logo_url
             FROM organizations
             WHERE id = ?
             LIMIT 1"
        );
        $OrgStmt->execute([$UserData["org_id"]]);
        $OrgData = $OrgStmt->fetch();

        if ($OrgData) {
            $UserData["org_name"] = $OrgData["name"];
            $UserData["org_code"] = $OrgData["code"];
            $UserData["org_logo_url"] = $OrgData["logo_url"];
        }
    } catch (PDOException $e) {

    }
}

successResponse(
    $UserData,
    "User data retrieved successfully"
);

?>
