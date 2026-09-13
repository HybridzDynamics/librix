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


// Update User (Status / Role)

if ($Method === "PUT" || $Method === "PATCH") {

    $RequestData = GetJsonInput();

    $TargetUserId = $parts[4] ?? ($RequestData["user_id"] ?? null);

    $IdError = ValidatePositiveInteger($TargetUserId, "User ID");

    if ($IdError !== null) {
        ErrorResponse($IdError, 400);
    }

    $TargetUserId = (int)$TargetUserId;

    // Check Target User Exists
    try {
        $Stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1");
        $Stmt->execute([$TargetUserId]);
        $ExistingTarget = $Stmt->fetch();

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    if (!$ExistingTarget) {
        NotFoundResponse("User not found");
    }

    $NewStatus = $RequestData["status"] ?? $ExistingTarget["status"];
    $NewRole = $RequestData["role"] ?? $ExistingTarget["role"];

    $ValidStatuses = ["active", "inactive", "suspended"];
    $ValidRoles = ["user", "admin"];

    if (!in_array($NewStatus, $ValidStatuses, true)) {
        ErrorResponse("Invalid status value. Allowed: " . implode(", ", $ValidStatuses), 422);
    }

    if (!in_array($NewRole, $ValidRoles, true)) {
        ErrorResponse("Invalid role value. Allowed: " . implode(", ", $ValidRoles), 422);
    }

    // Prevent Admin from suspending/deactivating self
    if ($TargetUserId === (int)$AdminUser["id"] && $NewStatus !== "active") {
        ErrorResponse("You cannot deactivate or suspend your own account", 400);
    }

    try {
        $Stmt = $pdo->prepare(
            "UPDATE users
             SET status = ?,
                 role = ?
             WHERE id = ?"
        );

        $Stmt->execute([
            $NewStatus,
            $NewRole,
            $TargetUserId
        ]);

        // If user suspended or deactivated, revoke all their active sessions
        if ($NewStatus !== "active") {
            $Stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE user_id = ?");
            $Stmt->execute([$TargetUserId]);
        }

        LogAudit(
            (int)$AdminUser["id"],
            "user_status_changed",
            "users",
            $TargetUserId,
            "User #$TargetUserId status updated to $NewStatus, role to $NewRole"
        );

    } catch (PDOException $e) {
        ErrorResponse("Unable to update user status", 500);
    }

    // Fetch Fresh User
    try {
        $Stmt = $pdo->prepare("SELECT id, name, email, role, status, email_verified_at, created_at, updated_at FROM users WHERE id = ? LIMIT 1");
        $Stmt->execute([$TargetUserId]);
        $UpdatedUser = $Stmt->fetch();
        $UpdatedUser["id"] = (int)$UpdatedUser["id"];

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    SuccessResponse($UpdatedUser, "User updated successfully");
}


// List Users (GET)

if ($Method === "GET") {

    $Page = ValidatePageNumber($_GET["page"] ?? 1);
    $Limit = ValidatePageLimit($_GET["limit"] ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
    $Offset = ($Page - 1) * $Limit;

    $StatusFilter = isset($_GET["status"]) ? trim($_GET["status"]) : null;
    $RoleFilter = isset($_GET["role"]) ? trim($_GET["role"]) : null;
    $Search = isset($_GET["search"]) ? trim($_GET["search"]) : null;

    $WhereClauses = [];
    $Bindings = [];

    if ($StatusFilter !== null && $StatusFilter !== "") {
        $WhereClauses[] = "status = ?";
        $Bindings[] = $StatusFilter;
    }

    if ($RoleFilter !== null && $RoleFilter !== "") {
        $WhereClauses[] = "role = ?";
        $Bindings[] = $RoleFilter;
    }

    if ($Search !== null && $Search !== "") {
        $WhereClauses[] = "(name LIKE ? OR email LIKE ?)";
        $SearchTerm = "%" . $Search . "%";
        $Bindings[] = $SearchTerm;
        $Bindings[] = $SearchTerm;
    }

    $WhereSql = !empty($WhereClauses) ? "WHERE " . implode(" AND ", $WhereClauses) : "";

    // Count Total
    try {
        $CountStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM users $WhereSql");
        $CountStmt->execute($Bindings);
        $TotalRow = $CountStmt->fetch();
        $Total = (int)($TotalRow["total"] ?? 0);

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    // Fetch Users
    try {
        $DataSql = "SELECT
                        id,
                        name,
                        email,
                        role,
                        status,
                        email_verified_at,
                        created_at,
                        updated_at
                    FROM users
                    $WhereSql
                    ORDER BY id ASC
                    LIMIT ? OFFSET ?";

        $Stmt = $pdo->prepare($DataSql);

        $ParamIndex = 1;
        foreach ($Bindings as $Binding) {
            $Stmt->bindValue($ParamIndex++, $Binding);
        }
        $Stmt->bindValue($ParamIndex++, (int)$Limit, PDO::PARAM_INT);
        $Stmt->bindValue($ParamIndex++, (int)$Offset, PDO::PARAM_INT);

        $Stmt->execute();

        $Users = $Stmt->fetchAll();

        foreach ($Users as &$Item) {
            $Item["id"] = (int)$Item["id"];
        }

    } catch (PDOException $e) {
        ErrorResponse("Database error", 500);
    }

    PaginatedResponse(
        $Users,
        $Page,
        $Limit,
        $Total
    );
}

MethodNotAllowedResponse(["GET", "PUT", "PATCH"]);

?>
