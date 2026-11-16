<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}

$OrgId = $_GET["id"] ?? null;

if ($OrgId !== null) {
    $IdError = validatePositiveInteger($OrgId, "Organization ID");

    if ($IdError !== null) {
        errorResponse($IdError, 400);
    }

    try {
        $Stmt = $pdo->prepare(
            "SELECT
                organizations.id,
                organizations.name,
                organizations.code,
                organizations.description,
                organizations.contact_email,
                organizations.logo_url,
                organizations.created_at,
                organizations.updated_at,
                COUNT(DISTINCT books.id) AS total_books,
                COUNT(DISTINCT users.id) AS total_members
             FROM organizations
             LEFT JOIN books
                ON organizations.id = books.org_id
             LEFT JOIN users
                ON organizations.id = users.org_id
             WHERE organizations.id = ?
             GROUP BY organizations.id
             LIMIT 1"
        );

        $Stmt->execute([(int)$OrgId]);

        $Organization = $Stmt->fetch();

    } catch (PDOException $e) {
        errorResponse("Database error", 500);
    }

    if (!$Organization) {
        notFoundResponse("Organization not found");
    }

    $Organization["total_books"] = (int)$Organization["total_books"];
    $Organization["total_members"] = (int)$Organization["total_members"];

    successResponse($Organization);
}

$User = requireAuth();

try {
    if ($User["role"] === "admin") {

        $Stmt = $pdo->prepare(
            "SELECT
                organizations.id,
                organizations.name,
                organizations.code,
                organizations.description,
                organizations.contact_email,
                organizations.logo_url,
                organizations.created_at,
                organizations.updated_at,
                COUNT(DISTINCT books.id) AS total_books,
                COUNT(DISTINCT users.id) AS total_members
             FROM organizations
             LEFT JOIN books
                ON organizations.id = books.org_id
             LEFT JOIN users
                ON organizations.id = users.org_id
             GROUP BY organizations.id
             ORDER BY organizations.name ASC"
        );

        $Stmt->execute();

        $Organizations = $Stmt->fetchAll();

        foreach ($Organizations as &$Item) {
            $Item["total_books"] = (int)$Item["total_books"];
            $Item["total_members"] = (int)$Item["total_members"];
        }

    } else {

        $Stmt = $pdo->prepare(
            "SELECT
                id,
                name,
                code,
                description,
                logo_url
             FROM organizations
             ORDER BY name ASC"
        );

        $Stmt->execute();

        $Organizations = $Stmt->fetchAll();
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

successResponse($Organizations);

?>
