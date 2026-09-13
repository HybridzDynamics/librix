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
    MethodNotAllowedResponse(["POST"]);
}


// Authentication & Admin Check

$User = RequireAuth();
RequireAdmin($User);
$UserId = (int)$User["id"];


// Request Data

$Input = GetJsonInput();
$Name = isset($Input["name"]) ? SanitizeString($Input["name"]) : null;
$Address = isset($Input["address"]) ? SanitizeString($Input["address"]) : null;
$Website = isset($Input["website"]) ? SanitizeString($Input["website"]) : null;


// Validation

$Errors = [];

if (empty($Name)) {
    $Errors["name"] = "Publisher name is required";
} elseif (mb_strlen($Name) > 200) {
    $Errors["name"] = "Publisher name cannot exceed 200 characters";
}

if (!empty($Website) && !filter_var($Website, FILTER_VALIDATE_URL)) {
    $Errors["website"] = "Invalid website URL";
}

if (!empty($Errors)) {
    ErrorResponse("Validation failed", 422, $Errors);
}


// Create Publisher

try {
    $Stmt = $pdo->prepare("INSERT INTO publishers (name, address, website) VALUES (?, ?, ?)");
    $Stmt->execute([$Name, $Address, $Website]);
    $NewId = (int)$pdo->lastInsertId();

    LogAudit($UserId, "create_publisher", "publishers", $NewId, "Created publisher '{$Name}'");

    SuccessResponse([
        "id" => $NewId,
        "name" => $Name,
        "address" => $Address,
        "website" => $Website
    ], "Publisher created successfully", 201);

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
