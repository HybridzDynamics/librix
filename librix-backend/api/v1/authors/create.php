<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();
requireAdmin($User);


// Request Data

$RequestData = getJsonInput();

$Name = trim($RequestData["name"] ?? "");
$Biography = isset($RequestData["biography"]) ? trim($RequestData["biography"]) : null;


// Validation

$Errors = [];

$NameError = required($Name, "Name");

if ($NameError !== null) {
    $Errors["name"] = $NameError;
} else {
    $NameError = minLength($Name, 2, "Name");

    if ($NameError !== null) {
        $Errors["name"] = $NameError;
    } else {
        $NameError = maxLength($Name, 150, "Name");

        if ($NameError !== null) {
            $Errors["name"] = $NameError;
        }
    }
}

if (hasValidationErrors($Errors)) {
    ValidationerrorResponse($Errors);
}


// Database Query

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO authors (name, biography)
         VALUES (?, ?)"
    );

    $Stmt->execute([
        $Name,
        $Biography
    ]);

    $AuthorId = (int)$pdo->lastInsertId();

} catch (PDOException $e) {
    errorResponse("Unable to create author", 500);
}


// Fetch Created Author

try {
    $Stmt = $pdo->prepare(
        "SELECT id, name, biography, created_at, updated_at
         FROM authors
         WHERE id = ?
         LIMIT 1"
    );

    $Stmt->execute([$AuthorId]);

    $CreatedAuthor = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to retrieve created author", 500);
}


// Audit Log

logAudit((int)$User["id"], "author_created", "authors", $AuthorId, "Created author '$Name'");


// Response

successResponse(
    $CreatedAuthor,
    "Author created successfully",
    201
);

?>
