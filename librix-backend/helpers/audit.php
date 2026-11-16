<?php

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

        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

?>
