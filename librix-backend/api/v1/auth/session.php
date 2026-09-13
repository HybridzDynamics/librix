<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Authorization Token

$AccessToken = GetAuthorizationToken();


// Check Token

if (!$AccessToken) {
    UnauthorizedResponse("Authorization token is required");
}


// Find Token

try {
    $Stmt = $pdo->prepare(
        "SELECT
            auth_tokens.id,
            auth_tokens.user_id,
            auth_tokens.expires_at,
            users.name,
            users.email,
            users.role,
            users.status
         FROM auth_tokens
         INNER JOIN users
            ON auth_tokens.user_id = users.id
         WHERE auth_tokens.token = ?
         LIMIT 1"
    );

    $Stmt->execute([$AccessToken]);

    $Session = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Check Token

if (!$Session) {
    UnauthorizedResponse("Invalid authentication token");
}


// Check Expiration

if (strtotime($Session["expires_at"]) <= time()) {

    try {
        $Stmt = $pdo->prepare(
            "DELETE FROM auth_tokens
             WHERE token = ?"
        );

        $Stmt->execute([$AccessToken]);

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    UnauthorizedResponse("Authentication token has expired");
}


// Check User Status

if ($Session["status"] !== "active") {
    UnauthorizedResponse("User account is not active");
}


// Session Response

SuccessResponse(
    [
        "authenticated" => true,
        "user" => [
            "id" => (int)$Session["user_id"],
            "name" => $Session["name"],
            "email" => $Session["email"],
            "role" => $Session["role"],
            "status" => $Session["status"]
        ],
        "expires_at" => $Session["expires_at"]
    ],
    "Session is active"
);

?>