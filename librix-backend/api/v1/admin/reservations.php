<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";
require __DIR__ . "/../../../middleware/auth.php";
require __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication

$User = requireAuth();
requireAdmin($User);


// Parameters

$Page = validatePageNumber($_GET["page"] ?? 1);
$Limit = validatePageLimit($_GET["limit"] ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
$Offset = ($Page - 1) * $Limit;

$Status = isset($_GET["status"]) ? trim($_GET["status"]) : null;

$WhereClauses = [];
$Bindings = [];

if ($Status !== null && $Status !== "") {
    $WhereClauses[] = "reservations.status = ?";
    $Bindings[] = $Status;
}

$WhereSql = !empty($WhereClauses) ? "WHERE " . implode(" AND ", $WhereClauses) : "";


// Total Count

try {
    $CountSql = "SELECT COUNT(*) AS total FROM reservations $WhereSql";
    $CountStmt = $pdo->prepare($CountSql);
    $CountStmt->execute($Bindings);
    $Total = (int)($CountStmt->fetchColumn() ?: 0);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Fetch Reservations

try {
    $DataSql = "SELECT
                    reservations.id,
                    reservations.book_id,
                    books.title AS book_title,
                    books.isbn,
                    reservations.user_id,
                    users.name AS user_name,
                    users.email AS user_email,
                    reservations.reserved_at,
                    reservations.status
                FROM reservations
                INNER JOIN books ON reservations.book_id = books.id
                INNER JOIN users ON reservations.user_id = users.id
                $WhereSql
                ORDER BY reservations.id DESC
                LIMIT ? OFFSET ?";

    $Stmt = $pdo->prepare($DataSql);

    $ParamIndex = 1;
    foreach ($Bindings as $Binding) {
        $Stmt->bindValue($ParamIndex++, $Binding);
    }
    $Stmt->bindValue($ParamIndex++, (int)$Limit, PDO::PARAM_INT);
    $Stmt->bindValue($ParamIndex++, (int)$Offset, PDO::PARAM_INT);

    $Stmt->execute();

    $Reservations = $Stmt->fetchAll();

    foreach ($Reservations as &$Item) {
        $Item["id"] = (int)$Item["id"];
        $Item["book_id"] = (int)$Item["book_id"];
        $Item["user_id"] = (int)$Item["user_id"];
    }

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Response

paginatedResponse(
    $Reservations,
    $Page,
    $Limit,
    $Total
);

?>
