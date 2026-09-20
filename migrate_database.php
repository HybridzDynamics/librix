<?php
// Database Migration Script
// Add new columns and tables for enhanced features

require_once 'librix-backend/config/database.php';

echo "Starting database migration...\n";

try {
    // Add profile picture and extended fields to users table
    echo "Adding profile picture and extended fields to users table...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture_url VARCHAR(500) NULL AFTER email_verified_at");
    } catch (PDOException $e) {
        echo "  - profile_picture_url column already exists\n";
    }
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER profile_picture_url");
    } catch (PDOException $e) {
        echo "  - phone column already exists\n";
    }
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT NULL AFTER phone");
    } catch (PDOException $e) {
        echo "  - address column already exists\n";
    }
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER address");
    } catch (PDOException $e) {
        echo "  - bio column already exists\n";
    }
    echo "✓ User table updated\n";

    // Create email notifications table
    echo "Creating email notifications table...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
            sent_at TIMESTAMP NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_email_notifications_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE,
                
            INDEX idx_email_notifications_status (status),
            INDEX idx_email_notifications_user (user_id)
        )");
        echo "✓ Email notifications table created\n";
    } catch (PDOException $e) {
        echo "  - email notifications table already exists\n";
    }

    // Create fine payments table
    echo "Creating fine payments table...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS fine_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            issue_id INT UNSIGNED NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NULL,
            payment_reference VARCHAR(100) NULL,
            status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_fine_payments_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE,
                
            CONSTRAINT fk_fine_payments_issue
                FOREIGN KEY (issue_id)
                REFERENCES book_issues(id)
                ON DELETE SET NULL,
                
            INDEX idx_fine_payments_user (user_id),
            INDEX idx_fine_payments_status (status)
        )");
        echo "✓ Fine payments table created\n";
    } catch (PDOException $e) {
        echo "  - fine payments table already exists\n";
    }

    // Create file uploads table
    echo "Creating file uploads table...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS file_uploads (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            org_id INT UNSIGNED NULL,
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT UNSIGNED NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            upload_type ENUM('profile_picture', 'org_logo', 'book_cover', 'other') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_file_uploads_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE SET NULL,
                
            CONSTRAINT fk_file_uploads_org
                FOREIGN KEY (org_id)
                REFERENCES organizations(id)
                ON DELETE SET NULL,
                
            INDEX idx_file_uploads_user (user_id),
            INDEX idx_file_uploads_org (org_id),
            INDEX idx_file_uploads_type (upload_type)
        )");
        echo "✓ File uploads table created\n";
    } catch (PDOException $e) {
        echo "  - file uploads table already exists\n";
    }

    // Create book recommendations table
    echo "Creating book recommendations table...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS book_recommendations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            book_id INT UNSIGNED NOT NULL,
            reason VARCHAR(255) NULL,
            score DECIMAL(3,2) NOT NULL DEFAULT 0.00,
            viewed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_recommendations_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE,
                
            CONSTRAINT fk_recommendations_book
                FOREIGN KEY (book_id)
                REFERENCES books(id)
                ON DELETE CASCADE,
                
            UNIQUE KEY unique_user_book (user_id, book_id),
            INDEX idx_recommendations_user (user_id),
            INDEX idx_recommendations_score (score)
        )");
        echo "✓ Book recommendations table created\n";
    } catch (PDOException $e) {
        echo "  - book recommendations table already exists\n";
    }

    // Create uploads directory
    echo "Creating uploads directory...\n";
    $uploadsDir = __DIR__ . '/librix-backend/uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        mkdir($uploadsDir . '/profile-pictures', 0755, true);
        mkdir($uploadsDir . '/org-logos', 0755, true);
        mkdir($uploadsDir . '/book-covers', 0755, true);
        echo "✓ Uploads directories created\n";
    } else {
        echo "✓ Uploads directories already exist\n";
    }

    echo "\n✅ Database migration completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>