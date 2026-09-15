<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
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
$OrgId = $RequestData["org_id"] ?? null; // Optional: assign to existing org
$AdminNotes = trim($RequestData["admin_notes"] ?? "");


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
    $Stmt = $pdo->prepare("SELECT * FROM librarian_requests WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$RequestId]);
    
    $Request = $Stmt->fetch();
    
    if (!$Request) {
        notFoundResponse("Librarian request not found");
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
        // Check if org_id provided, otherwise create new org
        $FinalOrgId = $OrgId;
        
        if ($FinalOrgId === null) {
            // Create new organization
            $OrgCode = strtoupper(substr(preg_replace("/[^a-zA-Z0-9]/", "", $Request["library_name"]), 0, 10));
            if ($OrgCode === "") {
                $OrgCode = "LIB" . time();
            }
            
            $Stmt = $pdo->prepare(
                "INSERT INTO organizations (name, code, description, contact_email)
                VALUES (?, ?, ?, ?)"
            );
            
            $Stmt->execute([
                $Request["library_name"],
                $OrgCode,
                "Library managed by " . $Request["name"],
                $Request["email"]
            ]);
            
            $FinalOrgId = (int)$pdo->lastInsertId();
        }
        
        // Update user to librarian role and assign to org
        $Stmt = $pdo->prepare(
            "UPDATE users SET role = 'librarian', org_id = ?, status = 'active' WHERE id = ?"
        );
        $Stmt->execute([$FinalOrgId, $Request["user_id"]]);
        
        // Update request status
        $Stmt = $pdo->prepare(
            "UPDATE librarian_requests SET status = 'approved', org_id = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $Stmt->execute([$FinalOrgId, $AdminNotes ?: null, (int)$RequestId]);
        
        logAudit($User["id"], "librarian_approved", "librarian_requests", $RequestId, "Approved librarian request ID: $RequestId");
        
    } else {
        // Reject request
        $Stmt = $pdo->prepare(
            "UPDATE librarian_requests SET status = 'rejected', admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $Stmt->execute([$AdminNotes ?: null, (int)$RequestId]);
        
        logAudit($User["id"], "librarian_rejected", "librarian_requests", $RequestId, "Rejected librarian request ID: $RequestId");
    }
    
    $pdo->commit();
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    errorResponse("Unable to process librarian request", 500);
}


// Fetch Updated Request

try {
    $Stmt = $pdo->prepare("SELECT * FROM librarian_requests WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$RequestId]);
    
    $UpdatedRequest = $Stmt->fetch();
    
} catch (PDOException $e) {
    errorResponse("Unable to retrieve updated request", 500);
}

successResponse($UpdatedRequest, "Librarian request processed successfully");

?>