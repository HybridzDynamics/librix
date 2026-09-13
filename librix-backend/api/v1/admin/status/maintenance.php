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


// Create Maintenance Window (POST)

if ($Method === "POST") {

    $RequestData = GetJsonInput();

    $Title = trim($RequestData["title"] ?? "");
    $Description = trim($RequestData["description"] ?? "");
    $Service = trim($RequestData["service"] ?? "all");
    $StartsAt = trim($RequestData["starts_at"] ?? "");
    $EndsAt = trim($RequestData["ends_at"] ?? "");
    $Status = trim($RequestData["status"] ?? "scheduled");

    $Errors = [];

    $TitleError = Required($Title, "Title");
    if ($TitleError !== null) $Errors["title"] = $TitleError;

    $DescError = Required($Description, "Description");
    if ($DescError !== null) $Errors["description"] = $DescError;

    $StartsError = Required($StartsAt, "Starts at");
    if ($StartsError !== null) $Errors["starts_at"] = $StartsError;

    $EndsError = Required($EndsAt, "Ends at");
    if ($EndsError !== null) $Errors["ends_at"] = $EndsError;

    $AllowedStatuses = ["scheduled", "active", "completed", "cancelled"];
    if (!in_array($Status, $AllowedStatuses, true)) {
        $Errors["status"] = "Invalid status. Allowed: " . implode(", ", $AllowedStatuses);
    }

    if (HasValidationErrors($Errors)) {
        ValidationErrorResponse($Errors);
    }

    try {
        $Stmt = $pdo->prepare(
            "INSERT INTO maintenance_windows
            (title, description, service, starts_at, ends_at, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $Stmt->execute([
            $Title,
            $Description,
            $Service,
            $StartsAt,
            $EndsAt,
            $Status,
            (int)$AdminUser["id"]
        ]);

        $WindowId = (int)$pdo->lastInsertId();

        LogAudit(
            (int)$AdminUser["id"],
            "maintenance_window_created",
            "maintenance_windows",
            $WindowId,
            "Scheduled maintenance '$Title' for service $Service from $StartsAt to $EndsAt"
        );

    } catch (PDOException $e) {
        ErrorResponse("Unable to schedule maintenance", 500);
    }

    // Return Created
    try {
        $Stmt = $pdo->prepare("SELECT * FROM maintenance_windows WHERE id = ? LIMIT 1");
        $Stmt->execute([$WindowId]);
        $Created = $Stmt->fetch();
        $Created["id"] = (int)$Created["id"];

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    SuccessResponse($Created, "Maintenance window scheduled successfully", 201);
}


// Update Maintenance Window (PUT/PATCH)

if ($Method === "PUT" || $Method === "PATCH") {

    $WindowId = $parts[5] ?? null;

    if ($WindowId === null) {
        $RequestData = GetJsonInput();
        $WindowId = $RequestData["id"] ?? null;
    }

    $IdError = ValidatePositiveInteger($WindowId, "Maintenance Window ID");
    if ($IdError !== null) {
        ErrorResponse($IdError, 400);
    }

    $WindowId = (int)$WindowId;

    try {
        $Stmt = $pdo->prepare("SELECT * FROM maintenance_windows WHERE id = ? LIMIT 1");
        $Stmt->execute([$WindowId]);
        $Existing = $Stmt->fetch();

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    if (!$Existing) {
        NotFoundResponse("Maintenance window not found");
    }

    $RequestData = GetJsonInput();

    $Title = array_key_exists("title", $RequestData) ? trim($RequestData["title"]) : $Existing["title"];
    $Description = array_key_exists("description", $RequestData) ? trim($RequestData["description"]) : $Existing["description"];
    $Service = array_key_exists("service", $RequestData) ? trim($RequestData["service"]) : $Existing["service"];
    $StartsAt = array_key_exists("starts_at", $RequestData) ? trim($RequestData["starts_at"]) : $Existing["starts_at"];
    $EndsAt = array_key_exists("ends_at", $RequestData) ? trim($RequestData["ends_at"]) : $Existing["ends_at"];
    $Status = array_key_exists("status", $RequestData) ? trim($RequestData["status"]) : $Existing["status"];

    $AllowedStatuses = ["scheduled", "active", "completed", "cancelled"];
    if (!in_array($Status, $AllowedStatuses, true)) {
        ErrorResponse("Invalid status value", 422);
    }

    try {
        $Stmt = $pdo->prepare(
            "UPDATE maintenance_windows
             SET title = ?,
                 description = ?,
                 service = ?,
                 starts_at = ?,
                 ends_at = ?,
                 status = ?
             WHERE id = ?"
        );

        $Stmt->execute([
            $Title,
            $Description,
            $Service,
            $StartsAt,
            $EndsAt,
            $Status,
            $WindowId
        ]);

        LogAudit(
            (int)$AdminUser["id"],
            "maintenance_window_updated",
            "maintenance_windows",
            $WindowId,
            "Updated maintenance window #$WindowId to status $Status"
        );

    } catch (PDOException $e) {
        ErrorResponse("Unable to update maintenance window", 500);
    }

    // Return Updated
    try {
        $Stmt = $pdo->prepare("SELECT * FROM maintenance_windows WHERE id = ? LIMIT 1");
        $Stmt->execute([$WindowId]);
        $Updated = $Stmt->fetch();
        $Updated["id"] = (int)$Updated["id"];

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    SuccessResponse($Updated, "Maintenance window updated successfully");
}


// Delete Maintenance Window (DELETE)

if ($Method === "DELETE") {

    $WindowId = $parts[5] ?? null;

    if ($WindowId === null) {
        $RequestData = GetJsonInput();
        $WindowId = $RequestData["id"] ?? null;
    }

    $IdError = ValidatePositiveInteger($WindowId, "Maintenance Window ID");
    if ($IdError !== null) {
        ErrorResponse($IdError, 400);
    }

    $WindowId = (int)$WindowId;

    try {
        $Stmt = $pdo->prepare("SELECT id, title FROM maintenance_windows WHERE id = ? LIMIT 1");
        $Stmt->execute([$WindowId]);
        $Existing = $Stmt->fetch();

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    if (!$Existing) {
        NotFoundResponse("Maintenance window not found");
    }

    try {
        $Stmt = $pdo->prepare("DELETE FROM maintenance_windows WHERE id = ?");
        $Stmt->execute([$WindowId]);

        LogAudit(
            (int)$AdminUser["id"],
            "maintenance_window_deleted",
            "maintenance_windows",
            $WindowId,
            "Deleted maintenance window #$WindowId ({$Existing['title']})"
        );

    } catch (PDOException $e) {
        ErrorResponse("Unable to delete maintenance window", 500);
    }

    SuccessResponse(["id" => $WindowId], "Maintenance window deleted successfully");
}

MethodNotAllowedResponse(["POST", "PUT", "DELETE"]);

?>
