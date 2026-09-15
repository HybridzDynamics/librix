<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();


// Request Data

$RequestData = getJsonInput();

$LibraryName = trim($RequestData["library_name"] ?? "");
$LibraryAddress = trim($RequestData["library_address"] ?? "");
$LibraryPhone = trim($RequestData["library_phone"] ?? "");
$Message = trim($RequestData["message"] ?? "");


// Validation

$Errors = [];

$LibraryNameError = required($LibraryName, "Library Name");
if ($LibraryNameError !== null) {
    $Errors["library_name"] = $LibraryNameError;
} else {
    $LengthError = maxLength($LibraryName, 150, "Library Name");
    if ($LengthError !== null) {
        $Errors["library_name"] = $LengthError;
    }
}

if ($LibraryPhone !== "") {
    $LengthError = maxLength($LibraryPhone, 50, "Library Phone");
    if ($LengthError !== null) {
        $Errors["library_phone"] = $LengthError;
    }
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}


// Create Librarian Request

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO librarian_requests
        (user_id, name, email, library_name, library_address, library_phone, message, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    
    $Stmt->execute([
        $User["id"],
        $User["name"],
        $User["email"],
        $LibraryName,
        $LibraryAddress ?: null,
        $LibraryPhone ?: null,
        $Message ?: null
    ]);
    
    $RequestId = (int)$pdo->lastInsertId();
    
    logAudit($User["id"], "librarian_request_created", "librarian_requests", $RequestId, "Librarian request for: $LibraryName");
    
} catch (PDOException $e) {
    errorResponse("Unable to create librarian request", 500);
}


// Fetch Created Request

try {
    $Stmt = $pdo->prepare("SELECT * FROM librarian_requests WHERE id = ? LIMIT 1");
    $Stmt->execute([$RequestId]);
    
    $Request = $Stmt->fetch();
    
} catch (PDOException $e) {
    errorResponse("Unable to retrieve request details", 500);
}

successResponse($Request, "Librarian request submitted for admin approval", 201);

?>