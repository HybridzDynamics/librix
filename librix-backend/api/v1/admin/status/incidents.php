<?php

// Configuration

require_once __DIR__ . "/../../../../config/config.php";
require_once __DIR__ . "/../../../../config/database.php";
require_once __DIR__ . "/../../../../helpers/response.php";
require_once __DIR__ . "/../../../../helpers/validation.php";
require_once __DIR__ . "/../../../../helpers/functions.php";
require_once __DIR__ . "/../../../../middleware/auth.php";
require_once __DIR__ . "/../../../../middleware/admin.php";


// Authentication

$AdminUser = RequireAuth();
RequireAdmin($AdminUser);


// Request Method

$Method = $_SERVER["REQUEST_METHOD"];


// Create Incident (POST)

if ($Method === "POST") {

    $RequestData = GetJsonInput();

    $Title = trim($RequestData["title"] ?? "");
    $Description = trim($RequestData["description"] ?? "");
    $Status = trim($RequestData["status"] ?? "investigating");
    $Severity = trim($RequestData["severity"] ?? "minor");
    $StartedAt = trim($RequestData["started_at"] ?? date("Y-m-d H:i:s"));

    $Errors = [];

    $TitleError = Required($Title, "Title");
    if ($TitleError !== null) $Errors["title"] = $TitleError;

    $DescError = Required($Description, "Description");
    if ($DescError !== null) $Errors["description"] = $DescError;

    $AllowedStatuses = ["investigating", "identified", "monitoring", "resolved"];
    if (!in_array($Status, $AllowedStatuses, true)) {
        $Errors["status"] = "Invalid status. Allowed: " . implode(", ", $AllowedStatuses);
    }

    $AllowedSeverities = ["minor", "major", "critical"];
    if (!in_array($Severity, $AllowedSeverities, true)) {
        $Errors["severity"] = "Invalid severity. Allowed: " . implode(", ", $AllowedSeverities);
    }

    if (HasValidationErrors($Errors)) {
        ValidationErrorResponse($Errors);
    }

    $ResolvedAt = ($Status === "resolved") ? date("Y-m-d H:i:s") : null;

    try {
        $Stmt = $pdo->prepare(
            "INSERT INTO status_incidents
            (title, description, status, severity, started_at, resolved_at)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $Stmt->execute([
            $Title,
            $Description,
            $Status,
            $Severity,
            $StartedAt,
            $ResolvedAt
        ]);

        $IncidentId = (int)$pdo->lastInsertId();

        LogAudit(
            (int)$AdminUser["id"],
            "status_incident_created",
            "status_incidents",
            $IncidentId,
            "Created incident '$Title' with severity $Severity and status $Status"
        );

    } catch (PDOException $e) {
        ErrorResponse("Unable to create incident", 500);
    }

    // Return Created
    try {
        $Stmt = $pdo->prepare("SELECT * FROM status_incidents WHERE id = ? LIMIT 1");
        $Stmt->execute([$IncidentId]);
        $Created = $Stmt->fetch();
        $Created["id"] = (int)$Created["id"];

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    SuccessResponse($Created, "Incident created successfully", 201);
}


// Update Incident (PUT/PATCH)

if ($Method === "PUT" || $Method === "PATCH") {

    $IncidentId = $parts[5] ?? null;

    if ($IncidentId === null) {
        $RequestData = GetJsonInput();
        $IncidentId = $RequestData["id"] ?? null;
    }

    $IdError = ValidatePositiveInteger($IncidentId, "Incident ID");
    if ($IdError !== null) {
        ErrorResponse($IdError, 400);
    }

    $IncidentId = (int)$IncidentId;

    // Verify Incident Exists
    try {
        $Stmt = $pdo->prepare("SELECT * FROM status_incidents WHERE id = ? LIMIT 1");
        $Stmt->execute([$IncidentId]);
        $Existing = $Stmt->fetch();

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    if (!$Existing) {
        NotFoundResponse("Incident not found");
    }

    $RequestData = GetJsonInput();

    $Title = array_key_exists("title", $RequestData) ? trim($RequestData["title"]) : $Existing["title"];
    $Description = array_key_exists("description", $RequestData) ? trim($RequestData["description"]) : $Existing["description"];
    $Status = array_key_exists("status", $RequestData) ? trim($RequestData["status"]) : $Existing["status"];
    $Severity = array_key_exists("severity", $RequestData) ? trim($RequestData["severity"]) : $Existing["severity"];

    $AllowedStatuses = ["investigating", "identified", "monitoring", "resolved"];
    if (!in_array($Status, $AllowedStatuses, true)) {
        ErrorResponse("Invalid status value", 422);
    }

    $AllowedSeverities = ["minor", "major", "critical"];
    if (!in_array($Severity, $AllowedSeverities, true)) {
        ErrorResponse("Invalid severity value", 422);
    }

    $ResolvedAt = $Existing["resolved_at"];
    if ($Status === "resolved" && $Existing["status"] !== "resolved") {
        $ResolvedAt = date("Y-m-d H:i:s");
    } elseif ($Status !== "resolved") {
        $ResolvedAt = null;
    }

    try {
        $Stmt = $pdo->prepare(
            "UPDATE status_incidents
             SET title = ?,
                 description = ?,
                 status = ?,
                 severity = ?,
                 resolved_at = ?
             WHERE id = ?"
        );

        $Stmt->execute([
            $Title,
            $Description,
            $Status,
            $Severity,
            $ResolvedAt,
            $IncidentId
        ]);

        LogAudit(
            (int)$AdminUser["id"],
            "status_incident_updated",
            "status_incidents",
            $IncidentId,
            "Updated incident #$IncidentId to status $Status"
        );

    } catch (PDOException $e) {
        ErrorResponse("Unable to update incident", 500);
    }

    // Return Updated
    try {
        $Stmt = $pdo->prepare("SELECT * FROM status_incidents WHERE id = ? LIMIT 1");
        $Stmt->execute([$IncidentId]);
        $Updated = $Stmt->fetch();
        $Updated["id"] = (int)$Updated["id"];

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    SuccessResponse($Updated, "Incident updated successfully");
}


// Delete Incident (DELETE)

if ($Method === "DELETE") {

    $IncidentId = $parts[5] ?? null;

    if ($IncidentId === null) {
        $RequestData = GetJsonInput();
        $IncidentId = $RequestData["id"] ?? null;
    }

    $IdError = ValidatePositiveInteger($IncidentId, "Incident ID");
    if ($IdError !== null) {
        ErrorResponse($IdError, 400);
    }

    $IncidentId = (int)$IncidentId;

    try {
        $Stmt = $pdo->prepare("SELECT id, title FROM status_incidents WHERE id = ? LIMIT 1");
        $Stmt->execute([$IncidentId]);
        $Existing = $Stmt->fetch();

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    if (!$Existing) {
        NotFoundResponse("Incident not found");
    }

    try {
        $Stmt = $pdo->prepare("DELETE FROM status_incidents WHERE id = ?");
        $Stmt->execute([$IncidentId]);

        LogAudit(
            (int)$AdminUser["id"],
            "status_incident_deleted",
            "status_incidents",
            $IncidentId,
            "Deleted incident #$IncidentId ({$Existing['title']})"
        );

    } catch (PDOException $e) {
        ErrorResponse("Unable to delete incident", 500);
    }

    SuccessResponse(["id" => $IncidentId], "Incident deleted successfully");
}

MethodNotAllowedResponse(["POST", "PUT", "DELETE"]);

?>
