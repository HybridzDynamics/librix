<?php

// Librarian: Link an existing book to this librarian's org
// POST /api/v1/librarian/link_book
// Body: { "book_id": 42, "copies": 3 }
// Creates a duplicate book record scoped to the librarian's org (preserving original),
// OR updates org_id if the book has no org and librarian is linking an unowned book.

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/librarian.php";

$User = requireAuth();
requireLibrarian($User);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$Body   = getRequestBody();
$BookId = isset($Body["book_id"]) ? (int)$Body["book_id"] : 0;
$Copies = isset($Body["copies"])  ? max(1, (int)$Body["copies"]) : 1;
$OrgId  = getOrgScope($User);

if (!$BookId) {
    badRequestResponse("book_id is required");
}

if (!$OrgId) {
    badRequestResponse("Your account must be assigned to an organization to link books");
}

try {
    // Fetch the source book
    $Stmt = $pdo->prepare("
        SELECT books.*, authors.name AS author_name, categories.name AS category_name
        FROM books
        LEFT JOIN authors    ON books.author_id    = authors.id
        LEFT JOIN categories ON books.category_id  = categories.id
        WHERE books.id = ?
        LIMIT 1
    ");
    $Stmt->execute([$BookId]);
    $Source = $Stmt->fetch();

    if (!$Source) {
        notFoundResponse("Book with ID $BookId not found in catalogue");
    }

    // Check if this exact book is already linked to this org
    $DupCheck = $pdo->prepare("
        SELECT id FROM books
        WHERE org_id = ? AND (isbn = ? OR title = ?)
        LIMIT 1
    ");
    $DupCheck->execute([
        $OrgId,
        $Source["isbn"] ?? "no-isbn-" . $BookId,
        $Source["title"]
    ]);
    $Existing = $DupCheck->fetch();

    if ($Existing) {
        successResponse([
            "message"  => "This book is already in your organization's collection",
            "book_id"  => (int)$Existing["id"],
            "org_id"   => $OrgId,
            "linked"   => false
        ]);
    }

    // Clone the book for this org
    $InsStmt = $pdo->prepare("
        INSERT INTO books (
            org_id, author_id, category_id, publisher_id,
            title, isbn, description, category, language,
            publisher, publication_year, total_copies,
            available_copies, cover_image, average_rating, rating_count, content
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?
        )
    ");
    $InsStmt->execute([
        $OrgId,
        $Source["author_id"],
        $Source["category_id"],
        $Source["publisher_id"],
        $Source["title"],
        $Source["isbn"] ? $Source["isbn"] . "-ORG-$OrgId" : null,  // suffix to avoid unique constraint
        $Source["description"],
        $Source["category"] ?? $Source["category_name"],
        $Source["language"],
        $Source["publisher"],
        $Source["publication_year"],
        $Copies,
        $Copies,
        $Source["cover_image"],
        $Source["average_rating"],
        $Source["rating_count"],
        $Source["content"]
    ]);

    $NewBookId = (int)$pdo->lastInsertId();

    // Clone readability analysis
    $ReadStmt = $pdo->prepare("
        SELECT * FROM readability_analysis WHERE book_id = ? LIMIT 1
    ");
    $ReadStmt->execute([$BookId]);
    $ReadRow = $ReadStmt->fetch();

    if ($ReadRow) {
        $pdo->prepare("
            INSERT INTO readability_analysis (
                book_id, sample_text, word_count, sentence_count, syllable_count,
                flesch_reading_ease, flesch_kincaid_grade, difficulty_level, estimated_reading_minutes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE flesch_reading_ease = VALUES(flesch_reading_ease)
        ")->execute([
            $NewBookId,
            $ReadRow["sample_text"],
            $ReadRow["word_count"],
            $ReadRow["sentence_count"],
            $ReadRow["syllable_count"],
            $ReadRow["flesch_reading_ease"],
            $ReadRow["flesch_kincaid_grade"],
            $ReadRow["difficulty_level"],
            $ReadRow["estimated_reading_minutes"]
        ]);
    } else {
        // Insert default readability
        $pdo->prepare("
            INSERT INTO readability_analysis (book_id, sample_text, word_count, sentence_count, syllable_count, flesch_reading_ease, flesch_kincaid_grade, difficulty_level, estimated_reading_minutes)
            VALUES (?, ?, 50, 4, 75, 70.0, 7.0, 'standard', 300)
        ")->execute([$NewBookId, substr($Source["description"] ?? $Source["title"], 0, 500)]);
    }

    logInfo("Book linked to org", [
        "source_book_id" => $BookId,
        "new_book_id"    => $NewBookId,
        "org_id"         => $OrgId,
        "librarian_id"   => $User["id"],
        "copies"         => $Copies
    ]);

    successResponse([
        "message"  => "Book '{$Source['title']}' successfully added to your organization's collection",
        "book_id"  => $NewBookId,
        "org_id"   => $OrgId,
        "copies"   => $Copies,
        "linked"   => true,
        "title"    => $Source["title"]
    ], null, 201);

} catch (PDOException $e) {
    logError("link_book DB error", ["error" => $e->getMessage()]);
    errorResponse("Database error: " . $e->getMessage(), 500);
}
?>
