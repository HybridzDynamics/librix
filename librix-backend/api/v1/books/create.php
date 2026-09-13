<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";
require __DIR__ . "/../../../middleware/auth.php";
require __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();
requireAdmin($User);


// Request Data

$RequestData = getJsonInput();

$Title = trim($RequestData["title"] ?? "");
$AuthorId = $RequestData["author_id"] ?? null;
$Isbn = isset($RequestData["isbn"]) ? trim($RequestData["isbn"]) : null;
$Description = isset($RequestData["description"]) ? trim($RequestData["description"]) : null;
$Category = isset($RequestData["category"]) ? trim($RequestData["category"]) : null;
$Publisher = isset($RequestData["publisher"]) ? trim($RequestData["publisher"]) : null;
$PublicationYear = $RequestData["publication_year"] ?? null;
$TotalCopies = $RequestData["total_copies"] ?? 1;
$CoverImage = isset($RequestData["cover_image"]) ? trim($RequestData["cover_image"]) : null;


// Validation

$Errors = [];

$TitleError = required($Title, "Title");

if ($TitleError !== null) {
    $Errors["title"] = $TitleError;
} else {
    $TitleError = maxLength($Title, 255, "Title");

    if ($TitleError !== null) {
        $Errors["title"] = $TitleError;
    }
}

if ($AuthorId !== null && $AuthorId !== "") {
    $AuthorIdError = validatePositiveInteger($AuthorId, "Author ID");

    if ($AuthorIdError !== null) {
        $Errors["author_id"] = $AuthorIdError;
    } else {
        // Verify Author Exists
        try {
            $Stmt = $pdo->prepare("SELECT id FROM authors WHERE id = ? LIMIT 1");
            $Stmt->execute([(int)$AuthorId]);

            if (!$Stmt->fetch()) {
                $Errors["author_id"] = "Selected author does not exist";
            }
        } catch (PDOException $e) {
            errorResponse("Database error", 500);
        }
    }
} else {
    $AuthorId = null;
}

if ($Isbn !== null && $Isbn !== "") {
    $IsbnLengthError = maxLength($Isbn, 20, "ISBN");

    if ($IsbnLengthError !== null) {
        $Errors["isbn"] = $IsbnLengthError;
    } else {
        // Verify ISBN Unique
        try {
            $Stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ? LIMIT 1");
            $Stmt->execute([$Isbn]);

            if ($Stmt->fetch()) {
                $Errors["isbn"] = "ISBN already exists";
            }
        } catch (PDOException $e) {
            errorResponse("Database error", 500);
        }
    }
} else {
    $Isbn = null;
}

$CopiesError = validatePositiveInteger($TotalCopies, "Total copies");

if ($CopiesError !== null) {
    $Errors["total_copies"] = $CopiesError;
} else {
    $TotalCopies = (int)$TotalCopies;
}

if ($PublicationYear !== null && $PublicationYear !== "") {
    if (!preg_match("/^\d{4}$/", (string)$PublicationYear)) {
        $Errors["publication_year"] = "Publication year must be a valid 4-digit year";
    }
} else {
    $PublicationYear = null;
}

if (hasValidationErrors($Errors)) {
    ValidationerrorResponse($Errors);
}


// Database Query

$AvailableCopies = $TotalCopies;

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO books
        (author_id, title, isbn, description, category, publisher, publication_year, total_copies, available_copies, cover_image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $Stmt->execute([
        $AuthorId,
        $Title,
        $Isbn,
        $Description,
        $Category,
        $Publisher,
        $PublicationYear,
        $TotalCopies,
        $AvailableCopies,
        $CoverImage
    ]);

    $NewBookId = (int)$pdo->lastInsertId();

} catch (PDOException $e) {
    errorResponse("Unable to create book", 500);
}


// Fetch Created Book

try {
    $Stmt = $pdo->prepare(
        "SELECT
            books.id,
            books.author_id,
            authors.name AS author_name,
            books.title,
            books.isbn,
            books.description,
            books.category,
            books.publisher,
            books.publication_year,
            books.total_copies,
            books.available_copies,
            books.cover_image,
            books.created_at,
            books.updated_at
         FROM books
         LEFT JOIN authors
            ON books.author_id = authors.id
         WHERE books.id = ?
         LIMIT 1"
    );

    $Stmt->execute([$NewBookId]);

    $CreatedBook = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Unable to retrieve created book", 500);
}


// Audit Log

logAudit((int)$User["id"], "book_created", "books", $NewBookId, "Created book '$Title'");


// Response

successResponse(
    $CreatedBook,
    "Book created successfully",
    201
);

?>
