<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}

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

    try {
        if ($pdo) {
            $Stmt = $pdo->prepare(
                "INSERT INTO status_checks (service, status, response_time)
                 VALUES (?, ?, ?)"
            );
            $Stmt->execute([$ServiceName, $Status, $ResponseTimeMs]);
        }
    } catch (Exception $e) {

    }

    return [
        "name" => $ServiceName,
        "status" => $Status,
        "response_time_ms" => $ResponseTimeMs
    ];
}

$Services = [];

$Services[] = [
    "name" => "API",
    "status" => "operational",
    "response_time_ms" => 1
];

$Services[] = CheckServiceHealth("Database", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT 1");
    return ($Stmt && $Stmt->fetchColumn() !== false);
});

$Services[] = CheckServiceHealth("Authentication", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    return ($Stmt !== false);
});

$Services[] = CheckServiceHealth("Books", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM books LIMIT 1");
    return ($Stmt !== false);
});

$Services[] = CheckServiceHealth("Authors", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM authors LIMIT 1");
    return ($Stmt !== false);
});

$Services[] = CheckServiceHealth("Library", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM book_issues LIMIT 1");
    return ($Stmt !== false);
});

$Services[] = CheckServiceHealth("Admin", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    return ($Stmt !== false);
});

$Services[] = CheckServiceHealth("Search", function ($pdo) {
    if (!$pdo) return false;
    $Stmt = $pdo->query("SELECT id FROM books WHERE title LIKE '%a%' LIMIT 1");
    return ($Stmt !== false);
});

successResponse($Services);

?>
