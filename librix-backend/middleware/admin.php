<?php

// Admin Middleware

require_once __DIR__ . "/../helpers/response.php";


// Require Admin

function RequireAdmin($User)
{
    if (!$User) {
        UnauthorizedResponse("Authentication required");
    }

    if (!isset($User["role"]) || $User["role"] !== "admin") {
        ForbiddenResponse("Admin access required");
    }

    return true;
}

?>