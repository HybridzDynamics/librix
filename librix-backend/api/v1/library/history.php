<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication

$User = requireAuth();
$UserId = (int)$User["id"];


// Query Parameters

$Page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$Limit = isset($_GET["limit"]) ? min(100, max(1, (int)$_GET["limit"])) : 15;
$Offset = ($Page - 1) * $Limit;
$Status = $_GET["status"] ?? null;


// Query Construction

$WhereConditions = ["book_issues.user_id = ?"];
$Params = [$UserId];

if (!empty($Status) && in_array($Status, ["issued", "returned", "overdue"])) {
    $WhereConditions[] = "book_issues.status = ?";
    $Params[] = $Status;
}

$WhereSql = implode(" AND ", $WhereConditions);


// Count and Fetch

try {
    $CountStmt = $pdo->prepare("SELECT COUNT(book_issues.id) FROM book_issues WHERE {$WhereSql}");
    $CountStmt->execute($Params);
    $Total = (int)$CountStmt->fetchColumn();

    $Sql = "SELECT 
                book_issues.id AS issue_id,
                book_issues.book_id,
                books.title,
                books.isbn,
                books.category,
                books.cover_image,
                authors.name AS author_name,
                book_issues.issued_at,
                book_issues.due_date,
                book_issues.returned_at,
                book_issues.renewal_count,
                book_issues.status,
                fines.amount AS fine_amount,
                fines.status AS fine_status
            FROM book_issues
            INNER JOIN books ON book_issues.book_id = books.id
            LEFT JOIN authors ON books.author_id = authors.id
            LEFT JOIN fines ON book_issues.id = fines.issue_id
            WHERE {$WhereSql}
            ORDER BY book_issues.issued_at DESC
            LIMIT ? OFFSET ?";

    $Stmt = $pdo->prepare($Sql);
    $ParamIndex = 1;
    foreach ($Params as $Val) {
        $Stmt->bindValue($ParamIndex++, $Val);
    }
    $Stmt->bindValue($ParamIndex++, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue($ParamIndex++, $Offset, PDO::PARAM_INT);
    $Stmt->execute();

    $History = $Stmt->fetchAll();

    foreach ($History as &$Item) {
        $Item["issue_id"] = (int)$Item["issue_id"];
        $Item["book_id"] = (int)$Item["book_id"];
        $Item["renewal_count"] = (int)$Item["renewal_count"];
        $Item["fine_amount"] = $Item["fine_amount"] !== null ? (float)$Item["fine_amount"] : null;
    }

    paginatedResponse($History, $Page, $Limit, $Total);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
