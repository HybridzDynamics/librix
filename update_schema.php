<?php

// Update Database Schema with new tables

require_once __DIR__ . "/librix-backend/config/database.php";

echo "Updating database schema...\n";

try {
    // Create tags table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Created tags table\n";

    // Create book_tags table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS book_tags (
            book_id INT UNSIGNED NOT NULL,
            tag_id INT UNSIGNED NOT NULL,
            count INT UNSIGNED NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (book_id, tag_id),
            
            CONSTRAINT fk_book_tags_book
                FOREIGN KEY (book_id)
                REFERENCES books(id)
                ON DELETE CASCADE,
                
            CONSTRAINT fk_book_tags_tag
                FOREIGN KEY (tag_id)
                REFERENCES tags(id)
                ON DELETE CASCADE
        )
    ");
    echo "✓ Created book_tags table\n";

    // Create org_join_requests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS org_join_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            org_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            message TEXT NULL,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_org_requests_org
                FOREIGN KEY (org_id)
                REFERENCES organizations(id)
                ON DELETE CASCADE,
                
            CONSTRAINT fk_org_requests_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE
        )
    ");
    echo "✓ Created org_join_requests table\n";

    // Create librarian_requests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS librarian_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            org_id INT UNSIGNED NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            library_name VARCHAR(150) NOT NULL,
            library_address TEXT NULL,
            library_phone VARCHAR(50) NULL,
            message TEXT NULL,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            admin_notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_librarian_requests_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE,
                
            CONSTRAINT fk_librarian_requests_org
                FOREIGN KEY (org_id)
                REFERENCES organizations(id)
                ON DELETE SET NULL
        )
    ");
    echo "✓ Created librarian_requests table\n";

    // Create audit_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            org_id INT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT UNSIGNED NULL,
            description TEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_audit_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE SET NULL,
                
            CONSTRAINT fk_audit_org
                FOREIGN KEY (org_id)
                REFERENCES organizations(id)
                ON DELETE SET NULL
        )
    ");
    echo "✓ Created audit_logs table\n";

    // Create rate_limits table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rate_key VARCHAR(255) NOT NULL UNIQUE,
            requests INT UNSIGNED NOT NULL DEFAULT 1,
            reset_at INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Created rate_limits table\n";

    // Update users table to change default role
    $pdo->exec("
        ALTER TABLE users 
        MODIFY COLUMN role ENUM('user', 'librarian', 'admin') NOT NULL DEFAULT 'user'
    ");
    echo "✓ Updated users table default role\n";

    // Add default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = 'admin@librix.com'");
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result['count'] == 0) {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES ('System Admin', 'admin@librix.com', ?, 'admin', 'active')
        ");
        $stmt->execute([$passwordHash]);
        echo "✓ Created default admin user (admin@librix.com / admin123)\n";
    } else {
        echo "ℹ Admin user already exists\n";
    }

    echo "\n✅ Database schema updated successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error updating schema: " . $e->getMessage() . "\n";
    exit(1);
}

?>