<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication

$User = requireAuth();
requireAdmin($User);


// Query Parameters

$Status = $_GET["status"] ?? "";
$OrgId = $_GET["org_id"] ?? "";
$Page = validatePageNumber($_GET["page"] ?? 1);
$Limit = validatePageLimit($_GET["limit"] ?? 20, 20, 100);
$Offset = ($Page - 1) * $Limit;


// Build Query

$WhereClauses = [];
$Bindings = [];

if ($Status !== "" && in_array($Status, ["pending", "approved", "rejected"])) {
    $WhereClauses[] = "jr.status = ?";
    $Bindings[] = $Status;
}

if ($OrgId !== "") {
    $WhereClauses[] = "jr.org_id = ?";
    $Bindings[] = (int)$OrgId;
}

$WhereSql = "";
if (!empty($WhereClauses)) {
    $WhereSql = "WHERE " . implode(" AND ", $WhereClauses);
}


// Count Total

try {
    $CountSql = "SELECT COUNT(*) as total FROM org_join_requests jr $WhereSql";
    $Stmt = $pdo->prepare($CountSql);
    $Stmt->execute($Bindings);
    $TotalRow = $Stmt->fetch();
    $Total = (int)($TotalRow["total"] ?? 0);
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Fetch Requests

try {
    $DataSql = "SELECT
                    jr.*,
                    o.name as org_name,
                    o.code as org_code,
                    u.name as user_name,
                    u.email as user_email
                 FROM org_join_requests jr
                 JOIN organizations o ON jr.org_id = o.id
                 JOIN users u ON jr.user_id = u.id
                 $WhereSql
                 ORDER BY jr.created_at DESC
                 LIMIT ? OFFSET ?";
    
    $Stmt = $pdo->prepare($DataSql);
    
    $ParamIndex = 1;
    foreach ($Bindings as $Binding) {
        $Stmt->bindValue($ParamIndex++, $Binding);
    }
    $Stmt->bindValue($ParamIndex++, (int)$Limit, PDO::PARAM_INT);
    $Stmt->bindValue($ParamIndex++, (int)$Offset, PDO::PARAM_INT);
    
    $Stmt->execute();
    
    $Requests = $Stmt->fetchAll();
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

paginatedResponse($Requests, $Page, $Limit, $Total);

?>