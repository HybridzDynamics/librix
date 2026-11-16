<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$RequestData = getJsonInput();

$Token = trim($RequestData["token"] ?? "");
$Password = $RequestData["password"] ?? "";

$Errors = [];

$TokenError = required($Token, "Reset token");

if ($TokenError !== null) {
    $Errors["token"] = $TokenError;
}

$PasswordError = required($Password, "Password");

if ($PasswordError !== null) {
    $Errors["password"] = $PasswordError;
} else {
    $PasswordError = minLength($Password, 8, "Password");

    if ($PasswordError !== null) {
        $Errors["password"] = $PasswordError;
    }
}

if (hasValidationErrors($Errors)) {
    ValidationerrorResponse($Errors);
}

try {
    $Stmt = $pdo->prepare(
        "SELECT id, user_id, expires_at, used_at
         FROM password_reset_tokens
         WHERE token = ?
         LIMIT 1"
    );

    $Stmt->execute([$Token]);

    $ResetRecord = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to process password reset", 500);
}

if (!$ResetRecord) {
    errorResponse("Invalid or expired reset token", 400);
}

if ($ResetRecord["used_at"] !== null) {
    errorResponse("Reset token has already been used", 400);
}

if (strtotime($ResetRecord["expires_at"]) <= time()) {
    errorResponse("Reset token has expired", 400);
}

$UserId = (int)$ResetRecord["user_id"];
$TokenRecordId = (int)$ResetRecord["id"];

try {
    $Stmt = $pdo->prepare("SELECT id, status FROM users WHERE id = ? LIMIT 1");
    $Stmt->execute([$UserId]);
    $User = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to process password reset", 500);
}

if (!$User || $User["status"] !== "active") {
    errorResponse("Unable to reset password for this account", 400);
}

$HashedPassword = password_hash($Password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $Stmt = $pdo->prepare(
        "UPDATE users
         SET password = ?
         WHERE id = ?"
    );

    $Stmt->execute([
        $HashedPassword,
        $UserId
    ]);

    $Stmt = $pdo->prepare(
        "UPDATE password_reset_tokens
         SET used_at = CURRENT_TIMESTAMP
         WHERE id = ?"
    );

    $Stmt->execute([$TokenRecordId]);

    $Stmt = $pdo->prepare(
        "DELETE FROM password_reset_tokens
         WHERE user_id = ? AND id != ?"
    );

    $Stmt->execute([$UserId, $TokenRecordId]);

    $Stmt = $pdo->prepare(
        "DELETE FROM auth_tokens
         WHERE user_id = ?"
    );

    $Stmt->execute([$UserId]);

    $pdo->commit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse("Unable to reset password", 500);
}

successResponse(
    [],
    "Password reset successful"
);

?>
