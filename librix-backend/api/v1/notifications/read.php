<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if (!in_array($_SERVER["REQUEST_METHOD"], ["PUT", "POST"])) {
    MethodNotAllowedResponse(["PUT", "POST"]);
}


// Authentication

$User = RequireAuth();
$UserId = (int)$User["id"];


// Request Data

$Input = GetJsonInput();
$NotificationId = $Input["id"] ?? ($parts[3] ?? ($_GET["id"] ?? null));
$MarkAll = isset($Input["all"]) && $Input["all"] === true;


// Execute

try {
    if ($MarkAll) {
        $Stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $Stmt->execute([$UserId]);
        $Count = $Stmt->rowCount();

        SuccessResponse(["updated_count" => $Count], "All notifications marked as read");
    } else {
        $IdError = ValidatePositiveInteger($NotificationId, "Notification ID");
        if ($IdError !== null) {
            ErrorResponse($IdError, 400);
        }

        $NotificationId = (int)$NotificationId;

        $Stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $Stmt->execute([$NotificationId, $UserId]);

        if ($Stmt->rowCount() === 0) {
            // Check if it exists at all
            $Check = $pdo->prepare("SELECT id, is_read FROM notifications WHERE id = ? AND user_id = ?");
            $Check->execute([$NotificationId, $UserId]);
            $Row = $Check->fetch();
            if (!$Row) {
                NotFoundResponse("Notification not found");
            }
        }

        SuccessResponse(["id" => $NotificationId, "is_read" => true], "Notification marked as read");
    }

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
