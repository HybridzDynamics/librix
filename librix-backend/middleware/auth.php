<?php

// Authentication Middleware

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../helpers/functions.php";


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
                users.status,
                users.org_id,
                organizations.name AS org_name,
                organizations.code AS org_code
             FROM auth_tokens
             INNER JOIN users
                ON auth_tokens.user_id = users.id
             LEFT JOIN organizations
                ON users.org_id = organizations.id
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
        "status" => $User["status"],
        "org_id" => $User["org_id"] ? (int)$User["org_id"] : null,
        "org_name" => $User["org_name"] ?? "Global Public Library",
        "org_code" => $User["org_code"] ?? "ORG-GLOBAL-00"
    ];
}

?>