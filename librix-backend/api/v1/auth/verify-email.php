<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    MethodNotAllowedResponse(["POST"]);
}


// Request Data

$RequestData = GetJsonInput();

$Token = trim($RequestData["token"] ?? "");


// Validation

$TokenError = Required($Token, "Verification token");

if ($TokenError !== null) {
    ValidationErrorResponse(["token" => $TokenError]);
}


// Find Verification Token

try {
    $Stmt = $pdo->prepare(
        "SELECT id, user_id, expires_at, used_at
         FROM email_verification_tokens
         WHERE token = ?
         LIMIT 1"
    );

    $Stmt->execute([$Token]);

    $TokenRecord = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Unable to verify email", 500);
}

if (!$TokenRecord) {
    ErrorResponse("Invalid or expired verification token", 400);
}

if ($TokenRecord["used_at"] !== null) {
    ErrorResponse("Verification token has already been used", 400);
}

if (strtotime($TokenRecord["expires_at"]) <= time()) {
    ErrorResponse("Verification token has expired", 400);
}

$UserId = (int)$TokenRecord["user_id"];
$TokenId = (int)$TokenRecord["id"];


// Verification Transaction

try {
    $pdo->beginTransaction();

    // Mark Token Used
    $Stmt = $pdo->prepare(
        "UPDATE email_verification_tokens
         SET used_at = CURRENT_TIMESTAMP
         WHERE id = ?"
    );

    $Stmt->execute([$TokenId]);

    // Delete other tokens for this user
    $Stmt = $pdo->prepare(
        "DELETE FROM email_verification_tokens
         WHERE user_id = ? AND id != ?"
    );

    $Stmt->execute([$UserId, $TokenId]);

    // Activate and Mark Verified
    $Stmt = $pdo->prepare(
        "UPDATE users
         SET email_verified_at = CURRENT_TIMESTAMP,
             status = CASE WHEN status = 'inactive' THEN 'active' ELSE status END
         WHERE id = ?"
    );

    $Stmt->execute([$UserId]);

    $pdo->commit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ErrorResponse("Unable to verify email", 500);
}


// Response

SuccessResponse(
    [],
    "Email verified successfully"
);

?>
