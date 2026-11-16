<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$User = requireAuth();
requireAdmin($User);
$UserId = (int)$User["id"];

$Input = getJsonInput();
$Name = isset($Input["name"]) ? sanitizeString($Input["name"]) : null;
$Description = isset($Input["description"]) ? sanitizeString($Input["description"]) : null;

$Errors = [];

if (empty($Name)) {
    $Errors["name"] = "Category name is required";
} elseif (mb_strlen($Name) > 100) {
    $Errors["name"] = "Category name cannot exceed 100 characters";
}

if (!empty($Errors)) {
    errorResponse("Validation failed", 422, $Errors);
}

try {
    $Check = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $Check->execute([$Name]);
    if ($Check->fetch()) {
        errorResponse("Category with this name already exists", 409);
    }

    $Stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $Stmt->execute([$Name, $Description]);
    $NewId = (int)$pdo->lastInsertId();

    logAudit($UserId, "create_category", "categories", $NewId, "Created category '{$Name}'");

    successResponse([
        "id" => $NewId,
        "name" => $Name,
        "description" => $Description
    ], "Category created successfully", 201);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
