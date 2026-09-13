<?php

// Authentication Middleware

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../helpers/functions.php";


// Require Authentication

function RequireAuth()
{
    global $pdo;

    $Token = GetAuthorizationToken();

    if (!$Token) {
        UnauthorizedResponse("Authorization token is required");
    }


    // Find Session

    try {
        $Stmt = $pdo->prepare(
            "SELECT
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

        $Stmt->execute([$Token]);

        $User = $Stmt->fetch();

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }


    // Check Token

    if (!$User) {
        UnauthorizedResponse("Invalid authentication token");
    }


    // Check Expiration

    if (strtotime($User["expires_at"]) <= time()) {

        try {
            $Stmt = $pdo->prepare(
                "DELETE FROM auth_tokens
                 WHERE token = ?"
            );

            $Stmt->execute([$Token]);

        } catch (PDOException $e) {
            ErrorResponse("Database error", 500);
        }

        UnauthorizedResponse("Authentication token has expired");
    }


    // Check User Status

    if ($User["status"] !== "active") {
        UnauthorizedResponse("User account is not active");
    }


    // Return User

    return [
        "id" => (int)$User["user_id"],
        "name" => $User["name"],
        "email" => $User["email"],
        "role" => $User["role"],
        "status" => $User["status"]
    ];
}

?>