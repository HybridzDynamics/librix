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

$PublisherId = $_GET["id"] ?? null;


// Single Publisher

if ($PublisherId !== null) {
    $IdError = validatePositiveInteger($PublisherId, "Publisher ID");
    if ($IdError !== null) {
        errorResponse($IdError, 400);
    }

    try {
        $Stmt = $pdo->prepare(
            "SELECT 
                publishers.id,
                publishers.name,
                publishers.address,
                publishers.website,
                publishers.created_at,
                publishers.updated_at,
                COUNT(books.id) AS total_books
             FROM publishers
             LEFT JOIN books ON publishers.id = books.publisher_id
             WHERE publishers.id = ?
             GROUP BY publishers.id
             LIMIT 1"
        );
        $Stmt->execute([(int)$PublisherId]);
        $Publisher = $Stmt->fetch();

        if (!$Publisher) {
            notFoundResponse("Publisher not found");
        }

        $Publisher["id"] = (int)$Publisher["id"];
        $Publisher["total_books"] = (int)$Publisher["total_books"];

        successResponse($Publisher);

    } catch (PDOException $e) {
        errorResponse("Database error", 500);
    }
}


// All Publishers

try {
    $Stmt = $pdo->prepare(
        "SELECT 
            publishers.id,
            publishers.name,
            publishers.address,
            publishers.website,
            publishers.created_at,
            publishers.updated_at,
            COUNT(books.id) AS total_books
         FROM publishers
         LEFT JOIN books ON publishers.id = books.publisher_id
         GROUP BY publishers.id
         ORDER BY publishers.name ASC"
    );
    $Stmt->execute();
    $Publishers = $Stmt->fetchAll();

    foreach ($Publishers as &$Item) {
        $Item["id"] = (int)$Item["id"];
        $Item["total_books"] = (int)$Item["total_books"];
    }

    successResponse($Publishers);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
