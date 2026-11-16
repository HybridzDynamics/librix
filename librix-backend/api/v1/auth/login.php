<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../helpers/audit.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$ClientIp = getClientIp();
$RateLimitKey = "login_" . $ClientIp;
$RetryAfter = checkRateLimit($RateLimitKey, RATE_LIMIT_LOGIN_MAX, RATE_LIMIT_WINDOW_SECONDS);

if ($RetryAfter > 0) {
    tooManyRequestsResponse("Too many login attempts. Please try again later.", $RetryAfter);
}

$RequestData = getJsonInput();

$UserEmail = trim($RequestData["email"] ?? "");
$Password = $RequestData["password"] ?? "";

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

$PasswordError = required($Password, "Password");

if ($PasswordError !== null) {
    $Errors["password"] = $PasswordError;
}

if (hasValidationErrors($Errors)) {
    ValidationerrorResponse($Errors);
}

try {
    $Stmt = $pdo->prepare(
        "SELECT id, name, email, password, role, status
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $Stmt->execute([$UserEmail]);

    $User = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

if (!$User) {
    errorResponse("Invalid email or password", 401);
}

if ($User["status"] !== "active") {
    forbiddenResponse("Your account is not active");
}

if (!password_verify($Password, $User["password"])) {
    errorResponse("Invalid email or password", 401);
}

$Token = generateToken(32);

$ExpiresAt = date(
    "Y-m-d H:i:s",
    strtotime("+7 days")
);

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO auth_tokens
        (user_id, token, expires_at)
        VALUES (?, ?, ?)"
    );

    $Stmt->execute([
        $User["id"],
        $Token,
        $ExpiresAt
    ]);

} catch (PDOException $e) {
    errorResponse("Unable to create authentication session", 500);
}

logAuditAction(
    $pdo,
    $User["id"],
    null,
    "login",
    "user",
    $User["id"],
    "User logged in successfully",
    getClientIp()
);

unset($User["password"]);

successResponse(
    [
        "token" => $Token,
        "expires_at" => $ExpiresAt,
        "user" => [
            "id" => (int)$User["id"],
            "name" => $User["name"],
            "email" => $User["email"],
            "role" => $User["role"],
            "status" => $User["status"]
        ]
    ],
    "Login successful"
);

?>
