<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    methodNotAllowedResponse(["PUT"]);
}

$User = requireAuth();
$UserId = (int)$User["id"];

try {
    $Stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $Stmt->execute([$UserId]);
    $ExistingUser = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

if (!$ExistingUser) {
    notFoundResponse("User not found");
}

$RequestData = getJsonInput();

$Name = array_key_exists("name", $RequestData) ? trim($RequestData["name"]) : $ExistingUser["name"];
$Email = array_key_exists("email", $RequestData) ? trim($RequestData["email"]) : $ExistingUser["email"];
$Password = array_key_exists("password", $RequestData) && !empty($RequestData["password"]) ? $RequestData["password"] : null;
$CurrentPassword = array_key_exists("current_password", $RequestData) ? $RequestData["current_password"] : null;
$Phone = array_key_exists("phone", $RequestData) ? trim($RequestData["phone"]) : null;
$Address = array_key_exists("address", $RequestData) ? trim($RequestData["address"]) : null;
$Bio = array_key_exists("bio", $RequestData) ? trim($RequestData["bio"]) : null;

$Errors = [];

$NameError = required($Name, "Name");

if ($NameError !== null) {
    $Errors["name"] = $NameError;
} else {
    $NameError = minLength($Name, 2, "Name");

    if ($NameError !== null) {
        $Errors["name"] = $NameError;
    } else {
        $NameError = maxLength($Name, 100, "Name");

        if ($NameError !== null) {
            $Errors["name"] = $NameError;
        }
    }
}

$EmailError = required($Email, "Email");

if ($EmailError !== null) {
    $Errors["email"] = $EmailError;
} else {
    $EmailError = validateEmail($Email);

    if ($EmailError !== null) {
        $Errors["email"] = $EmailError;
    } else {

        try {
            $Stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $Stmt->execute([$Email, $UserId]);

            if ($Stmt->fetch()) {
                $Errors["email"] = "Email is already in use by another account";
            }
        } catch (PDOException $e) {
            errorResponse("Database error", 500);
        }
    }
}

if ($Password !== null) {

    if ($CurrentPassword !== null) {
        if (!password_verify($CurrentPassword, $ExistingUser["password"])) {
            $Errors["current_password"] = "Current password is incorrect";
        }
    } else {
        $Errors["current_password"] = "Current password is required to change password";
    }

    $PasswordError = minLength($Password, 6, "Password");

    if ($PasswordError !== null) {
        $Errors["password"] = $PasswordError;
    }
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}

try {
    if ($Password !== null) {
        $HashedPassword = password_hash($Password, PASSWORD_DEFAULT);

        $Stmt = $pdo->prepare(
            "UPDATE users
             SET name = ?,
                 email = ?,
                 password = ?,
                 phone = ?,
                 address = ?,
                 bio = ?
             WHERE id = ?"
        );

        $Stmt->execute([
            $Name,
            $Email,
            $HashedPassword,
            $Phone,
            $Address,
            $Bio,
            $UserId
        ]);

    } else {
        $Stmt = $pdo->prepare(
            "UPDATE users
             SET name = ?,
                 email = ?,
                 phone = ?,
                 address = ?,
                 bio = ?
             WHERE id = ?"
        );

        $Stmt->execute([
            $Name,
            $Email,
            $Phone,
            $Address,
            $Bio,
            $UserId
        ]);
    }

} catch (PDOException $e) {
    errorResponse("Unable to update profile", 500);
}

try {
    $Stmt = $pdo->prepare(
        "SELECT id, name, email, role, status, profile_picture_url, phone, address, bio, created_at, updated_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $Stmt->execute([$UserId]);

    $UpdatedUser = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to retrieve updated profile", 500);
}

$UpdatedUser["id"] = (int)$UpdatedUser["id"];

successResponse(
    $UpdatedUser,
    "Profile updated successfully"
);

?>
