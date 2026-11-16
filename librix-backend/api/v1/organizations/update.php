<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    methodNotAllowedResponse(["PUT"]);
}

$User = requireAuth();
requireAdmin($User);

$OrgId = $_GET["id"] ?? null;

$IdError = validatePositiveInteger($OrgId, "Organization ID");

if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$RequestData = getJsonInput();

$Name = trim($RequestData["name"] ?? "");
$Description = trim($RequestData["description"] ?? "");
$ContactEmail = trim($RequestData["contact_email"] ?? "");
$LogoUrl = trim($RequestData["logo_url"] ?? "");

$Errors = [];

if ($Name !== "") {
    $LengthError = maxLength($Name, 150, "Organization Name");
    if ($LengthError !== null) {
        $Errors["name"] = $LengthError;
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

try {
    $Stmt = $pdo->prepare("SELECT id FROM organizations WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$OrgId]);

    if (!$Stmt->fetch()) {
        notFoundResponse("Organization not found");
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

$UpdateFields = [];
$UpdateValues = [];

if ($Name !== "") {
    $UpdateFields[] = "name = ?";
    $UpdateValues[] = $Name;
}

if ($Description !== "") {
    $UpdateFields[] = "description = ?";
    $UpdateValues[] = $Description;
}

if ($ContactEmail !== "") {
    $UpdateFields[] = "contact_email = ?";
    $UpdateValues[] = $ContactEmail;
}

if ($LogoUrl !== "") {
    $UpdateFields[] = "logo_url = ?";
    $UpdateValues[] = $LogoUrl;
}

if (empty($UpdateFields)) {
    errorResponse("No fields to update", 400);
}

$UpdateValues[] = (int)$OrgId;

try {
    $Sql = "UPDATE organizations SET " . implode(", ", $UpdateFields) . " WHERE id = ?";
    $Stmt = $pdo->prepare($Sql);
    $Stmt->execute($UpdateValues);

    logAudit($User["id"], "organization_updated", "organizations", $OrgId, "Updated organization ID: $OrgId");

} catch (PDOException $e) {
    errorResponse("Unable to update organization", 500);
}

try {
    $Stmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$OrgId]);

    $Organization = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to retrieve organization details", 500);
}

successResponse($Organization, "Organization updated successfully");

?>
