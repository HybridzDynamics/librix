<?php

// Librarian RBAC Middleware

require_once __DIR__ . "/../helpers/response.php";


// Require Librarian or Admin

function requireLibrarian($User)
{
    if (!$User) {
        unauthorizedResponse("Authentication required");
    }

    $role = $User["role"] ?? "user";

    if ($role !== "librarian" && $role !== "admin") {
        forbiddenResponse("Librarian or Administrator access required");
    }

    return true;
}


// Get Org Scope (Returns null for Superadmin if unconstrained, or org_id for Librarian)

function getOrgScope($User)
{
    if (!$User) return null;

    // If active_org_id header or query is passed by admin/librarian
    $customOrg = $_GET["org_id"] ?? $_SERVER["HTTP_X_ORG_ID"] ?? null;
    if ($customOrg) {
        return (int)$customOrg;
    }

    return isset($User["org_id"]) ? (int)$User["org_id"] : null;
}

?>
