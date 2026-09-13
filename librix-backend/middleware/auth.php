<?php

// Authentication Middleware

require __DIR__ . "/../config/database.php";
require __DIR__ . "/../helpers/response.php";
require __DIR__ . "/../helpers/functions.php";


// Require Authentication

function requireAuth()
{
    global $pdo;

    $Token = getAuthorizationToken();

    if (!$Token) {
        unauthorizedResponse("Authorization token is required");
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
        errorResponse("Database error", 500);
    }


    // Check Token

    if (!$User) {
        unauthorizedResponse("Invalid authentication token");
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
            errorResponse("Database error", 500);
        }

        unauthorizedResponse("Authentication token has expired");
    }


    // Check User Status

    if ($User["status"] !== "active") {
        unauthorizedResponse("User account is not active");
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