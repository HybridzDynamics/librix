<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Database Query - Overall Uptime

$OverallUptime = 100.00;
$TotalChecks = 0;
$OperationalChecks = 0;

try {
    $Stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'operational' THEN 1 ELSE 0 END) AS operational_count,
            AVG(response_time) AS avg_response_time
         FROM status_checks"
    );

    $Stmt->execute();

    $OverallData = $Stmt->fetch();

    $TotalChecks = (int)($OverallData["total"] ?? 0);
    $OperationalChecks = (int)($OverallData["operational_count"] ?? 0);
    $AvgResponseTime = round((float)($OverallData["avg_response_time"] ?? 0), 2);

    if ($TotalChecks > 0) {
        $OverallUptime = round(($OperationalChecks / $TotalChecks) * 100, 2);
    }

} catch (PDOException $e) {
    ErrorResponse("Unable to calculate uptime", 500);
}


// Database Query - By Service

$ServiceUptimes = [];

try {
    $Stmt = $pdo->prepare(
        "SELECT
            service,
            COUNT(*) AS total_checks,
            SUM(CASE WHEN status = 'operational' THEN 1 ELSE 0 END) AS operational_checks,
            ROUND(AVG(response_time), 2) AS avg_response_time_ms
         FROM status_checks
         GROUP BY service
         ORDER BY service ASC"
    );

    $Stmt->execute();

    $Rows = $Stmt->fetchAll();

    foreach ($Rows as $Row) {
        $ServiceTotal = (int)$Row["total_checks"];
        $ServiceOperational = (int)$Row["operational_checks"];
        $Percentage = ($ServiceTotal > 0) ? round(($ServiceOperational / $ServiceTotal) * 100, 2) : 100.00;

        $ServiceUptimes[] = [
            "service" => $Row["service"],
            "uptime_percentage" => $Percentage,
            "total_checks" => $ServiceTotal,
            "avg_response_time_ms" => (float)$Row["avg_response_time_ms"]
        ];
    }

} catch (PDOException $e) {
    ErrorResponse("Unable to calculate uptime", 500);
}


// Response

SuccessResponse([
    "uptime_percentage" => $OverallUptime,
    "total_checks" => $TotalChecks,
    "avg_response_time_ms" => $AvgResponseTime ?? 0.0,
    "services" => $ServiceUptimes
]);

?>
