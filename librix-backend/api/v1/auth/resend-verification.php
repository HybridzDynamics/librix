<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Rate Limiting

$ClientIp = getClientIp();
$RateLimitKey = "resend_verify_" . $ClientIp;
$RetryAfter = checkRateLimit($RateLimitKey, RATE_LIMIT_AUTH_MAX, RATE_LIMIT_WINDOW_SECONDS);

if ($RetryAfter > 0) {
    tooManyRequestsResponse("Too many verification requests. Please try again later.", $RetryAfter);
}


// Request Data

$RequestData = getJsonInput();

$UserEmail = trim($RequestData["email"] ?? "");


// Validation

$Errors = [];

$EmailError = required($UserEmail, "Email");

if ($EmailError !== null) {
    $Errors["email"] = $EmailError;
} else {
    $EmailError = validateEmail($UserEmail);

    if ($EmailError !== null) {
        $Errors["email"] = $EmailError;
    }
}

if (hasValidationErrors($Errors)) {
    ValidationerrorResponse($Errors);
}


// Generic Message

$GenericMessage = "If an unverified account exists for this email, a new verification link has been sent.";


// Check User

try {
    $Stmt = $pdo->prepare(
        "SELECT id, status, email_verified_at
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $Stmt->execute([$UserEmail]);

    $User = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to process request", 500);
}


// Issue Token if User Exists, Not Suspended, and Unverified

if ($User && $User["status"] !== "suspended" && $User["email_verified_at"] === null) {

    $VerificationToken = generateToken(32);

    $ExpiresAt = date(
        "Y-m-d H:i:s",
        strtotime("+24 hours")
    );

    $UserId = (int)$User["id"];

    try {
        $Stmt = $pdo->prepare(
            "DELETE FROM email_verification_tokens
             WHERE user_id = ? AND used_at IS NULL"
        );

        $Stmt->execute([$UserId]);

        $Stmt = $pdo->prepare(
            "INSERT INTO email_verification_tokens
            (user_id, token, expires_at)
            VALUES (?, ?, ?)"
        );

        $Stmt->execute([
            $UserId,
            $VerificationToken,
            $ExpiresAt
        ]);

    } catch (PDOException $e) {
        errorResponse("Unable to process request", 500);
    }
}


// Response

successResponse(
    [],
    $GenericMessage
);

?>
