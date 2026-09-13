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
$Description = isset($Input["description"]) ? SanitizeString($Input["description"]) : null;


// Validation

$Errors = [];

if (empty($Name)) {
    $Errors["name"] = "Category name is required";
} elseif (mb_strlen($Name) > 100) {
    $Errors["name"] = "Category name cannot exceed 100 characters";
}

if (!empty($Errors)) {
    ErrorResponse("Validation failed", 422, $Errors);
}


// Check Duplicate Name

try {
    $Check = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $Check->execute([$Name]);
    if ($Check->fetch()) {
        ErrorResponse("Category with this name already exists", 409);
    }

    $Stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $Stmt->execute([$Name, $Description]);
    $NewId = (int)$pdo->lastInsertId();

    LogAudit($UserId, "create_category", "categories", $NewId, "Created category '{$Name}'");

    SuccessResponse([
        "id" => $NewId,
        "name" => $Name,
        "description" => $Description
    ], "Category created successfully", 201);

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
