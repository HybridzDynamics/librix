<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Service Check Helper Function

function CheckServiceHealth($ServiceName, $TestCallable)
{
    global $pdo;

    $StartTime = microtime(true);
    $Status = "operational";

    try {
        $Result = $TestCallable($pdo);

        if (!$Result) {
            $Status = "degraded";
        }
    } catch (Exception $e) {
        $Status = "outage";
    }

    $EndTime = microtime(true);
    $ResponseTimeMs = (int)round(($EndTime - $StartTime) * 1000);

    // Record Check in status_checks
    try {
        if ($pdo) {
            $Stmt = $pdo->prepare(
                "INSERT INTO status_checks (service, status, response_time)
                 VALUES (?, ?, ?)"
            );
            $Stmt->execute([$ServiceName, $Status, $ResponseTimeMs]);
        }
    } catch (Exception $e) {
        // Suppress logging errors to preserve status reporting
    }

    return [
        "name" => $ServiceName,
        "status" => $Status,
        "response_time_ms" => $ResponseTimeMs
    ];
}


// Perform Real Component Checks

$Services = [];

// API Service
$Services[] = [
    "name" => "API",
    "status" => "operational",
    "response_time_ms" => 1
];

// Database Service
$Services[] = CheckServiceHealth("Database", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT 1");
    return ($Stmt && $Stmt->fetchColumn() !== false);
});

// Authentication Service
$Services[] = CheckServiceHealth("Authentication", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    return ($Stmt !== false);
});

// Books Service
$Services[] = CheckServiceHealth("Books", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM books LIMIT 1");
    return ($Stmt !== false);
});

// Authors Service
$Services[] = CheckServiceHealth("Authors", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM authors LIMIT 1");
    return ($Stmt !== false);
});

// Library Service
$Services[] = CheckServiceHealth("Library", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM book_issues LIMIT 1");
    return ($Stmt !== false);
});

// Admin Service
$Services[] = CheckServiceHealth("Admin", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    return ($Stmt !== false);
});

// Search Service
$Services[] = CheckServiceHealth("Search", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM books WHERE title LIKE '%a%' LIMIT 1");
    return ($Stmt !== false);
});


// Response

successResponse($Services);

?>
