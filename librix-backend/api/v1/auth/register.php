<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Rate Limiting

$ClientIp = getClientIp();
$RateLimitKey = "register_" . $ClientIp;
$RetryAfter = checkRateLimit($RateLimitKey, RATE_LIMIT_AUTH_MAX, RATE_LIMIT_WINDOW_SECONDS);

if ($RetryAfter > 0) {
    tooManyRequestsResponse("Too many registration attempts. Please try again later.", $RetryAfter);
}


// Request Data

$RequestData = getJsonInput();

$UserName = trim($RequestData["name"] ?? "");
$UserEmail = trim($RequestData["email"] ?? "");
$Password = $RequestData["password"] ?? "";


// Validation

$Errors = [];

$NameError = required($UserName, "Name");

if ($NameError !== null) {
    $Errors["name"] = $NameError;
} else {
    $NameError = minLength($UserName, 2, "Name");

    if ($NameError !== null) {
        $Errors["name"] = $NameError;
    }
}

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
} else {
    $PasswordError = minLength($Password, 8, "Password");

    if ($PasswordError !== null) {
        $Errors["password"] = $PasswordError;
    }
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}


// Check Existing User

try {
    $Stmt = $pdo->prepare(
        "SELECT id FROM users WHERE email = ? LIMIT 1"
    );

    $Stmt->execute([$UserEmail]);

    $ExistingUser = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

if ($ExistingUser) {
    errorResponse("Email is already registered", 409);
}


// Create User

$HashedPassword = password_hash(
    $Password,
    PASSWORD_DEFAULT
);

$UserRole = $RequestData["role"] ?? "user";
if (!in_array($UserRole, ["user", "librarian"])) {
    $UserRole = "user"; // Default to user if invalid
}

$OrgId = isset($RequestData["org_id"]) ? (int)$RequestData["org_id"] : null;

$InitialStatus = "pending"; // Always require approval for librarians and users joining orgs
$EmailVerifiedAt = EMAIL_VERIFICATION_ENABLED ? null : date("Y-m-d H:i:s");

try {
    $pdo->beginTransaction();

    $Stmt = $pdo->prepare(
        "INSERT INTO users
        (name, email, password, role, status, email_verified_at, org_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $Stmt->execute([
        $UserName,
        $UserEmail,
        $HashedPassword,
        $UserRole,
        $InitialStatus,
        $EmailVerifiedAt,
        $OrgId
    ]);

    $UserId = (int)$pdo->lastInsertId();

    if (EMAIL_VERIFICATION_ENABLED) {
        $VerificationToken = generateToken(32);
        $ExpiresAt = date("Y-m-d H:i:s", strtotime("+24 hours"));

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
    }

    $pdo->commit();

    logAudit($UserId, "user_registered", "users", $UserId, "User registered with email $UserEmail");

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse("Unable to create account", 500);
}


// Get Created User

try {
    $Stmt = $pdo->prepare(
        "SELECT id, name, email, role, status, email_verified_at, created_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $Stmt->execute([$UserId]);

    $User = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to retrieve account", 500);
}


// Registration Response

successResponse(
    [
        "user" => [
            "id" => (int)$User["id"],
            "name" => $User["name"],
            "email" => $User["email"],
            "role" => $User["role"],
            "status" => $User["status"],
            "created_at" => $User["created_at"]
        ]
    ],
    "Registration successful",
    201
);

?>