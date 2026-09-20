<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication

$User = requireAuth();
$UserId = (int)$User["id"];


// Query Parameters

$Limit = isset($_GET["limit"]) ? min(50, max(1, (int)$_GET["limit"])) : 10;
$Difficulty = $_GET["difficulty"] ?? ""; // Filter by readability difficulty
$BasedOn = $_GET["based_on"] ?? null; // Book ID to base recommendations on


try {
    $pdo->beginTransaction();
    
    // Get user's reading history for personalized recommendations
    $HistoryQuery = "
        SELECT DISTINCT bi.book_id, b.category, a.name AS author_name, r.difficulty_level
        FROM book_issues bi
        INNER JOIN books b ON bi.book_id = b.id
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN readability_analysis r ON b.id = r.book_id
        WHERE bi.user_id = ? AND bi.returned_at IS NOT NULL
        LIMIT 20
    ";
    
    $HistoryStmt = $pdo->prepare($HistoryQuery);
    $HistoryStmt->execute([$UserId]);
    $History = $HistoryStmt->fetchAll();
    
    // Build recommendation criteria based on history
    $PreferredCategories = [];
    $PreferredAuthors = [];
    $PreferredDifficulty = null;
    
    foreach ($History as $Book) {
        if ($Book["category"]) {
            $Categories = explode(",", $Book["category"]);
            foreach ($Categories as $Cat) {
                $Cat = trim($Cat);
                if (!empty($Cat) && !in_array($Cat, $PreferredCategories)) {
                    $PreferredCategories[] = $Cat;
                }
            }
        }
        
        if ($Book["author_name"] && !in_array($Book["author_name"], $PreferredAuthors)) {
            $PreferredAuthors[] = $Book["author_name"];
        }
        
        if ($Book["difficulty_level"] && !$PreferredDifficulty) {
            $PreferredDifficulty = $Book["difficulty_level"];
        }
    }
    
    // Override with user-specified difficulty
    if (!empty($Difficulty)) {
        $PreferredDifficulty = $Difficulty;
    }
    
    // Build search query
    $WhereConditions = ["b.available_copies > 0"];
    $Params = [];
    
    // Exclude books user has already read
    if (!empty($History)) {
        $ReadBookIds = array_column($History, "book_id");
        if (!empty($ReadBookIds)) {
            $Placeholders = implode(',', array_fill(0, count($ReadBookIds), '?'));
            $WhereConditions[] = "b.id NOT IN ({$Placeholders})";
            $Params = array_merge($Params, $ReadBookIds);
        }
    }
    
    // Filter by preferred categories
    if (!empty($PreferredCategories)) {
        $CategoryConditions = [];
        foreach ($PreferredCategories as $Cat) {
            $CategoryConditions[] = "b.category LIKE ?";
            $Params[] = "%{$Cat}%";
        }
        $WhereConditions[] = "(" . implode(" OR ", $CategoryConditions) . ")";
    }
    
    // Filter by preferred authors
    if (!empty($PreferredAuthors)) {
        $AuthorConditions = [];
        foreach ($PreferredAuthors as $Author) {
            $AuthorConditions[] = "a.name LIKE ?";
            $Params[] = "%{$Author}%";
        }
        $WhereConditions[] = "(" . implode(" OR ", $AuthorConditions) . ")";
    }
    
    // Filter by difficulty
    if ($PreferredDifficulty) {
        $WhereConditions[] = "r.difficulty_level = ?";
        $Params[] = $PreferredDifficulty;
    }
    
    // Get recommendations
    $Query = "
        SELECT 
            b.id,
            b.title,
            b.author_id,
            a.name AS author_name,
            b.category,
            b.average_rating AS rating,
            b.rating_count,
            b.cover_image,
            b.description,
            r.difficulty_level,
            r.flesch_reading_ease,
            r.flesch_kincaid_grade,
            CASE 
                WHEN b.average_rating >= 4.5 THEN 0.95
                WHEN b.average_rating >= 4.0 THEN 0.85
                WHEN b.average_rating >= 3.5 THEN 0.75
                WHEN b.average_rating >= 3.0 THEN 0.65
                ELSE 0.50
            END AS base_score
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN readability_analysis r ON b.id = r.book_id
        WHERE " . implode(" AND ", $WhereConditions) . "
        ORDER BY base_score DESC, b.average_rating DESC
        LIMIT ?
    ";
    
    $Params[] = $Limit;
    
    $Stmt = $pdo->prepare($Query);
    $Stmt->execute($Params);
    $Recommendations = $Stmt->fetchAll();
    
    // Save recommendations to database
    foreach ($Recommendations as $Rec) {
        $BookId = (int)$Rec["id"];
        $Score = (float)$Rec["base_score"];
        $Reason = [];
        
        if ($Rec["rating"] >= 4.0) {
            $Reason[] = "Highly rated";
        }
        
        if (in_array($Rec["difficulty_level"], [$PreferredDifficulty])) {
            $Reason[] = "Matches your reading level";
        }
        
        if (!empty($PreferredCategories)) {
            foreach ($PreferredCategories as $Cat) {
                if (stripos($Rec["category"], $Cat) !== false) {
                    $Reason[] = "Similar to books you've read";
                    break;
                }
            }
        }
        
        $ReasonText = implode(", ", $Reason) ?: "Popular choice";
        
        // Insert or update recommendation
        $UpsertStmt = $pdo->prepare(
            "INSERT INTO book_recommendations (user_id, book_id, reason, score)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                score = VALUES(score),
                created_at = CURRENT_TIMESTAMP"
        );
        
        $UpsertStmt->execute([$UserId, $BookId, $ReasonText, $Score]);
    }
    
    $pdo->commit();
    
    // Format response
    foreach ($Recommendations as &$Rec) {
        $Rec["id"] = (int)$Rec["id"];
        $Rec["author_id"] = (int)$Rec["author_id"];
        $Rec["rating"] = (float)$Rec["rating"];
        $Rec["base_score"] = (float)$Rec["base_score"];
        $Rec["flesch_reading_ease"] = $Rec["flesch_reading_ease"] ? (float)$Rec["flesch_reading_ease"] : null;
        $Rec["flesch_kincaid_grade"] = $Rec["flesch_kincaid_grade"] ? (float)$Rec["flesch_kincaid_grade"] : null;
    }
    
    successResponse($Recommendations, "Recommendations generated successfully");

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse("Database error", 500);
}

?>