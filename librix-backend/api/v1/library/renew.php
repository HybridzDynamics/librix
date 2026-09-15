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
$UserId = (int)$User["id"];
$IsAdmin = ($User["role"] === "admin");


// Request Data

$Input = getJsonInput();
$IssueId = $Input["issue_id"] ?? null;

$IdError = validatePositiveInteger($IssueId, "Issue ID");
if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$IssueId = (int)$IssueId;


// Find Book Issue

try {
    $Stmt = $pdo->prepare(
        "SELECT 
            book_issues.id,
            book_issues.book_id,
            book_issues.user_id,
            book_issues.due_date,
            book_issues.renewal_count,
            book_issues.max_renewals,
            book_issues.status,
            books.title AS book_title
         FROM book_issues
         INNER JOIN books ON book_issues.book_id = books.id
         WHERE book_issues.id = ?
         LIMIT 1"
    );
    $Stmt->execute([$IssueId]);
    $Issue = $Stmt->fetch();

    if (!$Issue) {
        notFoundResponse("Book issue not found");
    }

    // Permission check
    if (!$IsAdmin && (int)$Issue["user_id"] !== $UserId) {
        forbiddenResponse("You can only renew books issued to your account");
    }

    // Status check
    if ($Issue["status"] === "returned") {
        errorResponse("Cannot renew a book that has already been returned", 400);
    }

    $RenewalCount = (int)$Issue["renewal_count"];
    $MaxRenewals = (int)$Issue["max_renewals"];

    if ($RenewalCount >= $MaxRenewals) {
        errorResponse("Maximum number of renewals ({$MaxRenewals}) reached for this book", 400);
    }

    // Calculate new due date (14 days from current due date or today if already overdue)
    $CurrentDueDate = strtotime($Issue["due_date"]);
    $BaseTime = ($CurrentDueDate > time()) ? $CurrentDueDate : time();
    $NewDueDate = date("Y-m-d", strtotime("+14 days", $BaseTime));
    $NewRenewalCount = $RenewalCount + 1;

    // Update Issue
    $UpdateStmt = $pdo->prepare(
        "UPDATE book_issues 
         SET due_date = ?, renewal_count = ?, status = 'issued' 
         WHERE id = ?"
    );
    $UpdateStmt->execute([$NewDueDate, $NewRenewalCount, $IssueId]);

    // Send Notification
    $NotifStmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, title, message, type)
         VALUES (?, ?, ?, 'success')"
    );
    $NotifStmt->execute([
        $Issue["user_id"],
        "Book Renewed",
        "Your borrowing of '{$Issue['book_title']}' has been renewed until {$NewDueDate}."
    ]);

    logAudit($UserId, "renew_book", "book_issues", $IssueId, "Renewed book issue #{$IssueId} until {$NewDueDate}");

    successResponse([
        "issue_id" => $IssueId,
        "book_title" => $Issue["book_title"],
        "new_due_date" => $NewDueDate,
        "renewal_count" => $NewRenewalCount,
        "renewals_remaining" => $MaxRenewals - $NewRenewalCount
    ], "Book successfully renewed until " . $NewDueDate);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
