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

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    methodNotAllowedResponse(["DELETE"]);
}


// Authentication & Admin Check

$User = requireAuth();
requireAdmin($User);
$UserId = (int)$User["id"];


// Category ID

$CategoryId = $GLOBALS["categoryId"] ?? ($parts[3] ?? ($_GET["id"] ?? null));
if ($CategoryId === null) {
    $Input = getJsonInput();
    $CategoryId = $Input["id"] ?? null;
}

$IdError = validatePositiveInteger($CategoryId, "Category ID");
if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$CategoryId = (int)$CategoryId;


// Find Category

try {
    $Stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = ? LIMIT 1");
    $Stmt->execute([$CategoryId]);
    $Category = $Stmt->fetch();

    if (!$Category) {
        notFoundResponse("Category not found");
    }

    // Set books with this category_id to NULL
    $BookUpdate = $pdo->prepare("UPDATE books SET category_id = NULL WHERE category_id = ?");
    $BookUpdate->execute([$CategoryId]);

    // Delete Category
    $DelStmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $DelStmt->execute([$CategoryId]);

    logAudit($UserId, "delete_category", "categories", $CategoryId, "Deleted category '{$Category['name']}'");

    successResponse(null, "Category deleted successfully");

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
