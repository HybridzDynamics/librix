<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/functions.php";
require __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Authentication

$User = requireAuth();
$UserId = (int)$User["id"];


// Pagination

$Page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$Limit = isset($_GET["limit"]) ? min(100, max(1, (int)$_GET["limit"])) : 20;
$Offset = ($Page - 1) * $Limit;
$UnreadOnly = isset($_GET["unread"]) && ($_GET["unread"] === "1" || $_GET["unread"] === "true");


// Query Notifications

try {
    // Unread count
    $UnreadStmt = $pdo->prepare("SELECT COUNT(id) FROM notifications WHERE user_id = ? AND is_read = 0");
    $UnreadStmt->execute([$UserId]);
    $UnreadCount = (int)$UnreadStmt->fetchColumn();

    // Total count
    $Where = "WHERE user_id = ?" . ($UnreadOnly ? " AND is_read = 0" : "");
    $CountStmt = $pdo->prepare("SELECT COUNT(id) FROM notifications {$Where}");
    $CountStmt->execute([$UserId]);
    $Total = (int)$CountStmt->fetchColumn();

    // Items
    $Stmt = $pdo->prepare(
        "SELECT 
            id,
            user_id,
            title,
            message,
            type,
            is_read,
            created_at
         FROM notifications
         {$Where}
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );
    $Stmt->bindValue(1, $UserId, PDO::PARAM_INT);
    $Stmt->bindValue(2, $Limit, PDO::PARAM_INT);
    $Stmt->bindValue(3, $Offset, PDO::PARAM_INT);
    $Stmt->execute();

    $Notifications = $Stmt->fetchAll();

    foreach ($Notifications as &$Item) {
        $Item["id"] = (int)$Item["id"];
        $Item["user_id"] = (int)$Item["user_id"];
        $Item["is_read"] = (bool)$Item["is_read"];
    }

    $TotalPages = $Limit > 0 ? (int)ceil($Total / $Limit) : 1;

    successResponse([
        "notifications" => $Notifications,
        "unread_count" => $UnreadCount,
        "pagination" => [
            "page" => $Page,
            "limit" => $Limit,
            "total" => $Total,
            "total_pages" => $TotalPages
        ]
    ]);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
