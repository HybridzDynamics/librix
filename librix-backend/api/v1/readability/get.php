<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../helpers/readability.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Book ID

$BookId = $_GET["book_id"] ?? ($parts[3] ?? null);

$IdError = ValidatePositiveInteger($BookId, "Book ID");
if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$BookId = (int)$BookId;


// Check Readability Table

try {
    $Stmt = $pdo->prepare(
        "SELECT 
            readability_analysis.id,
            readability_analysis.book_id,
            readability_analysis.word_count,
            readability_analysis.sentence_count,
            readability_analysis.syllable_count,
            readability_analysis.flesch_reading_ease,
            readability_analysis.flesch_kincaid_grade,
            readability_analysis.difficulty_level,
            readability_analysis.estimated_reading_minutes,
            readability_analysis.analyzed_at,
            books.title AS book_title
         FROM readability_analysis
         INNER JOIN books ON readability_analysis.book_id = books.id
         WHERE readability_analysis.book_id = ?
         LIMIT 1"
    );
    $Stmt->execute([$BookId]);
    $Readability = $Stmt->fetch();

    if ($Readability) {
        $Readability["id"] = (int)$Readability["id"];
        $Readability["book_id"] = (int)$Readability["book_id"];
        $Readability["word_count"] = (int)$Readability["word_count"];
        $Readability["sentence_count"] = (int)$Readability["sentence_count"];
        $Readability["syllable_count"] = (int)$Readability["syllable_count"];
        $Readability["flesch_reading_ease"] = (float)$Readability["flesch_reading_ease"];
        $Readability["flesch_kincaid_grade"] = (float)$Readability["flesch_kincaid_grade"];
        $Readability["estimated_reading_minutes"] = (int)$Readability["estimated_reading_minutes"];

        SuccessResponse($Readability);
    }

    // If not analyzed yet, check if book exists
    $BookStmt = $pdo->prepare("SELECT id, title, description FROM books WHERE id = ? LIMIT 1");
    $BookStmt->execute([$BookId]);
    $Book = $BookStmt->fetch();

    if (!$Book) {
        NotFoundResponse("Book not found");
    }

    // Auto-analyze if description is available
    if (!empty($Book["description"])) {
        $Analysis = AnalyzeTextReadability($Book["description"]);

        $InsertStmt = $pdo->prepare(
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

        $SampleText = mb_substr($Book["description"], 0, 1000);
        $InsertStmt->execute([
            $BookId,
            $SampleText,
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
        $Analysis["id"] = (int)$pdo->lastInsertId();

        SuccessResponse($Analysis);
    }

    NotFoundResponse("Readability analysis not found for this book");

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
