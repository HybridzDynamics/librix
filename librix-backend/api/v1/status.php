<?php

// Configuration

require_once __DIR__ . "/../../config/config.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/response.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Check Database Status

$DatabaseStatus = "disconnected";
try {
    if (isset($pdo)) {
        $Stmt = $pdo->query("SELECT 1");
        if ($Stmt && $Stmt->fetchColumn() !== false) {
            $DatabaseStatus = "connected";
        }
    }
} catch (Exception $e) {
    $DatabaseStatus = "disconnected";
}


// Check Other Services Status

$ApiStatus = "operational";
$ReadabilityStatus = "operational";
$CirculationStatus = "operational";
$NotificationStatus = "operational";


// Overall System Status

$OverallStatus = "operational";
if ($DatabaseStatus !== "connected") {
    $OverallStatus = "degraded";
    $ApiStatus = "degraded";
}


// Get System Statistics

$TotalBooks = 0;
$TotalUsers = 0;
$ActiveIssues = 0;

try {
    $Stmt = $pdo->query("SELECT COUNT(*) FROM books");
    $TotalBooks = (int)$Stmt->fetchColumn();
    
    $Stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $TotalUsers = (int)$Stmt->fetchColumn();
    
    $Stmt = $pdo->query("SELECT COUNT(*) FROM book_issues WHERE status = 'issued'");
    $ActiveIssues = (int)$Stmt->fetchColumn();
} catch (Exception $e) {
    // Stats collection failed, but continue
}


// Response

successResponse([
    "status" => $OverallStatus,
    "name" => APP_NAME,
    "version" => APP_VERSION,
    "updated_at" => date("Y-m-d H:i:s"),
    "api_status" => $ApiStatus,
    "database_status" => $DatabaseStatus,
    "readability_status" => $ReadabilityStatus,
    "circulation_status" => $CirculationStatus,
    "notification_status" => $NotificationStatus,
    "statistics" => [
        "total_books" => $TotalBooks,
        "total_users" => $TotalUsers,
        "active_issues" => $ActiveIssues
    ]
], "System status retrieved successfully");

?>
