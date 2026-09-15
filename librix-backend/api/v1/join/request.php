<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();


// Request Data

$RequestData = getJsonInput();

$OrgCode = trim($RequestData["org_code"] ?? "");
$Message = trim($RequestData["message"] ?? "");


// Validation

$Errors = [];

$CodeError = required($OrgCode, "Organization Code");
if ($CodeError !== null) {
    $Errors["org_code"] = $CodeError;
} else {
    $LengthError = maxLength($OrgCode, 50, "Organization Code");
    if ($LengthError !== null) {
        $Errors["org_code"] = $LengthError;
    }
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}


// Find Organization by Code

try {
    $Stmt = $pdo->prepare("SELECT id, name FROM organizations WHERE code = ? LIMIT 1");
    $Stmt->execute([$OrgCode]);
    
    $Organization = $Stmt->fetch();
    
    if (!$Organization) {
        errorResponse("Organization not found", 404);
    }
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Check if user already has a pending or approved request for this org

try {
    $Stmt = $pdo->prepare(
        "SELECT id, status FROM org_join_requests 
         WHERE org_id = ? AND user_id = ? 
         AND status IN ('pending', 'approved') 
         LIMIT 1"
    );
    
    $Stmt->execute([$Organization["id"], $User["id"]]);
    
    $ExistingRequest = $Stmt->fetch();
    
    if ($ExistingRequest) {
        if ($ExistingRequest["status"] === "approved") {
            errorResponse("You are already a member of this organization", 400);
        } else {
            errorResponse("You already have a pending request for this organization", 400);
        }
    }
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Create Join Request

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO org_join_requests
        (org_id, user_id, message, status)
        VALUES (?, ?, ?, 'pending')"
    );
    
    $Stmt->execute([
        $Organization["id"],
        $User["id"],
        $Message ?: null
    ]);
    
    $RequestId = (int)$pdo->lastInsertId();
    
    logAudit($User["id"], "org_join_request", "org_join_requests", $RequestId, "Join request for org: {$Organization['name']}");
    
} catch (PDOException $e) {
    errorResponse("Unable to create join request", 500);
}


// Fetch Created Request

try {
    $Stmt = $pdo->prepare(
        "SELECT jr.*, o.name as org_name, o.code as org_code, u.name as user_name
         FROM org_join_requests jr
         JOIN organizations o ON jr.org_id = o.id
         JOIN users u ON jr.user_id = u.id
         WHERE jr.id = ? LIMIT 1"
    );
    
    $Stmt->execute([$RequestId]);
    
    $Request = $Stmt->fetch();
    
} catch (PDOException $e) {
    errorResponse("Unable to retrieve request details", 500);
}

successResponse($Request, "Organization join request submitted", 201);

?>