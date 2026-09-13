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

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    MethodNotAllowedResponse(["DELETE"]);
}


// Authentication & Admin Check

$User = RequireAuth();
RequireAdmin($User);
$UserId = (int)$User["id"];


// Category ID

$CategoryId = $categoryId ?? ($parts[3] ?? ($_GET["id"] ?? null));
if ($CategoryId === null) {
    $Input = GetJsonInput();
    $CategoryId = $Input["id"] ?? null;
}

$IdError = ValidatePositiveInteger($CategoryId, "Category ID");
if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$CategoryId = (int)$CategoryId;


// Find Category

try {
    $Stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = ? LIMIT 1");
    $Stmt->execute([$CategoryId]);
    $Category = $Stmt->fetch();

    if (!$Category) {
        NotFoundResponse("Category not found");
    }

    // Set books with this category_id to NULL
    $BookUpdate = $pdo->prepare("UPDATE books SET category_id = NULL WHERE category_id = ?");
    $BookUpdate->execute([$CategoryId]);

    // Delete Category
    $DelStmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $DelStmt->execute([$CategoryId]);

    LogAudit($UserId, "delete_category", "categories", $CategoryId, "Deleted category '{$Category['name']}'");

    SuccessResponse(null, "Category deleted successfully");

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
