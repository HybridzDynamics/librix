<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Parameters

$Page = validatePageNumber($_GET["page"] ?? 1);
$Limit = validatePageLimit($_GET["limit"] ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
$Offset = ($Page - 1) * $Limit;

$ServiceParam = isset($_GET["service"]) ? trim($_GET["service"]) : null;

$AllowedServices = ["API", "Database", "Authentication", "Books", "Authors", "Library", "Admin", "Search"];

$WhereClause = "";
$Bindings = [];

if ($ServiceParam !== null && $ServiceParam !== "") {
    if (!in_array($ServiceParam, $AllowedServices, true)) {
        errorResponse("Invalid service name. Allowed: " . implode(", ", $AllowedServices), 422);
    }
    $WhereClause = "WHERE service = ?";
    $Bindings[] = $ServiceParam;
}


// Total Count

try {
    $CountSql = "SELECT COUNT(*) AS total FROM status_checks $WhereClause";
    $CountStmt = $pdo->prepare($CountSql);
    $CountStmt->execute($Bindings);
    $Total = (int)($CountStmt->fetchColumn() ?: 0);

} catch (PDOException $e) {
    errorResponse("Unable to retrieve status history", 500);
}


// Fetch Checks

try {
    $DataSql = "SELECT
                    id,
                    service,
                    status,
                    response_time,
                    checked_at
                FROM status_checks
                $WhereClause
                ORDER BY checked_at DESC
                LIMIT ? OFFSET ?";

    $Stmt = $pdo->prepare($DataSql);

    $ParamIndex = 1;
    foreach ($Bindings as $Binding) {
        $Stmt->bindValue($ParamIndex++, $Binding);
    }
    $Stmt->bindValue($ParamIndex++, (int)$Limit, PDO::PARAM_INT);
    $Stmt->bindValue($ParamIndex++, (int)$Offset, PDO::PARAM_INT);

    $Stmt->execute();

    $History = $Stmt->fetchAll();

    foreach ($History as &$Item) {
        $Item["id"] = (int)$Item["id"];
        $Item["response_time"] = (int)$Item["response_time"];
    }

} catch (PDOException $e) {
    errorResponse("Unable to retrieve status history", 500);
}


// Response

paginatedResponse(
    $History,
    $Page,
    $Limit,
    $Total
);

?>
