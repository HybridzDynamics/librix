<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../helpers/email.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();
requireAdmin($User);


// Request Data

$RequestData = getJsonInput();

$RequestId = $RequestData["request_id"] ?? null;
$Action = $RequestData["action"] ?? ""; // "approve" or "reject"


// Validation

$Errors = [];

$IdError = validatePositiveInteger($RequestId, "Request ID");
if ($IdError !== null) {
    $Errors["request_id"] = $IdError;
}

if ($Action !== "approve" && $Action !== "reject") {
    $Errors["action"] = "Action must be 'approve' or 'reject'";
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}


// Check Request Exists

try {
    $Stmt = $pdo->prepare("SELECT * FROM org_join_requests WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$RequestId]);
    
    $Request = $Stmt->fetch();
    
    if (!$Request) {
        notFoundResponse("Join request not found");
    }
    
    if ($Request["status"] !== "pending") {
        errorResponse("Request has already been processed", 400);
    }
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Process Request

try {
    $pdo->beginTransaction();
    
    if ($Action === "approve") {
        // Update user to assign to org
        $Stmt = $pdo->prepare(
            "UPDATE users SET org_id = ?, status = 'active' WHERE id = ?"
        );
        $Stmt->execute([$Request["org_id"], $Request["user_id"]]);
        
        // Update request status
        $Stmt = $pdo->prepare(
            "UPDATE org_join_requests SET status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $Stmt->execute([(int)$RequestId]);
        
        logAudit($User["id"], "org_join_approved", "org_join_requests", $RequestId, "Approved join request ID: $RequestId");
        
        // Send email notification
        $OrgStmt = $pdo->prepare("SELECT name FROM organizations WHERE id = ? LIMIT 1");
        $OrgStmt->execute([$Request["org_id"]]);
        $Org = $OrgStmt->fetch();
        $OrgName = $Org ? $Org["name"] : "Library";
        
        sendOrgJoinApprovalNotification($Request["user_id"], $OrgName, "approved");
        
    } else {
        // Reject request
        $Stmt = $pdo->prepare(
            "UPDATE org_join_requests SET status = 'rejected', updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $Stmt->execute([(int)$RequestId]);
        
        logAudit($User["id"], "org_join_rejected", "org_join_requests", $RequestId, "Rejected join request ID: $RequestId");
        
        // Send email notification
        $OrgStmt = $pdo->prepare("SELECT name FROM organizations WHERE id = ? LIMIT 1");
        $OrgStmt->execute([$Request["org_id"]]);
        $Org = $OrgStmt->fetch();
        $OrgName = $Org ? $Org["name"] : "Library";
        
        sendOrgJoinApprovalNotification($Request["user_id"], $OrgName, "rejected");
    }
    
    $pdo->commit();
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    errorResponse("Unable to process join request", 500);
}


// Fetch Updated Request

try {
    $Stmt = $pdo->prepare("SELECT * FROM org_join_requests WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$RequestId]);
    
    $UpdatedRequest = $Stmt->fetch();
    
} catch (PDOException $e) {
    errorResponse("Unable to retrieve updated request", 500);
}

successResponse($UpdatedRequest, "Join request processed successfully");

?>