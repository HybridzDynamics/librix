<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Request Data

$AuthorId = $authorId ?? ($parts[3] ?? null);


// Single Author

if ($AuthorId !== null) {

    $IdError = ValidatePositiveInteger($AuthorId, "Author ID");

    if ($IdError !== null) {
        ErrorResponse($IdError, 400);
    }

    try {
        $Stmt = $pdo->prepare(
            "SELECT
                authors.id,
                authors.name,
                authors.biography,
                authors.created_at,
                authors.updated_at,
                COUNT(books.id) AS total_books
             FROM authors
             LEFT JOIN books
                ON authors.id = books.author_id
             WHERE authors.id = ?
             GROUP BY authors.id
             LIMIT 1"
        );

        $Stmt->execute([(int)$AuthorId]);

        $Author = $Stmt->fetch();

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    if (!$Author) {
        NotFoundResponse("Author not found");
    }

    $Author["total_books"] = (int)$Author["total_books"];

    SuccessResponse($Author);
}


// All Authors

try {
    $Stmt = $pdo->prepare(
        "SELECT
            authors.id,
            authors.name,
            authors.biography,
            authors.created_at,
            authors.updated_at,
            COUNT(books.id) AS total_books
         FROM authors
         LEFT JOIN books
            ON authors.id = books.author_id
         GROUP BY authors.id
         ORDER BY authors.name ASC"
    );

    $Stmt->execute();

    $Authors = $Stmt->fetchAll();

    foreach ($Authors as &$Item) {
        $Item["total_books"] = (int)$Item["total_books"];
    }

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

SuccessResponse($Authors);

?>
