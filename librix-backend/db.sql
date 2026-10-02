-- LibriX Database
-- Multi-Tenant Architecture & Role-Based Access Control (RBAC)

CREATE DATABASE IF NOT EXISTS librix
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE librix;


-- -------------------------------------------------------------
-- 1. Organizations (Multi-Tenancy)
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    contact_email VARCHAR(255) NULL,
    logo_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_org_code (code)
);


-- -------------------------------------------------------------
-- 2. Users & RBAC
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id INT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'librarian', 'admin') NOT NULL DEFAULT 'admin',
    status ENUM('active', 'inactive', 'suspended', 'pending') NOT NULL DEFAULT 'pending',
    email_verified_at TIMESTAMP NULL,
    profile_picture_url VARCHAR(500) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    bio TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_org
        FOREIGN KEY (org_id)
        REFERENCES organizations(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_org (org_id)
);


-- -------------------------------------------------------------
-- 3. Authors
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS authors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    biography TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- -------------------------------------------------------------
-- 4. Book Categories
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- -------------------------------------------------------------
-- 5. Publishers
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS publishers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    address TEXT NULL,
    website VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- -------------------------------------------------------------
-- 6. Books (Tied to Organizations)
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id INT UNSIGNED NULL,
    author_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    publisher_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    language VARCHAR(50) NULL DEFAULT 'English',
    publisher VARCHAR(150) NULL,
    publication_year YEAR NULL,
    total_copies INT UNSIGNED NOT NULL DEFAULT 1,
    available_copies INT UNSIGNED NOT NULL DEFAULT 1,
    cover_image VARCHAR(500) NULL,
    average_rating DECIMAL(3,2) NULL DEFAULT 0.00,
    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    content LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_books_org
        FOREIGN KEY (org_id)
        REFERENCES organizations(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_books_author
        FOREIGN KEY (author_id)
        REFERENCES authors(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_books_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_books_publisher
        FOREIGN KEY (publisher_id)
        REFERENCES publishers(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_books_org (org_id),
    INDEX idx_books_category (category_id),
    INDEX idx_books_publisher (publisher_id),
    INDEX idx_books_title (title),
    INDEX idx_books_isbn (isbn)
);


-- -------------------------------------------------------------
-- 7. Book Issues (Circulation)
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS book_issues (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id INT UNSIGNED NULL,
    book_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NOT NULL,
    returned_at TIMESTAMP NULL,
    renewal_count INT UNSIGNED NOT NULL DEFAULT 0,
    max_renewals INT UNSIGNED NOT NULL DEFAULT 2,
    status ENUM('issued', 'returned', 'overdue') NOT NULL DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_issues_org
        FOREIGN KEY (org_id)
        REFERENCES organizations(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_issues_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_issues_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_issues_org (org_id),
    INDEX idx_issues_user (user_id),
    INDEX idx_issues_book (book_id),
    INDEX idx_issues_status (status)
);


-- -------------------------------------------------------------
-- 8. Book Reservations
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id INT UNSIGNED NULL,
    book_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'active',
    fulfilled_at TIMESTAMP NULL,
    notified_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,

    CONSTRAINT fk_reservations_org
        FOREIGN KEY (org_id)
        REFERENCES organizations(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_reservations_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_reservations_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_reservations_org (org_id),
    INDEX idx_reservations_user (user_id),
    INDEX idx_reservations_book (book_id),
    INDEX idx_reservations_status (status)
);


-- -------------------------------------------------------------
-- 9. Fines
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS fines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    issue_id INT UNSIGNED NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('unpaid', 'paid', 'waived') NOT NULL DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,

    CONSTRAINT fk_fines_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_fines_issue
        FOREIGN KEY (issue_id)
        REFERENCES book_issues(id)
        ON DELETE CASCADE
);


-- -------------------------------------------------------------
-- 10. Reviews & Ratings
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_reviews_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_user_book_review (user_id, book_id)
);


-- -------------------------------------------------------------
-- 11. Readability Metrics
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS readability_analysis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL UNIQUE,
    sample_text LONGTEXT NULL,
    word_count INT UNSIGNED NOT NULL DEFAULT 0,
    sentence_count INT UNSIGNED NOT NULL DEFAULT 0,
    syllable_count INT UNSIGNED NOT NULL DEFAULT 0,
    flesch_reading_ease DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    flesch_kincaid_grade DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    difficulty_level ENUM('very_easy', 'easy', 'fairly_easy', 'standard', 'fairly_difficult', 'difficult', 'very_difficult') NOT NULL DEFAULT 'standard',
    estimated_reading_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_readability_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE
);


-- -------------------------------------------------------------
-- 12. User Favorites (Wishlist)
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_favorites_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorites_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_user_favorite (user_id, book_id)
);


-- -------------------------------------------------------------
-- 13. Notifications
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- -------------------------------------------------------------
-- 14. Auth Tokens (Bearer Sessions)
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_tokens_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_tokens_token (token)
);


-- -------------------------------------------------------------
-- 15. System Status & Services
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS status_checks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service VARCHAR(100) NOT NULL,
    status ENUM('operational', 'degraded', 'outage') NOT NULL DEFAULT 'operational',
    response_time INT UNSIGNED NOT NULL DEFAULT 0,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- -------------------------------------------------------------
-- 16. Book Tags (from tags.csv)
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- -------------------------------------------------------------
-- 17. Book-Tag Relations (from book_tags.csv)
-- -------------------------------------------------------------

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
);


-- -------------------------------------------------------------
-- 18. Organization Join Requests
-- -------------------------------------------------------------

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
);


-- -------------------------------------------------------------
-- 19. Librarian Approval Requests
-- -------------------------------------------------------------

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
);


-- -------------------------------------------------------------
-- 20. Audit Logs
-- -------------------------------------------------------------

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
);


-- -------------------------------------------------------------
-- 21. Rate Limiting
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate_key VARCHAR(255) NOT NULL UNIQUE,
    requests INT UNSIGNED NOT NULL DEFAULT 1,
    reset_at INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- -------------------------------------------------------------
-- 22. Email Notifications
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS email_notifications (
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
);


-- -------------------------------------------------------------
-- 23. Fine Payments
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS fine_payments (
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
);


-- -------------------------------------------------------------
-- 24. File Uploads (Profile Pictures, Logos)
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS file_uploads (
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
);


-- -------------------------------------------------------------
-- 25. Book Recommendations
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS book_recommendations (
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
);


-- -------------------------------------------------------------
-- 26. Default Admin User
-- -------------------------------------------------------------

INSERT INTO users (name, email, password, role, status) VALUES
('System Admin', 'admin@librix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
ON DUPLICATE KEY UPDATE status = 'active';
