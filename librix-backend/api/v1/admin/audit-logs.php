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


// Parameters

$Page = validatePageNumber($_GET["page"] ?? 1);
$Limit = validatePageLimit($_GET["limit"] ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
$Offset = ($Page - 1) * $Limit;

$Action = isset($_GET["action"]) ? trim($_GET["action"]) : null;
$EntityType = isset($_GET["entity_type"]) ? trim($_GET["entity_type"]) : null;
$UserId = isset($_GET["user_id"]) ? $_GET["user_id"] : null;
$StartDate = isset($_GET["start_date"]) ? trim($_GET["start_date"]) : null;
$EndDate = isset($_GET["end_date"]) ? trim($_GET["end_date"]) : null;


// Build Query

$WhereClauses = [];
$Bindings = [];

if ($Action !== null && $Action !== "") {
    $WhereClauses[] = "action LIKE ?";
    $Bindings[] = "%$Action%";
}

if ($EntityType !== null && $EntityType !== "") {
    $WhereClauses[] = "entity_type = ?";
    $Bindings[] = $EntityType;
}

if ($UserId !== null && $UserId !== "") {
    $WhereClauses[] = "user_id = ?";
    $Bindings[] = (int)$UserId;
}

if ($StartDate !== null && $StartDate !== "") {
    $WhereClauses[] = "created_at >= ?";
    $Bindings[] = $StartDate;
}

if ($EndDate !== null && $EndDate !== "") {
    $WhereClauses[] = "created_at <= ?";
    $Bindings[] = $EndDate;
}

$WhereSql = "";
if (!empty($WhereClauses)) {
    $WhereSql = "WHERE " . implode(" AND ", $WhereClauses);
}


// Count Total

try {
    $CountSql = "SELECT COUNT(*) AS total FROM audit_logs $WhereSql";
    $CountStmt = $pdo->prepare($CountSql);
    $CountStmt->execute($Bindings);
    $Total = (int)($CountStmt->fetchColumn() ?: 0);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Fetch Audit Logs

try {
    $DataSql = "SELECT
                    al.id,
                    al.user_id,
                    u.name AS user_name,
                    u.email AS user_email,
                    al.org_id,
                    o.name AS org_name,
                    al.action,
                    al.entity_type,
                    al.entity_id,
                    al.description,
                    al.ip_address,
                    al.created_at
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN organizations o ON al.org_id = o.id
                $WhereSql
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";

    $Stmt = $pdo->prepare($DataSql);

    $ParamIndex = 1;
    foreach ($Bindings as $Binding) {
        $Stmt->bindValue($ParamIndex++, $Binding);
    }
    $Stmt->bindValue($ParamIndex++, (int)$Limit, PDO::PARAM_INT);
    $Stmt->bindValue($ParamIndex++, (int)$Offset, PDO::PARAM_INT);

    $Stmt->execute();

    $AuditLogs = $Stmt->fetchAll();

    foreach ($AuditLogs as &$Log) {
        $Log["id"] = (int)$Log["id"];
        $Log["user_id"] = $Log["user_id"] ? (int)$Log["user_id"] : null;
        $Log["org_id"] = $Log["org_id"] ? (int)$Log["org_id"] : null;
        $Log["entity_id"] = $Log["entity_id"] ? (int)$Log["entity_id"] : null;
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Response

paginatedResponse(
    $AuditLogs,
    $Page,
    $Limit,
    $Total
);

?>
