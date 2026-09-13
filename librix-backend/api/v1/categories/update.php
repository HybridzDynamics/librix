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

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    methodNotAllowedResponse(["PUT"]);
}


// Authentication & Admin Check

$User = requireAuth();
requireAdmin($User);
$UserId = (int)$User["id"];


// Category ID

$CategoryId = $GLOBALS["categoryId"] ?? ($parts[3] ?? null);
$Input = getJsonInput();

if ($CategoryId === null) {
    $CategoryId = $Input["id"] ?? null;
}

$IdError = validatePositiveInteger($CategoryId, "Category ID");
if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$CategoryId = (int)$CategoryId;


// Find Category

try {
    $Stmt = $pdo->prepare("SELECT id, name, description FROM categories WHERE id = ? LIMIT 1");
    $Stmt->execute([$CategoryId]);
    $Existing = $Stmt->fetch();

    if (!$Existing) {
        notFoundResponse("Category not found");
    }
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Fields to update

$Name = isset($Input["name"]) ? sanitizeString($Input["name"]) : $Existing["name"];
$Description = array_key_exists("description", $Input) ? ($Input["description"] ? sanitizeString($Input["description"]) : null) : $Existing["description"];

if (empty($Name)) {
    errorResponse("Category name cannot be empty", 422);
}

if (mb_strlen($Name) > 100) {
    errorResponse("Category name cannot exceed 100 characters", 422);
}


// Check Duplicate Name

try {
    $Check = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1");
    $Check->execute([$Name, $CategoryId]);
    if ($Check->fetch()) {
        errorResponse("Category with this name already exists", 409);
    }

    $UpdateStmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
    $UpdateStmt->execute([$Name, $Description, $CategoryId]);

    logAudit($UserId, "update_category", "categories", $CategoryId, "Updated category '{$Name}'");

    successResponse([
        "id" => $CategoryId,
        "name" => $Name,
        "description" => $Description
    ], "Category updated successfully");

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
