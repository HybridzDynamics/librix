<?php

// Audit Log Helper Functions

/**
 * Log an action to the audit_logs table
 * 
 * @param PDO $pdo Database connection
 * @param int|null $userId User ID performing the action
 * @param int|null $orgId Organization ID
 * @param string $action Action performed (login, logout, create, update, delete, etc.)
 * @param string $entityType Type of entity (user, book, issue, fine, etc.)
 * @param int|null $entityId ID of the entity
 * @param string|null $description Description of the action
 * @param string|null $ipAddress IP address of the request
 * @return bool Success status
 */
function logAuditAction($pdo, $userId, $orgId, $action, $entityType, $entityId = null, $description = null, $ipAddress = null) {
    try {
        $sql = "INSERT INTO audit_logs (user_id, org_id, action, entity_type, entity_id, description, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $orgId,
            $action,
            $entityType,
            $entityId,
            $description,
            $ipAddress
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Log error but don't throw to avoid breaking main functionality
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

?>
