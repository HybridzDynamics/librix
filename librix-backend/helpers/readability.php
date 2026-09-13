<?php

// Readability Helper Functions

/**
 * Counts the syllables in an English word.
 */
function CountWordSyllables(string $Word): int
{
    $Word = strtolower(trim($Word));
    $Word = preg_replace("/[^a-z]/", "", $Word);

    if (strlen($Word) <= 3) {
        return 1;
    }

    // Remove silent 'e' at the end unless preceded by 'l' (like 'table')
    if (str_ends_with($Word, "e") && !str_ends_with($Word, "le")) {
        $Word = substr($Word, 0, -1);
    }

    // Count vowel groups
    preg_match_all("/[aeiouy]{1,2}/", $Word, $Matches);
    $Count = count($Matches[0]);

    return max(1, $Count);
}

/**
 * Computes Flesch Reading Ease, Flesch-Kincaid Grade, and reading time from text.
 */
function AnalyzeTextReadability(string $Text): array
{
    $Text = trim($Text);
    if (empty($Text)) {
        return [
            "word_count" => 0,
            "sentence_count" => 0,
            "syllable_count" => 0,
            "flesch_reading_ease" => 0.0,
            "flesch_kincaid_grade" => 0.0,
            "difficulty_level" => "standard",
            "estimated_reading_minutes" => 0
        ];
    }

    // Split sentences (. ! ? or newline)
    $Sentences = preg_split('/[.!?]+(?:\s+|$)/', $Text, -1, PREG_SPLIT_NO_EMPTY);
    $SentenceCount = max(1, count($Sentences));

    // Split words
    $Words = preg_split('/\s+/', $Text, -1, PREG_SPLIT_NO_EMPTY);
    $WordCount = count($Words);

    if ($WordCount === 0) {
        return [
            "word_count" => 0,
            "sentence_count" => $SentenceCount,
            "syllable_count" => 0,
            "flesch_reading_ease" => 0.0,
            "flesch_kincaid_grade" => 0.0,
            "difficulty_level" => "standard",
            "estimated_reading_minutes" => 0
        ];
    }

    // Count total syllables
    $TotalSyllables = 0;
    foreach ($Words as $Word) {
        $TotalSyllables += CountWordSyllables($Word);
    }

    // Flesch Reading Ease: 206.835 - (1.015 * (words/sentences)) - (84.6 * (syllables/words))
    $WordsPerSentence = $WordCount / $SentenceCount;
    $SyllablesPerWord = $TotalSyllables / $WordCount;

    $EaseScore = 206.835 - (1.015 * $WordsPerSentence) - (84.6 * $SyllablesPerWord);
    $EaseScore = round(max(0.0, min(100.0, $EaseScore)), 2);

    // Flesch-Kincaid Grade Level: (0.39 * (words/sentences)) + (11.8 * (syllables/words)) - 15.59
    $GradeLevel = (0.39 * $WordsPerSentence) + (11.8 * $SyllablesPerWord) - 15.59;
    $GradeLevel = round(max(0.0, $GradeLevel), 1);

    // Difficulty classification
    if ($EaseScore >= 90) {
        $Difficulty = "very_easy";
    } elseif ($EaseScore >= 80) {
        $Difficulty = "easy";
    } elseif ($EaseScore >= 70) {
        $Difficulty = "fairly_easy";
    } elseif ($EaseScore >= 60) {
        $Difficulty = "standard";
    } elseif ($EaseScore >= 50) {
        $Difficulty = "fairly_difficult";
    } elseif ($EaseScore >= 30) {
        $Difficulty = "difficult";
    } else {
        $Difficulty = "very_difficult";
    }

    // Estimated reading time at ~200 words per minute
    $ReadingMinutes = max(1, (int)ceil($WordCount / 200));

    return [
        "word_count" => $WordCount,
        "sentence_count" => $SentenceCount,
        "syllable_count" => $TotalSyllables,
        "flesch_reading_ease" => $EaseScore,
        "flesch_kincaid_grade" => $GradeLevel,
        "difficulty_level" => $Difficulty,
        "estimated_reading_minutes" => $ReadingMinutes
    ];
}

?>
