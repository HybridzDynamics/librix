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

$BookId = $bookId ?? ($parts[3] ?? null);

$IdError = ValidatePositiveInteger($BookId, "Book ID");

if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$BookId = (int)$BookId;


// Find Existing Book

try {
    $Stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
    $Stmt->execute([$BookId]);
    $ExistingBook = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

if (!$ExistingBook) {
    NotFoundResponse("Book not found");
}

$RequestData = GetJsonInput();

$Title = array_key_exists("title", $RequestData) ? trim($RequestData["title"]) : $ExistingBook["title"];
$AuthorId = array_key_exists("author_id", $RequestData) ? $RequestData["author_id"] : $ExistingBook["author_id"];
$Isbn = array_key_exists("isbn", $RequestData) ? (trim((string)$RequestData["isbn"]) ?: null) : $ExistingBook["isbn"];
$Description = array_key_exists("description", $RequestData) ? $RequestData["description"] : $ExistingBook["description"];
$Category = array_key_exists("category", $RequestData) ? $RequestData["category"] : $ExistingBook["category"];
$Publisher = array_key_exists("publisher", $RequestData) ? $RequestData["publisher"] : $ExistingBook["publisher"];
$PublicationYear = array_key_exists("publication_year", $RequestData) ? $RequestData["publication_year"] : $ExistingBook["publication_year"];
$TotalCopies = array_key_exists("total_copies", $RequestData) ? $RequestData["total_copies"] : $ExistingBook["total_copies"];
$AvailableCopies = array_key_exists("available_copies", $RequestData) ? $RequestData["available_copies"] : null;
$CoverImage = array_key_exists("cover_image", $RequestData) ? $RequestData["cover_image"] : $ExistingBook["cover_image"];


// Validation

$Errors = [];

$TitleError = Required($Title, "Title");

if ($TitleError !== null) {
    $Errors["title"] = $TitleError;
} else {
    $TitleError = MaxLength($Title, 255, "Title");

    if ($TitleError !== null) {
        $Errors["title"] = $TitleError;
    }
}

if ($AuthorId !== null && $AuthorId !== "") {
    $AuthorIdError = ValidatePositiveInteger($AuthorId, "Author ID");

    if ($AuthorIdError !== null) {
        $Errors["author_id"] = $AuthorIdError;
    } else {
        try {
            $Stmt = $pdo->prepare("SELECT id FROM authors WHERE id = ? LIMIT 1");
            $Stmt->execute([(int)$AuthorId]);

            if (!$Stmt->fetch()) {
                $Errors["author_id"] = "Selected author does not exist";
            }
        } catch (PDOException $e) {
            ErrorResponse("Database error", 500);
        }
    }
} else {
    $AuthorId = null;
}

if ($Isbn !== null && $Isbn !== "") {
    $IsbnLengthError = MaxLength($Isbn, 20, "ISBN");

    if ($IsbnLengthError !== null) {
        $Errors["isbn"] = $IsbnLengthError;
    } else {
        try {
            $Stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ? AND id != ? LIMIT 1");
            $Stmt->execute([$Isbn, $BookId]);

            if ($Stmt->fetch()) {
                $Errors["isbn"] = "ISBN already exists";
            }
        } catch (PDOException $e) {
            ErrorResponse("Database error", 500);
        }
    }
}

$CopiesError = ValidatePositiveInteger($TotalCopies, "Total copies");

if ($CopiesError !== null) {
    $Errors["total_copies"] = $CopiesError;
} else {
    $TotalCopies = (int)$TotalCopies;
}

if ($AvailableCopies !== null) {
    if (!is_numeric($AvailableCopies) || (int)$AvailableCopies < 0 || (int)$AvailableCopies > $TotalCopies) {
        $Errors["available_copies"] = "Available copies must be between 0 and total copies ($TotalCopies)";
    } else {
        $AvailableCopies = (int)$AvailableCopies;
    }
} else {
    // Recalculate available copies if total copies changed
    $CopiesDifference = $TotalCopies - (int)$ExistingBook["total_copies"];
    $CalculatedAvailable = (int)$ExistingBook["available_copies"] + $CopiesDifference;
    $AvailableCopies = max(0, min($CalculatedAvailable, $TotalCopies));
}

if ($PublicationYear !== null && $PublicationYear !== "") {
    if (!preg_match("/^\d{4}$/", (string)$PublicationYear)) {
        $Errors["publication_year"] = "Publication year must be a valid 4-digit year";
    }
} else {
    $PublicationYear = null;
}

if (HasValidationErrors($Errors)) {
    ValidationErrorResponse($Errors);
}


// Database Query

try {
    $Stmt = $pdo->prepare(
        "UPDATE books
         SET author_id = ?,
             title = ?,
             isbn = ?,
             description = ?,
             category = ?,
             publisher = ?,
             publication_year = ?,
             total_copies = ?,
             available_copies = ?,
             cover_image = ?
         WHERE id = ?"
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
        $CoverImage,
        $BookId
    ]);

} catch (PDOException $e) {
    ErrorResponse("Unable to update book", 500);
}


// Fetch Updated Book

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

    $Stmt->execute([$BookId]);

    $UpdatedBook = $Stmt->fetch();

} catch (PDOException $e) {
    ErrorResponse("Unable to retrieve updated book", 500);
}


// Audit Log

LogAudit((int)$User["id"], "book_updated", "books", $BookId, "Updated book '$Title'");


// Response

SuccessResponse(
    $UpdatedBook,
    "Book updated successfully"
);

?>
