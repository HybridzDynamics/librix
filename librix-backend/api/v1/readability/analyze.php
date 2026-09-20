<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../helpers/readability.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();


// Request Data

$RequestData = getJsonInput();

$BookId = $RequestData["book_id"] ?? null;
$Text = trim($RequestData["text"] ?? "");


// Validation

$Errors = [];

$IdError = validatePositiveInteger($BookId, "Book ID");
if ($IdError !== null) {
    $Errors["book_id"] = $IdError;
}

if (empty($Text)) {
    $Errors["text"] = "Text is required for analysis";
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}


// Check if book exists

try {
    $Stmt = $pdo->prepare("SELECT id, title FROM books WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$BookId]);
    $Book = $Stmt->fetch();
    
    if (!$Book) {
        notFoundResponse("Book not found");
    }
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Analyze Text

$Analysis = AnalyzeTextReadability($Text);


// Store Analysis

try {
    $SampleText = mb_substr($Text, 0, 1000);
    
    $Stmt = $pdo->prepare(
        "INSERT INTO readability_analysis 
         (book_id, sample_text, word_count, sentence_count, syllable_count, flesch_reading_ease, flesch_kincaid_grade, difficulty_level, estimated_reading_minutes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            sample_text = VALUES(sample_text),
            word_count = VALUES(word_count),
            sentence_count = VALUES(sentence_count),
            syllable_count = VALUES(syllable_count),
            flesch_reading_ease = VALUES(flesch_reading_ease),
            flesch_kincaid_grade = VALUES(flesch_kincaid_grade),
            difficulty_level = VALUES(difficulty_level),
            estimated_reading_minutes = VALUES(estimated_reading_minutes),
            created_at = CURRENT_TIMESTAMP"
    );
    
    $Stmt->execute([
        (int)$BookId,
        $SampleText,
        $Analysis["word_count"],
        $Analysis["sentence_count"],
        $Analysis["syllable_count"],
        $Analysis["flesch_reading_ease"],
        $Analysis["flesch_kincaid_grade"],
        $Analysis["difficulty_level"],
        $Analysis["estimated_reading_minutes"]
    ]);
    
    logAudit($User["id"], "readability_analyzed", "readability_analysis", (int)$BookId, "Analyzed readability for book: {$Book['title']}");
    
} catch (PDOException $e) {
    errorResponse("Failed to store analysis", 500);
}


$Analysis["book_id"] = (int)$BookId;
$Analysis["book_title"] = $Book["title"];

successResponse($Analysis, "Readability analysis completed");

?>