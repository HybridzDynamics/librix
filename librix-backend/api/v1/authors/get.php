<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Request Data

$AuthorId = $_GET["id"] ?? null;


// Single Author

if ($AuthorId !== null) {

    $IdError = validatePositiveInteger($AuthorId, "Author ID");

    if ($IdError !== null) {
        errorResponse($IdError, 400);
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
        errorResponse("Database error", 500);
    }

    if (!$Author) {
        notFoundResponse("Author not found");
    }

    $Author["total_books"] = (int)$Author["total_books"];

    successResponse($Author);
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
    errorResponse("Database error", 500);
}

successResponse($Authors);

?>
