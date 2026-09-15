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
$Page = validatePageNumber($_GET["page"] ?? 1);
$Limit = validatePageLimit($_GET["limit"] ?? 20, 20, 100);
$Offset = ($Page - 1) * $Limit;


// Build Query

$WhereClauses = [];
$Bindings = [];

if ($Status !== "" && in_array($Status, ["pending", "approved", "rejected"])) {
    $WhereClauses[] = "status = ?";
    $Bindings[] = $Status;
}

$WhereSql = "";
if (!empty($WhereClauses)) {
    $WhereSql = "WHERE " . implode(" AND ", $WhereClauses);
}


// Count Total

try {
    $CountSql = "SELECT COUNT(*) as total FROM librarian_requests $WhereSql";
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
                    lr.*,
                    u.name as user_name,
                    u.email as user_email,
                    o.name as org_name
                 FROM librarian_requests lr
                 LEFT JOIN users u ON lr.user_id = u.id
                 LEFT JOIN organizations o ON lr.org_id = o.id
                 $WhereSql
                 ORDER BY lr.created_at DESC
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