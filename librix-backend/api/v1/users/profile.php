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
        "SELECT id, name, email, role, status, profile_picture_url, phone, address, bio, created_at, updated_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $Stmt->execute([$UserId]);

    $Profile = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

if (!$Profile) {
    notFoundResponse("User not found");
}

$Profile["id"] = (int)$Profile["id"];

successResponse(
    $Profile,
    "Profile retrieved successfully"
);

?>
