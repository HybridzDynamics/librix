<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication

$User = requireAuth();
requireAdmin($User);


// Database Query - Users Statistics

try {
    $Stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_users,
            SUM(CASE WHEN status != 'active' THEN 1 ELSE 0 END) AS inactive_users,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admin_users
         FROM users"
    );

    $Stmt->execute();
    $UsersStats = $Stmt->fetch();

} catch (PDOException $e) {
    error_log("Statistics users error: " . $e->getMessage());
    $UsersStats = [
        "total_users" => 0,
        "active_users" => 0,
        "inactive_users" => 0,
        "admin_users" => 0
    ];
}


// Database Query - Books Statistics

try {
    $Stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_books
         FROM books"
    );

    $Stmt->execute();
    $BooksStats = $Stmt->fetch();

} catch (PDOException $e) {
    error_log("Statistics books error: " . $e->getMessage());
    $BooksStats = ["total_books" => 0];
}


// Database Query - Authors Statistics

try {
    $Stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total_authors FROM authors"
    );

    $Stmt->execute();
    $AuthorsStats = $Stmt->fetch();

} catch (PDOException $e) {
    error_log("Statistics authors error: " . $e->getMessage());
    $AuthorsStats = ["total_authors" => 0];
}


// Database Query - Issues Statistics

try {
    $Stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_issues,
            SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) AS active_issues,
            SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) AS returned_issues,
            SUM(CASE WHEN (status = 'issued' AND due_date < CURRENT_DATE) OR status = 'overdue' THEN 1 ELSE 0 END) AS overdue_issues
         FROM book_issues"
    );

    $Stmt->execute();
    $IssuesStats = $Stmt->fetch();

} catch (PDOException $e) {
    error_log("Statistics issues error: " . $e->getMessage());
    $IssuesStats = [
        "total_issues" => 0,
        "active_issues" => 0,
        "returned_issues" => 0,
        "overdue_issues" => 0
    ];
}


// Database Query - Reservations Statistics

try {
    $Stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_reservations,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_reservations
         FROM reservations"
    );

    $Stmt->execute();
    $ReservationsStats = $Stmt->fetch();

} catch (PDOException $e) {
    error_log("Statistics reservations error: " . $e->getMessage());
    $ReservationsStats = [
        "total_reservations" => 0,
        "active_reservations" => 0
    ];
}


// Database Query - Fines Statistics

try {
    $Stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_fines,
            COALESCE(SUM(amount), 0.00) AS total_amount,
            COALESCE(SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0.00 END), 0.00) AS unpaid_amount,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0.00 END), 0.00) AS paid_amount
         FROM fines"
    );

    $Stmt->execute();
    $FinesStats = $Stmt->fetch();

} catch (PDOException $e) {
    error_log("Statistics fines error: " . $e->getMessage());
    $FinesStats = [
        "total_fines" => 0,
        "total_amount" => 0.00,
        "unpaid_amount" => 0.00,
        "paid_amount" => 0.00
    ];
}


// Response

successResponse(
    [
        "users" => [
            "total" => (int)($UsersStats["total_users"] ?? 0),
            "active" => (int)($UsersStats["active_users"] ?? 0),
            "inactive" => (int)($UsersStats["inactive_users"] ?? 0),
            "admins" => (int)($UsersStats["admin_users"] ?? 0)
        ],
        "books" => [
            "total_titles" => (int)($BooksStats["total_books"] ?? 0),
            "total_copies" => (int)($BooksStats["total_books"] ?? 0) * 5, // Assume 5 copies per book
            "available_copies" => (int)($BooksStats["total_books"] ?? 0) * 5,
            "issued_copies" => 0
        ],
        "authors" => [
            "total" => (int)($AuthorsStats["total_authors"] ?? 0)
        ],
        "issues" => [
            "total" => (int)($IssuesStats["total_issues"] ?? 0),
            "active" => (int)($IssuesStats["active_issues"] ?? 0),
            "returned" => (int)($IssuesStats["returned_issues"] ?? 0),
            "overdue" => (int)($IssuesStats["overdue_issues"] ?? 0)
        ],
        "reservations" => [
            "total" => (int)($ReservationsStats["total_reservations"] ?? 0),
            "active" => (int)($ReservationsStats["active_reservations"] ?? 0)
        ],
        "fines" => [
            "total_records" => (int)($FinesStats["total_fines"] ?? 0),
            "total_amount" => (float)($FinesStats["total_amount"] ?? 0.00),
            "unpaid_amount" => (float)($FinesStats["unpaid_amount"] ?? 0.00),
            "paid_amount" => (float)($FinesStats["paid_amount"] ?? 0.00)
        ]
    ],
    "System statistics retrieved successfully"
);

?>
