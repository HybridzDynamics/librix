<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();


// Request Data

$RequestData = getJsonInput();

$IssueId = $RequestData["issue_id"] ?? null;
$BookId = $RequestData["book_id"] ?? null;


// Validation

if ($IssueId === null && $BookId === null) {
    errorResponse("Either issue_id or book_id is required", 400);
}

$UserId = (int)$User["id"];
$IsAdmin = ($User["role"] === "admin");


// Find Issue Record

try {
    if ($IssueId !== null) {
        $IdError = validatePositiveInteger($IssueId, "Issue ID");

        if ($IdError !== null) {
            errorResponse($IdError, 400);
        }

        $Stmt = $pdo->prepare(
            "SELECT id, book_id, user_id, due_date, status
             FROM book_issues
             WHERE id = ?
             LIMIT 1"
        );

        $Stmt->execute([(int)$IssueId]);

    } else {
        $BookIdError = validatePositiveInteger($BookId, "Book ID");

        if ($BookIdError !== null) {
            errorResponse($BookIdError, 400);
        }

        $Stmt = $pdo->prepare(
            "SELECT id, book_id, user_id, due_date, status
             FROM book_issues
             WHERE book_id = ? AND user_id = ? AND status IN ('issued', 'overdue')
             ORDER BY id DESC
             LIMIT 1"
        );

        $Stmt->execute([(int)$BookId, $UserId]);
    }

    $IssueRecord = $Stmt->fetch();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

if (!$IssueRecord) {
    notFoundResponse("Issue record not found");
}

if (!$IsAdmin && (int)$IssueRecord["user_id"] !== $UserId) {
    forbiddenResponse("You can only return your own issued books");
}

if ($IssueRecord["status"] === "returned") {
    errorResponse("This book has already been returned", 400);
}

$TargetIssueId = (int)$IssueRecord["id"];
$TargetBookId = (int)$IssueRecord["book_id"];
$TargetUserId = (int)$IssueRecord["user_id"];
$DueDate = $IssueRecord["due_date"];


// Check Overdue Fine

$CurrentDate = date("Y-m-d");
$FineAmount = 0.00;
$FineReason = null;

if (strtotime($CurrentDate) > strtotime($DueDate)) {
    $DaysOverdue = (int)floor((strtotime($CurrentDate) - strtotime($DueDate)) / 86400);

    if ($DaysOverdue > 0) {
        $FinePerDay = defined("FINE_RATE_PER_DAY") ? FINE_RATE_PER_DAY : 5.00;
        $FineAmount = (float)($DaysOverdue * $FinePerDay);
        $FineReason = "Late return: $DaysOverdue day(s) overdue";
    }
}


// Return Book Transaction

try {
    $pdo->beginTransaction();

    // Update Issue Record
    $Stmt = $pdo->prepare(
        "UPDATE book_issues
         SET returned_at = CURRENT_TIMESTAMP,
             status = 'returned'
         WHERE id = ?"
    );

    $Stmt->execute([$TargetIssueId]);

    // Increase Available Copies
    $Stmt = $pdo->prepare(
        "UPDATE books
         SET available_copies = available_copies + 1
         WHERE id = ?"
    );

    $Stmt->execute([$TargetBookId]);

    // Create Fine If Overdue (avoid duplicate fines)
    if ($FineAmount > 0) {
        $Stmt = $pdo->prepare(
            "SELECT id FROM fines WHERE issue_id = ? LIMIT 1"
        );
        $Stmt->execute([$TargetIssueId]);

        if (!$Stmt->fetch()) {
            $Stmt = $pdo->prepare(
                "INSERT INTO fines
                (issue_id, user_id, amount, reason, status)
                VALUES (?, ?, ?, ?, 'unpaid')"
            );

            $Stmt->execute([
                $TargetIssueId,
                $TargetUserId,
                $FineAmount,
                $FineReason
            ]);
        }
    }

    $pdo->commit();

    logAudit($UserId, "book_returned", "books", $TargetBookId, "Book returned for issue #$TargetIssueId");

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse("Unable to process book return", 500);
}


// Response

$ResponseData = [
    "issue_id" => $TargetIssueId,
    "book_id" => $TargetBookId,
    "user_id" => $TargetUserId,
    "status" => "returned",
    "returned_at" => currentTime()
];

if ($FineAmount > 0) {
    $ResponseData["fine"] = [
        "amount" => $FineAmount,
        "reason" => $FineReason,
        "status" => "unpaid"
    ];
}

successResponse(
    $ResponseData,
    "Book returned successfully"
);

?>
