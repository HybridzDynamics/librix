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

$Name = trim($RequestData["name"] ?? "");
$Code = trim($RequestData["code"] ?? "");
$Description = trim($RequestData["description"] ?? "");
$ContactEmail = trim($RequestData["contact_email"] ?? "");
$LogoUrl = trim($RequestData["logo_url"] ?? "");


// Validation

$Errors = [];

$NameError = required($Name, "Organization Name");
if ($NameError !== null) {
    $Errors["name"] = $NameError;
} else {
    $LengthError = maxLength($Name, 150, "Organization Name");
    if ($LengthError !== null) {
        $Errors["name"] = $LengthError;
    }
}

$CodeError = required($Code, "Organization Code");
if ($CodeError !== null) {
    $Errors["code"] = $CodeError;
} else {
    $LengthError = maxLength($Code, 50, "Organization Code");
    if ($LengthError !== null) {
        $Errors["code"] = $LengthError;
    }
}

if ($ContactEmail !== "") {
    $EmailError = validateEmail($ContactEmail);
    if ($EmailError !== null) {
        $Errors["contact_email"] = $EmailError;
    }
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}


// Generate unique code if not provided

if ($Code === "") {
    $Code = strtoupper(substr(preg_replace("/[^a-zA-Z0-9]/", "", $Name), 0, 10));
    if ($Code === "") {
        $Code = "ORG" . time();
    }
}


// Create Organization

try {
    $pdo->beginTransaction();
    
    $Stmt = $pdo->prepare(
        "INSERT INTO organizations
        (name, code, description, contact_email, logo_url)
        VALUES (?, ?, ?, ?, ?)"
    );
    
    $Stmt->execute([
        $Name,
        $Code,
        $Description ?: null,
        $ContactEmail ?: null,
        $LogoUrl ?: null
    ]);
    
    $OrgId = (int)$pdo->lastInsertId();
    
    $pdo->commit();
    
    logAudit($User["id"], "organization_created", "organizations", $OrgId, "Created organization: $Name");
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    if (strpos($e->getMessage(), "Duplicate entry") !== false) {
        errorResponse("Organization code already exists", 409);
    }
    
    errorResponse("Unable to create organization", 500);
}


// Fetch Created Organization

try {
    $Stmt = $pdo->prepare(
        "SELECT * FROM organizations WHERE id = ? LIMIT 1"
    );
    
    $Stmt->execute([$OrgId]);
    
    $Organization = $Stmt->fetch();
    
} catch (PDOException $e) {
    errorResponse("Unable to retrieve organization details", 500);
}

successResponse($Organization, "Organization created successfully", 201);

?>