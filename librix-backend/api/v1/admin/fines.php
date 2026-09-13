<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Authentication

$AdminUser = RequireAuth();
RequireAdmin($AdminUser);


// Request Method

$Method = $_SERVER["REQUEST_METHOD"];


// Update Fine Status (PUT/PATCH)

if ($Method === "PUT" || $Method === "PATCH") {

    $RequestData = GetJsonInput();

    $FineId = $parts[4] ?? ($RequestData["fine_id"] ?? null);

    $IdError = ValidatePositiveInteger($FineId, "Fine ID");

    if ($IdError !== null) {
        ErrorResponse($IdError, 400);
    }

    $FineId = (int)$FineId;

    $NewStatus = trim($RequestData["status"] ?? "");
    $AllowedStatuses = ["unpaid", "paid", "waived"];

    if (!in_array($NewStatus, $AllowedStatuses, true)) {
        ErrorResponse("Invalid fine status. Allowed: " . implode(", ", $AllowedStatuses), 422);
    }

    // Verify Fine Exists
    try {
        $Stmt = $pdo->prepare("SELECT id, user_id, amount, status FROM fines WHERE id = ? LIMIT 1");
        $Stmt->execute([$FineId]);
        $ExistingFine = $Stmt->fetch();

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    if (!$ExistingFine) {
        NotFoundResponse("Fine not found");
    }

    $PaidAt = ($NewStatus === "paid") ? date("Y-m-d H:i:s") : null;

    try {
        $Stmt = $pdo->prepare(
            "UPDATE fines
             SET status = ?,
                 paid_at = ?
             WHERE id = ?"
        );

        $Stmt->execute([
            $NewStatus,
            $PaidAt,
            $FineId
        ]);

        LogAudit(
            (int)$AdminUser["id"],
            "fine_status_updated",
            "fines",
            $FineId,
            "Fine #$FineId status changed to $NewStatus (Amount: {$ExistingFine['amount']})"
        );

    } catch (PDOException $e) {
        ErrorResponse("Unable to update fine status", 500);
    }

    SuccessResponse([
        "id" => $FineId,
        "status" => $NewStatus,
        "paid_at" => $PaidAt
    ], "Fine updated successfully");
}


// List Fines (GET)

if ($Method === "GET") {

    $Page = ValidatePageNumber($_GET["page"] ?? 1);
    $Limit = ValidatePageLimit($_GET["limit"] ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
    $Offset = ($Page - 1) * $Limit;

    $Status = isset($_GET["status"]) ? trim($_GET["status"]) : null;

    $WhereClauses = [];
    $Bindings = [];

    if ($Status !== null && $Status !== "") {
        $WhereClauses[] = "fines.status = ?";
        $Bindings[] = $Status;
    }

    $WhereSql = !empty($WhereClauses) ? "WHERE " . implode(" AND ", $WhereClauses) : "";

    // Count Total
    try {
        $CountStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM fines $WhereSql");
        $CountStmt->execute($Bindings);
        $Total = (int)($CountStmt->fetchColumn() ?: 0);

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    // Fetch Fines
    try {
        $DataSql = "SELECT
                        fines.id,
                        fines.issue_id,
                        fines.user_id,
                        users.name AS user_name,
                        users.email AS user_email,
                        books.title AS book_title,
                        fines.amount,
                        fines.reason,
                        fines.status,
                        fines.created_at,
                        fines.paid_at
                    FROM fines
                    INNER JOIN users ON fines.user_id = users.id
                    INNER JOIN book_issues ON fines.issue_id = book_issues.id
                    INNER JOIN books ON book_issues.book_id = books.id
                    $WhereSql
                    ORDER BY fines.id DESC
                    LIMIT ? OFFSET ?";

        $Stmt = $pdo->prepare($DataSql);

        $ParamIndex = 1;
        foreach ($Bindings as $Binding) {
            $Stmt->bindValue($ParamIndex++, $Binding);
        }
        $Stmt->bindValue($ParamIndex++, (int)$Limit, PDO::PARAM_INT);
        $Stmt->bindValue($ParamIndex++, (int)$Offset, PDO::PARAM_INT);

        $Stmt->execute();

        $Fines = $Stmt->fetchAll();

        foreach ($Fines as &$Item) {
            $Item["id"] = (int)$Item["id"];
            $Item["issue_id"] = (int)$Item["issue_id"];
            $Item["user_id"] = (int)$Item["user_id"];
            $Item["amount"] = (float)$Item["amount"];
        }

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    PaginatedResponse(
        $Fines,
        $Page,
        $Limit,
        $Total
    );
}

MethodNotAllowedResponse(["GET", "PUT", "PATCH"]);

?>
