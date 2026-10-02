<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../config/bookmind.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication (optional for recommendations, but good for personalization)

$User = null;
try {
    $User = requireAuth(false); // Not required
} catch (Exception $e) {
    // Continue without auth for public recommendations
}


// Query Parameters

$Limit = isset($_GET["limit"]) ? min(50, max(1, (int)$_GET["limit"])) : 10;
$Type = $_GET["type"] ?? "popular"; // popular, content, collaborative, hybrid
$BookId = isset($_GET["book_id"]) ? (int)$_GET["book_id"] : null;
$UserId = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : null;


// BookMind API Configuration (loaded from config/bookmind.php)

$BookMindBaseUrl = BOOKMIND_API_URL;


try {
    $Recommendations = [];
    $BookMindAvailable = false;
    
    // Check if BookMind is available
    $HealthCheckUrl = BOOKMEND_HEALTH_ENDPOINT;
    $HealthCheck = @file_get_contents($HealthCheckUrl);
    if ($HealthCheck !== false) {
        $HealthData = json_decode($HealthCheck, true);
        if (isset($HealthData["status"]) && $HealthData["status"] === "healthy") {
            $BookMindAvailable = true;
        }
    }
    
    if ($BookMindAvailable) {
        switch ($Type) {
            case "popular":
                // Get popular books from BookMind
                $Url = $BookMindBaseUrl . "/recommendations/popular?limit=" . $Limit;
                $Response = @file_get_contents($Url);
                if ($Response !== false) {
                    $Data = json_decode($Response, true);
                    if (isset($Data["recommendations"])) {
                        $Recommendations = $Data["recommendations"];
                    }
                }
                break;
                
            case "content":
                // Get content-based recommendations for a specific book
                if (!$BookId) {
                    errorResponse("book_id parameter is required for content recommendations", 400);
                }
                $Url = $BookMindBaseUrl . "/recommendations/content/" . $BookId . "?limit=" . $Limit;
                $Response = @file_get_contents($Url);
                if ($Response !== false) {
                    $Data = json_decode($Response, true);
                    if (isset($Data["recommendations"])) {
                        $Recommendations = $Data["recommendations"];
                    }
                }
                break;
                
            case "collaborative":
                // Get collaborative filtering recommendations
                $TargetUserId = $UserId ?: ($User ? $User["id"] : null);
                if (!$TargetUserId) {
                    errorResponse("user_id parameter is required for collaborative recommendations", 400);
                }
                $Url = $BookMindBaseUrl . "/recommendations/collaborative/" . $TargetUserId . "?limit=" . $Limit;
                $Response = @file_get_contents($Url);
                if ($Response !== false) {
                    $Data = json_decode($Response, true);
                    if (isset($Data["recommendations"])) {
                        $Recommendations = $Data["recommendations"];
                    }
                }
                break;
                
            case "hybrid":
                // Get hybrid recommendations (best of both worlds)
                $TargetUserId = $UserId ?: ($User ? $User["id"] : null);
                if ($TargetUserId) {
                    $Url = $BookMindBaseUrl . "/recommendations/hybrid/" . $TargetUserId . "?limit=" . $Limit;
                    $Response = @file_get_contents($Url);
                    if ($Response !== false) {
                        $Data = json_decode($Response, true);
                        if (isset($Data["recommendations"])) {
                            $Recommendations = $Data["recommendations"];
                        }
                    }
                } else {
                    // Fallback to popular for unauthenticated users
                    $Url = $BookMindBaseUrl . "/recommendations/popular?limit=" . $Limit;
                    $Response = @file_get_contents($Url);
                    if ($Response !== false) {
                        $Data = json_decode($Response, true);
                        if (isset($Data["recommendations"])) {
                            $Recommendations = $Data["recommendations"];
                            // Add note that this is a fallback
                            foreach ($Recommendations as &$Rec) {
                                $Rec["fallback_reason"] = "cold_start_recommendation";
                            }
                        }
                    }
                }
                break;
                
            default:
                errorResponse("Invalid recommendation type. Use: popular, content, collaborative, or hybrid", 400);
        }
    }
    
    // If BookMind is not available or has no results, fall back to database-based recommendations
    if (empty($Recommendations) || !$BookMindAvailable) {
        $Recommendations = getFallbackRecommendations($pdo, $Limit, $Type, $BookId, $User);
        $Source = $BookMindAvailable ? "bookmind" : "database_fallback";
    } else {
        $Source = "bookmind";
    }
    
    successResponse([
        "recommendations" => $Recommendations,
        "type" => $Type,
        "count" => count($Recommendations),
        "source" => $Source,
        "bookmind_available" => $BookMindAvailable
    ], "Recommendations retrieved successfully");


// Fallback function when BookMind is unavailable

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
                // For logged-in users, get books from their favorite categories
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
                // Fall through to default
                
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
