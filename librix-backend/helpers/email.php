<?php

// Email Notification Helper Functions

/**
 * Create an email notification record
 */
function createEmailNotification($UserId, $Subject, $Body) {
    global $pdo;
    
    try {
        $Stmt = $pdo->prepare(
            "INSERT INTO email_notifications (user_id, subject, body, status)
            VALUES (?, ?, ?, 'pending')"
        );
        $Stmt->execute([$UserId, $Subject, $Body]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Send librarian request approval notification
 */
function sendLibrarianApprovalNotification($UserId, $OrgName, $Status) {
    $Subject = "Librarian Request " . ucfirst($Status);
    
    if ($Status === 'approved') {
        $Body = "Congratulations! Your librarian request for {$OrgName} has been approved. You now have librarian privileges and can manage the library collection.";
    } else {
        $Body = "Your librarian request for {$OrgName} has been reviewed and unfortunately was not approved at this time. Please contact the administrator for more information.";
    }
    
    return createEmailNotification($UserId, $Subject, $Body);
}

/**
 * Send organization join approval notification
 */
function sendOrgJoinApprovalNotification($UserId, $OrgName, $Status) {
    $Subject = "Organization Join Request " . ucfirst($Status);
    
    if ($Status === 'approved') {
        $Body = "Great news! Your request to join {$OrgName} has been approved. You now have access to the organization's library collection and resources.";
    } else {
        $Body = "Your request to join {$OrgName} has been reviewed and was not approved at this time. Please contact the organization administrator for more information.";
    }
    
    return createEmailNotification($UserId, $Subject, $Body);
}

/**
 * Send book availability notification
 */
function sendBookAvailabilityNotification($UserId, $BookTitle) {
    $Subject = "Book Now Available: {$BookTitle}";
    $Body = "The book '{$BookTitle}' you reserved is now available for pickup. Please visit the library to borrow it within 48 hours.";
    
    return createEmailNotification($UserId, $Subject, $Body);
}

/**
 * Send due date reminder notification
 */
function sendDueDateReminder($UserId, $BookTitle, $DueDate) {
    $Subject = "Book Due Date Reminder";
    $Body = "This is a reminder that '{$BookTitle}' is due on {$DueDate}. Please return it to the library to avoid late fees.";
    
    return createEmailNotification($UserId, $Subject, $Body);
}

/**
 * Send overdue notification
 */
function sendOverdueNotification($UserId, $BookTitle, $DaysOverdue, $FineAmount) {
    $Subject = "Book Overdue Notice";
    $Body = "The book '{$BookTitle}' is {$DaysOverdue} days overdue. A fine of \${$FineAmount} has been applied to your account. Please return the book as soon as possible.";
    
    return createEmailNotification($UserId, $Subject, $Body);
}

/**
 * Mark email notification as sent
 */
function markEmailAsSent($NotificationId) {
    global $pdo;
    
    try {
        $Stmt = $pdo->prepare(
            "UPDATE email_notifications 
            SET status = 'sent', sent_at = CURRENT_TIMESTAMP 
            WHERE id = ?"
        );
        $Stmt->execute([$NotificationId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Mark email notification as failed
 */
function markEmailAsFailed($NotificationId, $ErrorMessage) {
    global $pdo;
    
    try {
        $Stmt = $pdo->prepare(
            "UPDATE email_notifications 
            SET status = 'failed', error_message = ? 
            WHERE id = ?"
        );
        $Stmt->execute([$ErrorMessage, $NotificationId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get pending email notifications
 */
function getPendingEmailNotifications($Limit = 50) {
    global $pdo;
    
    try {
        $Stmt = $pdo->prepare(
            "SELECT en.*, u.email, u.name 
            FROM email_notifications en
            INNER JOIN users u ON en.user_id = u.id
            WHERE en.status = 'pending'
            ORDER BY en.created_at ASC
            LIMIT ?"
        );
        $Stmt->bindValue(1, $Limit, PDO::PARAM_INT);
        $Stmt->execute();
        return $Stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

?>