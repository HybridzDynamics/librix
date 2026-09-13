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


// Check Database Health

$IsDbConnected = false;

try {
    if (isset($pdo)) {
        $Stmt = $pdo->query("SELECT 1");
        if ($Stmt && $Stmt->fetchColumn() !== false) {
            $IsDbConnected = true;
        }
    }
} catch (Exception $e) {
    $IsDbConnected = false;
}

if (!$IsDbConnected) {
    successResponse([
        "status" => "major_outage",
        "name" => APP_NAME,
        "version" => APP_VERSION,
        "updated_at" => currentTime()
    ]);
}


// Check Active Maintenance

$HasActiveMaintenance = false;

try {
    $Stmt = $pdo->prepare(
        "SELECT id FROM maintenance_windows
         WHERE status = 'active'
            OR (status = 'scheduled' AND starts_at <= NOW() AND ends_at >= NOW())
         LIMIT 1"
    );

    $Stmt->execute();

    if ($Stmt->fetch()) {
        $HasActiveMaintenance = true;
    }

} catch (PDOException $e) {
    // Graceful fallback
}

if ($HasActiveMaintenance) {
    successResponse([
        "status" => "maintenance",
        "name" => APP_NAME,
        "version" => APP_VERSION,
        "updated_at" => currentTime()
    ]);
}


// Check Unresolved Incidents

$HighestIncidentSeverity = null;

try {
    $Stmt = $pdo->prepare(
        "SELECT severity FROM status_incidents
         WHERE status IN ('investigating', 'identified', 'monitoring')
         ORDER BY CASE
            WHEN severity = 'critical' THEN 1
            WHEN severity = 'major' THEN 2
            WHEN severity = 'minor' THEN 3
            ELSE 4
         END ASC
         LIMIT 1"
    );

    $Stmt->execute();

    $ActiveIncident = $Stmt->fetch();

    if ($ActiveIncident) {
        $HighestIncidentSeverity = $ActiveIncident["severity"];
    }

} catch (PDOException $e) {
    // Graceful fallback
}


// Calculate Overall Status

$OverallStatus = "operational";

if ($HighestIncidentSeverity === "critical") {
    $OverallStatus = "major_outage";
} elseif ($HighestIncidentSeverity === "major") {
    $OverallStatus = "partial_outage";
} elseif ($HighestIncidentSeverity === "minor") {
    $OverallStatus = "degraded";
}


// Response

successResponse([
    "status" => $OverallStatus,
    "name" => APP_NAME,
    "version" => APP_VERSION,
    "updated_at" => currentTime()
]);

?>
