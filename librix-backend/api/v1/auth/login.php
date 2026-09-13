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


// Rate Limiting

$ClientIp = GetClientIp();
$RateLimitKey = "login_" . $ClientIp;
$RetryAfter = CheckRateLimit($RateLimitKey, RATE_LIMIT_LOGIN_MAX, RATE_LIMIT_WINDOW_SECONDS);

if ($RetryAfter > 0) {
    TooManyRequestsResponse("Too many login attempts. Please try again later.", $RetryAfter);
}


// Request Data

$RequestData = GetJsonInput();

$UserEmail = trim($RequestData["email"] ?? "");
$Password = $RequestData["password"] ?? "";


// Validation

$Errors = [];

$EmailError = Required($UserEmail, "Email");

if ($EmailError !== null) {
    $Errors["email"] = $EmailError;
} else {
    $EmailError = ValidateEmail($UserEmail);

    if ($EmailError !== null) {
        $Errors["email"] = $EmailError;
    }
}

$PasswordError = Required($Password, "Password");

if ($PasswordError !== null) {
    $Errors["password"] = $PasswordError;
}

if (HasValidationErrors($Errors)) {
    ValidationErrorResponse($Errors);
}


// Find User

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
    ErrorResponse("Database error", 500);
}


// Check User

if (!$User) {
    ErrorResponse("Invalid email or password", 401);
}


// Check User Status

if ($User["status"] !== "active") {
    ForbiddenResponse("Your account is not active");
}


// Check Password

if (!password_verify($Password, $User["password"])) {
    ErrorResponse("Invalid email or password", 401);
}


// Generate Token

$Token = GenerateToken(32);


// Token Expiration

$ExpiresAt = date(
    "Y-m-d H:i:s",
    strtotime("+7 days")
);


// Store Token

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
    ErrorResponse("Unable to create authentication session", 500);
}


// Remove Password

unset($User["password"]);


// Login Response

SuccessResponse(
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