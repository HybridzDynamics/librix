<?php

// Configuration

require_once __DIR__ . "/../../../../config/config.php";
require_once __DIR__ . "/../../../../config/database.php";
require_once __DIR__ . "/../../../../helpers/response.php";
require_once __DIR__ . "/../../../../helpers/validation.php";
require_once __DIR__ . "/../../../../helpers/functions.php";
require_once __DIR__ . "/../../../../helpers/audit.php";
require_once __DIR__ . "/../../../../middleware/auth.php";
require_once __DIR__ . "/../../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    methodNotAllowedResponse(["DELETE"]);
}


// Authentication

$User = requireAuth();
requireAdmin($User);


// Request Data

// URL structure: /api/v1/admin/users/{id}/delete
// parts[0] = api, parts[1] = v1, parts[2] = admin, parts[3] = users, parts[4] = {id}, parts[5] = delete
$UserId = $parts[4] ?? null;


// Validation

if ($UserId === null) {
    errorResponse("User ID is required", 400);
}

$IdError = validatePositiveInteger($UserId, "User ID");

if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$UserId = (int)$UserId;


// Prevent deleting yourself

if ($UserId === $User["id"]) {
    errorResponse("You cannot delete your own account", 400);
}


// Prevent deleting the main admin account (ID 1)

if ($UserId === 1) {
    errorResponse("Cannot delete the main administrator account", 403);
}


// Check if user exists

try {
    $Stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
    $Stmt->execute([$UserId]);
    $TargetUser = $Stmt->fetch();

    if (!$TargetUser) {
        notFoundResponse("User not found");
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Check if user has active issues or fines

try {
    $Stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM book_issues WHERE user_id = ? AND status = 'issued'"
    );
    $Stmt->execute([$UserId]);
    $ActiveIssues = (int)$Stmt->fetchColumn();

    if ($ActiveIssues > 0) {
        errorResponse("Cannot delete user with active book issues. Please return all books first.", 400);
    }

    $Stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM fines WHERE user_id = ? AND status = 'unpaid'"
    );
    $Stmt->execute([$UserId]);
    $UnpaidFines = (int)$Stmt->fetchColumn();

    if ($UnpaidFines > 0) {
        errorResponse("Cannot delete user with unpaid fines. Please settle all fines first.", 400);
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Delete user (using transaction for data integrity)

try {
    $pdo->beginTransaction();

    // Delete notifications
    $Stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $Stmt->execute([$UserId]);

    // Delete reservations
    $Stmt = $pdo->prepare("DELETE FROM reservations WHERE user_id = ?");
    $Stmt->execute([$UserId]);

    // Delete book issues history
    $Stmt = $pdo->prepare("DELETE FROM book_issues WHERE user_id = ?");
    $Stmt->execute([$UserId]);

    // Delete fines (all should be paid at this point)
    $Stmt = $pdo->prepare("DELETE FROM fines WHERE user_id = ?");
    $Stmt->execute([$UserId]);

    // Delete user
    $Stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $Stmt->execute([$UserId]);

    $pdo->commit();

    // Log the action
    logAuditAction(
        $pdo,
        $User["id"],
        null,
        "delete",
        "user",
        $UserId,
        "Deleted user: {$TargetUser['name']} ({$TargetUser['email']})",
        getClientIp()
    );

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse("Failed to delete user", 500);
}


// Response

successResponse(
    [
        "deleted_user_id" => $UserId,
        "deleted_user_name" => $TargetUser["name"],
        "deleted_user_email" => $TargetUser["email"]
    ],
    "User deleted successfully"
);

?>
