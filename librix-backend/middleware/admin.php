<?php

// Admin Middleware

require_once __DIR__ . "/../helpers/response.php";


// Require Admin

function requireAdmin($User)
{
    if (!$User) {
        unauthorizedResponse("Authentication required");
    }

    if (!isset($User["role"]) || $User["role"] !== "admin") {
        forbiddenResponse("Admin access required");
    }

    return true;
}

?>