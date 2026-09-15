<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Request Data

$CategoryId = $categoryId ?? ($parts[3] ?? ($_GET["id"] ?? null));


// Single Category

if ($CategoryId !== null) {
    $IdError = validatePositiveInteger($CategoryId, "Category ID");
    if ($IdError !== null) {
        errorResponse($IdError, 400);
    }

    try {
        $Stmt = $pdo->prepare(
            "SELECT 
                categories.id,
                categories.name,
                categories.description,
                categories.created_at,
                COUNT(books.id) AS total_books
             FROM categories
             LEFT JOIN books ON categories.id = books.category_id OR categories.name = books.category
             WHERE categories.id = ?
             GROUP BY categories.id
             LIMIT 1"
        );
        $Stmt->execute([(int)$CategoryId]);
        $Category = $Stmt->fetch();

        if (!$Category) {
            notFoundResponse("Category not found");
        }

        $Category["id"] = (int)$Category["id"];
        $Category["total_books"] = (int)$Category["total_books"];

        successResponse($Category);

    } catch (PDOException $e) {
        errorResponse("Database error", 500);
    }
}


// All Categories

try {
    $Stmt = $pdo->prepare(
        "SELECT 
            categories.id,
            categories.name,
            categories.description,
            categories.created_at,
            COUNT(books.id) AS total_books
         FROM categories
         LEFT JOIN books ON categories.id = books.category_id OR categories.name = books.category
         GROUP BY categories.id
         ORDER BY categories.name ASC"
    );
    $Stmt->execute();
    $Categories = $Stmt->fetchAll();

    foreach ($Categories as &$Item) {
        $Item["id"] = (int)$Item["id"];
        $Item["total_books"] = (int)$Item["total_books"];
    }

    successResponse($Categories);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
