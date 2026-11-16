<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$ClientIp = getClientIp();
$RateLimitKey = "forgot_password_" . $ClientIp;
$RetryAfter = checkRateLimit($RateLimitKey, RATE_LIMIT_AUTH_MAX, RATE_LIMIT_WINDOW_SECONDS);

if ($RetryAfter > 0) {
    tooManyRequestsResponse("Too many password reset requests. Please try again later.", $RetryAfter);
}

$RequestData = getJsonInput();

$UserEmail = trim($RequestData["email"] ?? "");

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

$GenericMessage = "If an account exists for this email, a password reset request has been created.";

try {
    $Stmt = $pdo->prepare(
        "SELECT id, status
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $Stmt->execute([$UserEmail]);

    $User = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to process request", 500);
}

if ($User && $User["status"] === "active") {

    $ResetToken = generateToken(32);

    $ExpiresAt = date(
        "Y-m-d H:i:s",
        strtotime("+30 minutes")
    );

    $UserId = (int)$User["id"];

    try {

        $Stmt = $pdo->prepare(
            "DELETE FROM password_reset_tokens
             WHERE user_id = ? AND used_at IS NULL"
        );

        $Stmt->execute([$UserId]);

        $Stmt = $pdo->prepare(
            "INSERT INTO password_reset_tokens
            (user_id, token, expires_at)
            VALUES (?, ?, ?)"
        );

        $Stmt->execute([
            $UserId,
            $ResetToken,
            $ExpiresAt
        ]);

    } catch (PDOException $e) {
        errorResponse("Unable to process request", 500);
    }
}

successResponse(
    [],
    $GenericMessage
);

?>
