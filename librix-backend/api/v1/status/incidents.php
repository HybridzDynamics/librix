<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Filter Parameters

$Status = isset($_GET["status"]) ? trim($_GET["status"]) : null;

$WhereClause = "";
$Bindings = [];

if ($Status !== null && $Status !== "") {
    $WhereClause = "WHERE status = ?";
    $Bindings[] = $Status;
}


// Query Incidents

try {
    $Stmt = $pdo->prepare(
        "SELECT
            id,
            title,
            description,
            status,
            severity,
            started_at,
            resolved_at,
            created_at,
            updated_at
         FROM status_incidents
         $WhereClause
         ORDER BY started_at DESC"
    );

    $Stmt->execute($Bindings);

    $Incidents = $Stmt->fetchAll();

    foreach ($Incidents as &$Item) {
        $Item["id"] = (int)$Item["id"];
    }

} catch (PDOException $e) {
    errorResponse("Unable to retrieve incidents", 500);
}


// Response

successResponse($Incidents);

?>
