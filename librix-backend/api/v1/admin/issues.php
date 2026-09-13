<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Authentication

$User = RequireAuth();
RequireAdmin($User);


// Parameters

$Page = ValidatePageNumber($_GET["page"] ?? 1);
$Limit = ValidatePageLimit($_GET["limit"] ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
$Offset = ($Page - 1) * $Limit;

$Status = isset($_GET["status"]) ? trim($_GET["status"]) : null;
$UserId = isset($_GET["user_id"]) ? $_GET["user_id"] : null;
$BookId = isset($_GET["book_id"]) ? $_GET["book_id"] : null;

$WhereClauses = [];
$Bindings = [];

if ($Status !== null && $Status !== "") {
    if ($Status === "overdue") {
        $WhereClauses[] = "(book_issues.status = 'overdue' OR (book_issues.status = 'issued' AND book_issues.due_date < CURRENT_DATE))";
    } else {
        $WhereClauses[] = "book_issues.status = ?";
        $Bindings[] = $Status;
    }
}

if ($UserId !== null && $UserId !== "") {
    $WhereClauses[] = "book_issues.user_id = ?";
    $Bindings[] = (int)$UserId;
}

if ($BookId !== null && $BookId !== "") {
    $WhereClauses[] = "book_issues.book_id = ?";
    $Bindings[] = (int)$BookId;
}

$WhereSql = !empty($WhereClauses) ? "WHERE " . implode(" AND ", $WhereClauses) : "";


// Total Count

try {
    $CountSql = "SELECT COUNT(*) AS total FROM book_issues $WhereSql";
    $CountStmt = $pdo->prepare($CountSql);
    $CountStmt->execute($Bindings);
    $Total = (int)($CountStmt->fetchColumn() ?: 0);

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Fetch Issues

try {
    $DataSql = "SELECT
                    book_issues.id,
                    book_issues.book_id,
                    books.title AS book_title,
                    books.isbn,
                    book_issues.user_id,
                    users.name AS user_name,
                    users.email AS user_email,
                    book_issues.issued_at,
                    book_issues.due_date,
                    book_issues.returned_at,
                    book_issues.status,
                    CASE
                        WHEN book_issues.status = 'issued' AND book_issues.due_date < CURRENT_DATE THEN 1
                        ELSE 0
                    END AS is_overdue
                FROM book_issues
                INNER JOIN books ON book_issues.book_id = books.id
                INNER JOIN users ON book_issues.user_id = users.id
                $WhereSql
                ORDER BY book_issues.id DESC
                LIMIT ? OFFSET ?";

    $Stmt = $pdo->prepare($DataSql);

    $ParamIndex = 1;
    foreach ($Bindings as $Binding) {
        $Stmt->bindValue($ParamIndex++, $Binding);
    }
    $Stmt->bindValue($ParamIndex++, (int)$Limit, PDO::PARAM_INT);
    $Stmt->bindValue($ParamIndex++, (int)$Offset, PDO::PARAM_INT);

    $Stmt->execute();

    $Issues = $Stmt->fetchAll();

    foreach ($Issues as &$Item) {
        $Item["id"] = (int)$Item["id"];
        $Item["book_id"] = (int)$Item["book_id"];
        $Item["user_id"] = (int)$Item["user_id"];
        $Item["is_overdue"] = (bool)$Item["is_overdue"];
    }

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Response

PaginatedResponse(
    $Issues,
    $Page,
    $Limit,
    $Total
);

?>
