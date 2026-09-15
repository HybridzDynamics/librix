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


// Authentication (requires auth, preferably admin or authenticated user)

$User = requireAuth();
$UserId = (int)$User["id"];


// Request Data

$Input = getJsonInput();
$BookId = $Input["book_id"] ?? null;
$Text = $Input["text"] ?? null;


// Validation

$IdError = validatePositiveInteger($BookId, "Book ID");
if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$BookId = (int)$BookId;

if (empty($Text) || !is_string($Text)) {
    errorResponse("Sample text is required for readability analysis", 400);
}


// Verify Book Exists

try {
    $BookStmt = $pdo->prepare("SELECT id, title FROM books WHERE id = ? LIMIT 1");
    $BookStmt->execute([$BookId]);
    $Book = $BookStmt->fetch();

    if (!$Book) {
        notFoundResponse("Book not found");
    }

    // Run Analysis
    $Analysis = AnalyzeTextReadability($Text);

    // Save to Database
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
            analyzed_at = CURRENT_TIMESTAMP"
    );

    $SampleTextSnippet = mb_substr($Text, 0, 1000);
    $Stmt->execute([
        $BookId,
        $SampleTextSnippet,
        $Analysis["word_count"],
        $Analysis["sentence_count"],
        $Analysis["syllable_count"],
        $Analysis["flesch_reading_ease"],
        $Analysis["flesch_kincaid_grade"],
        $Analysis["difficulty_level"],
        $Analysis["estimated_reading_minutes"]
    ]);

    $Analysis["book_id"] = $BookId;
    $Analysis["book_title"] = $Book["title"];

    logAudit($UserId, "analyze_readability", "readability_analysis", $BookId, "Analyzed readability for book #{$BookId}");

    successResponse($Analysis, "Readability analysis completed successfully", 200);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
