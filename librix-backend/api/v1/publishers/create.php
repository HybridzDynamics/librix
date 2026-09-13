<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";
require __DIR__ . "/../../../middleware/auth.php";
require __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication & Admin Check

$User = requireAuth();
requireAdmin($User);
$UserId = (int)$User["id"];


// Request Data

$Input = getJsonInput();
$Name = isset($Input["name"]) ? sanitizeString($Input["name"]) : null;
$Address = isset($Input["address"]) ? sanitizeString($Input["address"]) : null;
$Website = isset($Input["website"]) ? sanitizeString($Input["website"]) : null;


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
    errorResponse("Validation failed", 422, $Errors);
}


// Create Publisher

try {
    $Stmt = $pdo->prepare("INSERT INTO publishers (name, address, website) VALUES (?, ?, ?)");
    $Stmt->execute([$Name, $Address, $Website]);
    $NewId = (int)$pdo->lastInsertId();

    logAudit($UserId, "create_publisher", "publishers", $NewId, "Created publisher '{$Name}'");

    successResponse([
        "id" => $NewId,
        "name" => $Name,
        "address" => $Address,
        "website" => $Website
    ], "Publisher created successfully", 201);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
