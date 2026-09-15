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

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    methodNotAllowedResponse(["DELETE"]);
}


// Authentication

$User = requireAuth();
requireAdmin($User);


// Request Data

$AuthorId = $authorId ?? ($parts[3] ?? null);

$IdError = validatePositiveInteger($AuthorId, "Author ID");

if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$AuthorId = (int)$AuthorId;


// Check Existing Author

try {
    $Stmt = $pdo->prepare("SELECT id, name FROM authors WHERE id = ? LIMIT 1");
    $Stmt->execute([$AuthorId]);
    $ExistingAuthor = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

if (!$ExistingAuthor) {
    notFoundResponse("Author not found");
}


// Delete Author

try {
    $Stmt = $pdo->prepare("DELETE FROM authors WHERE id = ?");
    $Stmt->execute([$AuthorId]);

    logAudit((int)$User["id"], "author_deleted", "authors", $AuthorId, "Deleted author '{$ExistingAuthor['name']}'");

} catch (PDOException $e) {
    errorResponse("Unable to delete author", 500);
}


// Response

successResponse(
    [
        "id" => $AuthorId,
        "name" => $ExistingAuthor["name"]
    ],
    "Author deleted successfully"
);

?>
