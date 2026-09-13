-- LibriX Database

CREATE DATABASE IF NOT EXISTS librix
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE librix;


-- Users

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Authors

CREATE TABLE authors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    biography TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Book Categories

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Publishers

CREATE TABLE publishers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    address TEXT NULL,
    website VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Books

CREATE TABLE books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

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

    INDEX idx_books_category (category_id),
    INDEX idx_books_publisher (publisher_id),
    INDEX idx_books_title (title),
    INDEX idx_books_isbn (isbn)
);


-- Book Issues

CREATE TABLE book_issues (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NOT NULL,
    returned_at TIMESTAMP NULL,
    renewal_count INT UNSIGNED NOT NULL DEFAULT 0,
    max_renewals INT UNSIGNED NOT NULL DEFAULT 2,
    status ENUM('issued', 'returned', 'overdue') NOT NULL DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_issues_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_issues_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_issues_user (user_id),
    INDEX idx_issues_status (status),
    INDEX idx_issues_due_date (due_date)
);


-- Book Reservations

CREATE TABLE reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'cancelled', 'fulfilled') NOT NULL DEFAULT 'active',

    CONSTRAINT fk_reservations_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_reservations_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_reservations_user (user_id),
    INDEX idx_reservations_status (status)
);


-- Fines

CREATE TABLE fines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    issue_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reason VARCHAR(255) NULL,
    status ENUM('unpaid', 'paid', 'waived') NOT NULL DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,

    CONSTRAINT fk_fines_issue
        FOREIGN KEY (issue_id)
        REFERENCES book_issues(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_fines_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_fines_user (user_id),
    INDEX idx_fines_status (status)
);


-- Reviews

CREATE TABLE reviews (
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
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_user_book_review (user_id, book_id),
    INDEX idx_reviews_book (book_id)
);


-- Favorites

CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_favorites_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_favorites_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_user_book_favorite (user_id, book_id),
    INDEX idx_favorites_user (user_id)
);


-- Notifications

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info',
    is_read TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read)
);


-- Readability Analysis

CREATE TABLE readability_analysis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    sample_text TEXT NULL,
    word_count INT UNSIGNED NOT NULL DEFAULT 0,
    sentence_count INT UNSIGNED NOT NULL DEFAULT 0,
    syllable_count INT UNSIGNED NOT NULL DEFAULT 0,
    flesch_reading_ease DECIMAL(5,2) NULL,
    flesch_kincaid_grade DECIMAL(5,2) NULL,
    difficulty_level ENUM('very_easy', 'easy', 'fairly_easy', 'standard', 'fairly_difficult', 'difficult', 'very_difficult') NULL,
    estimated_reading_minutes INT UNSIGNED NULL,
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_readability_book
        FOREIGN KEY (book_id)
        REFERENCES books(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_readability_book (book_id)
);


-- Authentication Tokens

CREATE TABLE auth_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_tokens_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- Password Reset Tokens

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reset_tokens_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- Email Verification Tokens

CREATE TABLE email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_email_tokens_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- Audit Logs

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT UNSIGNED NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);


-- Rate Limits

CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate_key VARCHAR(255) NOT NULL,
    requests INT UNSIGNED NOT NULL DEFAULT 1,
    reset_at INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_rate_key (rate_key)
);


-- Status Incidents

CREATE TABLE status_incidents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('investigating', 'identified', 'monitoring', 'resolved') NOT NULL DEFAULT 'investigating',
    severity ENUM('minor', 'major', 'critical') NOT NULL DEFAULT 'minor',
    started_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Status Checks

CREATE TABLE status_checks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service VARCHAR(100) NOT NULL,
    status ENUM('operational', 'degraded', 'outage') NOT NULL DEFAULT 'operational',
    response_time INT UNSIGNED NOT NULL DEFAULT 0,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Maintenance Windows

CREATE TABLE maintenance_windows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    service VARCHAR(100) NOT NULL DEFAULT 'all',
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('scheduled', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_maintenance_user
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

ALTER TABLE books
ADD COLUMN average_rating DECIMAL(3,2) NULL,
ADD COLUMN rating_count INT UNSIGNED NOT NULL DEFAULT 0,
ADD COLUMN content LONGTEXT NULL;