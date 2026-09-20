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


// Query Parameters

$BookId = $_GET["book_id"] ?? null;
$Page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$Limit = isset($_GET["limit"]) ? min(100, max(1, (int)$_GET["limit"])) : 10;
$Offset = ($Page - 1) * $Limit;


// If specific BookId provided

if ($BookId !== null) {
    $IdError = validatePositiveInteger($BookId, "Book ID");
    if ($IdError !== null) {
        errorResponse($IdError, 400);
    }

    try {
        // Get Stats
        $StatsStmt = $pdo->prepare(
            "SELECT 
                COUNT(id) AS total_reviews,
                COALESCE(ROUND(AVG(rating), 1), 0.0) AS average_rating
             FROM reviews 
             WHERE book_id = ?"
        );
        $StatsStmt->execute([(int)$BookId]);
        $Stats = $StatsStmt->fetch();

        // Get Total for pagination
        $Total = (int)($Stats["total_reviews"] ?? 0);

        // Get Reviews
        $Stmt = $pdo->prepare(
            "SELECT 
                reviews.id,
                reviews.book_id,
                reviews.user_id,
                reviews.rating,
                reviews.review_text,
                reviews.created_at,
                reviews.updated_at,
                users.name AS user_name
             FROM reviews
             INNER JOIN users ON reviews.user_id = users.id
             WHERE reviews.book_id = ?
             ORDER BY reviews.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $Stmt->bindValue(1, (int)$BookId, PDO::PARAM_INT);
        $Stmt->bindValue(2, $Limit, PDO::PARAM_INT);
        $Stmt->bindValue(3, $Offset, PDO::PARAM_INT);
        $Stmt->execute();
        $Reviews = $Stmt->fetchAll();

        foreach ($Reviews as &$Review) {
            $Review["id"] = (int)$Review["id"];
            $Review["book_id"] = (int)$Review["book_id"];
            $Review["user_id"] = (int)$Review["user_id"];
            $Review["rating"] = (int)$Review["rating"];
        }

        $TotalPages = $Limit > 0 ? (int)ceil($Total / $Limit) : 1;

        successResponse([
            "reviews" => $Reviews,
            "average_rating" => (float)$Stats["average_rating"],
            "total_reviews" => $Total,
            "pagination" => [
                "page" => $Page,
                "limit" => $Limit,
                "total" => $Total,
                "total_pages" => $TotalPages
            ]
        ]);

    } catch (PDOException $e) {
        errorResponse("Database error", 500);
    }
}


// All Reviews (e.g. for general listing or admin)

try {
    $CountStmt = $pdo->query("SELECT COUNT(id) FROM reviews");
    $Total = (int)$CountStmt->fetchColumn();

    $Stmt = $pdo->prepare(
        "SELECT 
            reviews.id,
            reviews.book_id,
            reviews.user_id,
            reviews.rating,
            reviews.review_text,
            reviews.created_at,
            reviews.updated_at,
            users.name AS user_name,
            books.title AS book_title
         FROM reviews
         INNER JOIN users ON reviews.user_id = users.id
         INNER JOIN books ON reviews.book_id = books.id
         ORDER BY reviews.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $Stmt->bindValue(1, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue(2, $Offset, PDO::PARAM_INT);
    $Stmt->execute();
    $Reviews = $Stmt->fetchAll();

    foreach ($Reviews as &$Review) {
        $Review["id"] = (int)$Review["id"];
        $Review["book_id"] = (int)$Review["book_id"];
        $Review["user_id"] = (int)$Review["user_id"];
        $Review["rating"] = (int)$Review["rating"];
    }

    paginatedResponse($Reviews, $Page, $Limit, $Total);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
