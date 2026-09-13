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
    MethodNotAllowedResponse(["POST"]);
}


// Authentication

$User = RequireAuth();
RequireAdmin($User);


// Request Data

$RequestData = GetJsonInput();

$Name = trim($RequestData["name"] ?? "");
$Biography = isset($RequestData["biography"]) ? trim($RequestData["biography"]) : null;


// Validation

$Errors = [];

$NameError = Required($Name, "Name");

if ($NameError !== null) {
    $Errors["name"] = $NameError;
} else {
    $NameError = MinLength($Name, 2, "Name");

    if ($NameError !== null) {
        $Errors["name"] = $NameError;
    } else {
        $NameError = MaxLength($Name, 150, "Name");

        if ($NameError !== null) {
            $Errors["name"] = $NameError;
        }
    }
}

if (HasValidationErrors($Errors)) {
    ValidationErrorResponse($Errors);
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
    ErrorResponse("Unable to create author", 500);
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
    ErrorResponse("Unable to retrieve created author", 500);
}


// Audit Log

LogAudit((int)$User["id"], "author_created", "authors", $AuthorId, "Created author '$Name'");


// Response

SuccessResponse(
    $CreatedAuthor,
    "Author created successfully",
    201
);

?>
