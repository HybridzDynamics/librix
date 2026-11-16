<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}

$User = null;
try {
    $User = requireAuth(false);
} catch (Exception $e) {

}

$Limit = isset($_GET["limit"]) ? min(50, max(1, (int)$_GET["limit"])) : 10;
$Type = $_GET["type"] ?? "popular";
$BookId = isset($_GET["book_id"]) ? (int)$_GET["book_id"] : null;

try {
    $Recommendations = getFallbackRecommendations($pdo, $Limit, $Type, $BookId, $User);

    successResponse([
        "recommendations" => $Recommendations,
        "type" => $Type,
        "count" => count($Recommendations),
        "source" => "database"
    ], "Recommendations retrieved successfully");

} catch (Exception $e) {
    errorResponse("Failed to retrieve recommendations", 500);
}

function getFallbackRecommendations($pdo, $limit, $type, $bookId, $user) {
    try {
        switch ($type) {
            case "popular":
                $Stmt = $pdo->prepare(
                    "SELECT b.id, b.title, b.author_name as author, b.cover_image, b.average_rating, b.rating_count,
                            (b.available_copies / b.total_copies) as availability_ratio
                     FROM books b
                     WHERE b.available_copies > 0
                     ORDER BY b.average_rating DESC, b.rating_count DESC
                     LIMIT ?"
                );
                $Stmt->execute([$limit]);
                return $Stmt->fetchAll();

            case "content":
                if (!$bookId) return [];
                $Stmt = $pdo->prepare(
                    "SELECT b.id, b.title, b.author_name as author, b.cover_image, b.average_rating,
                            b.category, b.language
                     FROM books b
                     WHERE b.id != ? AND b.available_copies > 0
                     ORDER BY
                        CASE WHEN b.category = (SELECT category FROM books WHERE id = ?) THEN 0 ELSE 1 END,
                        b.average_rating DESC
                     LIMIT ?"
                );
                $Stmt->execute([$bookId, $bookId, $limit]);
                return $Stmt->fetchAll();

            case "hybrid":
            case "collaborative":

                if ($user) {
                    $Stmt = $pdo->prepare(
                        "SELECT b.id, b.title, b.author_name as author, b.cover_image, b.average_rating,
                                b.category, b.available_copies
                         FROM books b
                         WHERE b.available_copies > 0
                         ORDER BY b.average_rating DESC, b.rating_count DESC
                         LIMIT ?"
                    );
                    $Stmt->execute([$limit]);
                    return $Stmt->fetchAll();
                }

            default:
                $Stmt = $pdo->prepare(
                    "SELECT b.id, b.title, b.author_name as author, b.cover_image, b.average_rating,
                            b.available_copies, b.category
                     FROM books b
                     WHERE b.available_copies > 0
                     ORDER BY b.created_at DESC
                     LIMIT ?"
                );
                $Stmt->execute([$limit]);
                return $Stmt->fetchAll();
        }
    } catch (PDOException $e) {
        return [];
    }
}

?>
