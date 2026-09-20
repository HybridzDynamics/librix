<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication

$User = requireAuth();
$UserId = (int)$User["id"];


// Check if admin (admin can see all fines)

$IsAdmin = $User["role"] === "admin";


// Query Parameters

$Page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$Limit = isset($_GET["limit"]) ? min(100, max(1, (int)$_GET["limit"])) : 20;
$Offset = ($Page - 1) * $Limit;
$Status = $_GET["status"] ?? null;


// Build Query

$WhereClause = $IsAdmin ? "" : "WHERE f.user_id = ?";
$Params = $IsAdmin ? [] : [$UserId];

if ($Status && in_array($Status, ["unpaid", "paid", "waived"])) {
    $WhereClause .= ($WhereClause ? " AND " : "WHERE ") . "f.status = ?";
    $Params[] = $Status;
}


try {
    // Get total count
    $CountQuery = "SELECT COUNT(*) FROM fines f " . $WhereClause;
    $CountStmt = $pdo->prepare($CountQuery);
    $CountStmt->execute($Params);
    $Total = (int)$CountStmt->fetchColumn();

    // Get fines with details
    $Query = "
        SELECT 
            f.id,
            f.user_id,
            f.issue_id,
            f.amount,
            f.reason,
            f.status,
            f.paid_at,
            f.created_at,
            u.name AS user_name,
            u.email AS user_email,
            bi.book_id,
            b.title AS book_title,
            bi.issued_at,
            bi.due_date,
            bi.returned_at
        FROM fines f
        INNER JOIN users u ON f.user_id = u.id
        LEFT JOIN book_issues bi ON f.issue_id = bi.id
        LEFT JOIN books b ON bi.book_id = b.id
        $WhereClause
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $Params[] = $Limit;
    $Params[] = $Offset;
    
    $Stmt = $pdo->prepare($Query);
    $Stmt->execute($Params);
    $Fines = $Stmt->fetchAll();

    foreach ($Fines as &$Fine) {
        $Fine["id"] = (int)$Fine["id"];
        $Fine["user_id"] = (int)$Fine["user_id"];
        $Fine["issue_id"] = $Fine["issue_id"] ? (int)$Fine["issue_id"] : null;
        $Fine["amount"] = (float)$Fine["amount"];
        $Fine["book_id"] = $Fine["book_id"] ? (int)$Fine["book_id"] : null;
    }

    $TotalPages = $Limit > 0 ? (int)ceil($Total / $Limit) : 1;

    paginatedResponse($Fines, $Page, $Limit, $Total);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>