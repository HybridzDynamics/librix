<?php

require_once __DIR__ . "/../helpers/response.php";

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

function getOrgScope($User)
{
    if (!$User) return null;

    $customOrg = $_GET["org_id"] ?? $_SERVER["HTTP_X_ORG_ID"] ?? null;
    if ($customOrg) {
        return (int)$customOrg;
    }

    return isset($User["org_id"]) ? (int)$User["org_id"] : null;
}

?>
