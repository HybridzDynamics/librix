<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Authentication

$User = RequireAuth();
$UserId = (int)$User["id"];


// Database Query

try {
    $Stmt = $pdo->prepare(
        "SELECT id, name, email, role, status, created_at, updated_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $Stmt->execute([$UserId]);

    $Profile = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

if (!$Profile) {
    NotFoundResponse("User not found");
}

$Profile["id"] = (int)$Profile["id"];


// Response

SuccessResponse(
    $Profile,
    "Profile retrieved successfully"
);

?>
