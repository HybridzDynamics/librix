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

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    MethodNotAllowedResponse(["PUT"]);
}


// Authentication

$User = RequireAuth();
RequireAdmin($User);


// Request Data

$AuthorId = $authorId ?? ($parts[3] ?? null);

$IdError = ValidatePositiveInteger($AuthorId, "Author ID");

if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$AuthorId = (int)$AuthorId;


// Find Existing Author

try {
    $Stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ? LIMIT 1");
    $Stmt->execute([$AuthorId]);
    $ExistingAuthor = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

if (!$ExistingAuthor) {
    NotFoundResponse("Author not found");
}

$RequestData = GetJsonInput();

$Name = array_key_exists("name", $RequestData) ? trim($RequestData["name"]) : $ExistingAuthor["name"];
$Biography = array_key_exists("biography", $RequestData) ? (trim((string)$RequestData["biography"]) ?: null) : $ExistingAuthor["biography"];


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
        "UPDATE authors
         SET name = ?,
             biography = ?
         WHERE id = ?"
    );

    $Stmt->execute([
        $Name,
        $Biography,
        $AuthorId
    ]);

} catch (PDOException $e) {
    ErrorResponse("Unable to update author", 500);
}


// Fetch Updated Author

try {
    $Stmt = $pdo->prepare(
        "SELECT id, name, biography, created_at, updated_at
         FROM authors
         WHERE id = ?
         LIMIT 1"
    );

    $Stmt->execute([$AuthorId]);

    $UpdatedAuthor = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Unable to retrieve updated author", 500);
}


// Audit Log

LogAudit((int)$User["id"], "author_updated", "authors", $AuthorId, "Updated author '$Name'");


// Response

SuccessResponse(
    $UpdatedAuthor,
    "Author updated successfully"
);

?>
