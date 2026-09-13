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

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    MethodNotAllowedResponse(["PUT"]);
}


// Authentication & Admin Check

$User = RequireAuth();
RequireAdmin($User);
$UserId = (int)$User["id"];


// Category ID

$CategoryId = $categoryId ?? ($parts[3] ?? null);
$Input = GetJsonInput();

if ($CategoryId === null) {
    $CategoryId = $Input["id"] ?? null;
}

$IdError = ValidatePositiveInteger($CategoryId, "Category ID");
if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$CategoryId = (int)$CategoryId;


// Find Category

try {
    $Stmt = $pdo->prepare("SELECT id, name, description FROM categories WHERE id = ? LIMIT 1");
    $Stmt->execute([$CategoryId]);
    $Existing = $Stmt->fetch();

    if (!$Existing) {
        NotFoundResponse("Category not found");
    }
} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Fields to update

$Name = isset($Input["name"]) ? SanitizeString($Input["name"]) : $Existing["name"];
$Description = array_key_exists("description", $Input) ? ($Input["description"] ? SanitizeString($Input["description"]) : null) : $Existing["description"];

if (empty($Name)) {
    ErrorResponse("Category name cannot be empty", 422);
}

if (mb_strlen($Name) > 100) {
    ErrorResponse("Category name cannot exceed 100 characters", 422);
}


// Check Duplicate Name

try {
    $Check = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1");
    $Check->execute([$Name, $CategoryId]);
    if ($Check->fetch()) {
        ErrorResponse("Category with this name already exists", 409);
    }

    $UpdateStmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
    $UpdateStmt->execute([$Name, $Description, $CategoryId]);

    LogAudit($UserId, "update_category", "categories", $CategoryId, "Updated category '{$Name}'");

    SuccessResponse([
        "id" => $CategoryId,
        "name" => $Name,
        "description" => $Description
    ], "Category updated successfully");

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
