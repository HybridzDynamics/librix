<?php

// Configuration

require_once __DIR__ . "/../../config/config.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../helpers/response.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Database Health Check

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

if ($DatabaseStatus !== "connected") {
    errorResponse("Database health check failed", 500, [
        "api" => "online",
        "database" => "disconnected"
    ]);
}


// Health Response

successResponse(
    [
        "api" => "online",
        "database" => "connected"
    ],
    "Health check passed"
);

?>
