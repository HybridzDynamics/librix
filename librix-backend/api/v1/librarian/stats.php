<?php

// Librarian Org Statistics

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/librarian.php";

$User = requireAuth();
requireLibrarian($User);

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}

$OrgId = getOrgScope($User);

try {
    // Org Info
    $OrgInfo = null;
    if ($OrgId) {
        $OrgStmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ?");
        $OrgStmt->execute([$OrgId]);
        $OrgInfo = $OrgStmt->fetch();
    }

    // Books Count
    $BooksStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_titles,
            COALESCE(SUM(total_copies), 0) AS total_copies,
            COALESCE(SUM(available_copies), 0) AS available_copies
        FROM books
        WHERE (? IS NULL OR org_id = ? OR org_id IS NULL)
    ");
    $BooksStmt->execute([$OrgId, $OrgId]);
    $BookStats = $BooksStmt->fetch();

    // Active Loans
    $LoansStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS active_loans,
            SUM(CASE WHEN due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue_loans
        FROM book_issues
        WHERE status = 'issued'
          AND (? IS NULL OR org_id = ? OR org_id IS NULL)
    ");
    $LoansStmt->execute([$OrgId, $OrgId]);
    $LoanStats = $LoansStmt->fetch();

    // Active Reservations
    $ResStmt = $pdo->prepare("
        SELECT COUNT(*) AS active_reservations
        FROM reservations
        WHERE status = 'active'
          AND (? IS NULL OR org_id = ? OR org_id IS NULL)
    ");
    $ResStmt->execute([$OrgId, $OrgId]);
    $ResStats = $ResStmt->fetch();

    // Total Members
    $MemStmt = $pdo->prepare("
        SELECT COUNT(*) AS total_members
        FROM users
        WHERE (? IS NULL OR org_id = ?)
    ");
    $MemStmt->execute([$OrgId, $OrgId]);
    $MemStats = $MemStmt->fetch();

    successResponse([
        "organization" => $OrgInfo ?: [
            "id" => $OrgId,
            "name" => $User["org_name"] ?? "Active Organization",
            "code" => $User["org_code"] ?? "ORG-DEFAULT"
        ],
        "stats" => [
            "total_titles" => (int)$BookStats["total_titles"],
            "total_copies" => (int)$BookStats["total_copies"],
            "available_copies" => (int)$BookStats["available_copies"],
            "active_loans" => (int)$LoanStats["active_loans"],
            "overdue_loans" => (int)$LoanStats["overdue_loans"],
            "active_reservations" => (int)$ResStats["active_reservations"],
            "total_members" => (int)$MemStats["total_members"]
        ]
    ]);

} catch (PDOException $e) {
    errorResponse("Database error: " . $e->getMessage(), 500);
}

?>
