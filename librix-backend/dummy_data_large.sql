USE librix;

CREATE TABLE IF NOT EXISTS contact_inquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE books MODIFY publication_year INT NULL;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM organizations;
DELETE FROM users;
DELETE FROM authors;
DELETE FROM categories;
DELETE FROM publishers;
DELETE FROM books;
DELETE FROM book_issues;
DELETE FROM reservations;
DELETE FROM fines;
DELETE FROM reviews;
DELETE FROM readability_analysis;
DELETE FROM favorites;
DELETE FROM notifications;
DELETE FROM auth_tokens;
DELETE FROM status_checks;
DELETE FROM tags;
DELETE FROM book_tags;
DELETE FROM org_join_requests;
DELETE FROM librarian_requests;
DELETE FROM audit_logs;
DELETE FROM rate_limits;
DELETE FROM email_notifications;
DELETE FROM fine_payments;
DELETE FROM file_uploads;
DELETE FROM book_recommendations;
DELETE FROM contact_inquiries;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO organizations (name, code, description, contact_email) VALUES
('City Public Library', 'CITY-001', 'Main public library serving the city', 'library@city.com'),
('University Library', 'UNIV-002', 'Academic research library', 'library@university.edu'),
('Community Center', 'COMM-003', 'Local community hub', 'info@community.org'),
('High School Library', 'HIGH-004', 'Secondary school library', 'librarian@highschool.edu'),
('Corporate Library', 'CORP-005', 'Corporate knowledge center', 'library@corp.com'),
('Medical Center Library', 'MED-006', 'Healthcare resource library', 'library@medical.org'),
('Law Library', 'LAW-007', 'Legal research facility', 'library@law.org'),
('Art Institute Library', 'ART-008', 'Fine arts resource center', 'library@art.edu'),
('Science Library', 'SCI-009', 'Scientific research library', 'library@science.edu'),
('History Archive', 'HIST-010', 'Historical documents archive', 'archive@history.org');

INSERT INTO users (name, email, password, role, status, org_id) VALUES
('System Admin', 'admin@librix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NULL),
('Alice Johnson', 'alice@city.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 1),
('Bob Smith', 'bob@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 2),
('Carol Davis', 'carol@community.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 3),
('David Wilson', 'david@highschool.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 4),
('Emma Brown', 'emma@corp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 5),
('Frank Miller', 'frank@medical.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 6),
('Grace Lee', 'grace@law.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 7),
('Henry Garcia', 'henry@art.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 8),
('Ivy Martinez', 'ivy@science.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 9),
('James Anderson', 'james@history.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', 10),
('Karen Taylor', 'karen@city.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian', 'active', 1),
('Liam White', 'liam@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian', 'active', 2),
('Mary Harris', 'mary@corp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 5);

INSERT INTO authors (name, biography) VALUES
('George Orwell', 'English novelist and essayist'),
('J.K. Rowling', 'British author of the Harry Potter series'),
('Stephen King', 'American author of horror fiction'),
('Agatha Christie', 'English crime novelist'),
('Ernest Hemingway', 'American novelist and journalist'),
('Harper Lee', 'American novelist best known for To Kill a Mockingbird'),
('F. Scott Fitzgerald', 'American novelist of the Jazz Age'),
('J.R.R. Tolkien', 'English writer and philologist'),
('Jane Austen', 'English novelist of romantic fiction'),
('Mark Twain', 'American writer and humorist'),
('Charles Dickens', 'English writer and social critic'),
('Leo Tolstoy', 'Russian writer of realistic fiction'),
('Virginia Woolf', 'English modernist writer'),
('William Shakespeare', 'English playwright and poet'),
('Gabriel Garcia Marquez', 'Colombian novelist and short-story writer'),
('Toni Morrison', 'American novelist and essayist'),
('Isabel Allende', 'Chilean-American novelist'),
('Haruki Murakami', 'Japanese writer of magical realism'),
('Margaret Atwood', 'Canadian poet and novelist'),
('Neil Gaiman', 'English author of fantasy and horror'),
('Ray Bradbury', 'American science fiction writer');

INSERT INTO categories (name, description) VALUES
('Fiction', 'Imaginative literature'),
('Non-Fiction', 'Factual and informative works'),
('Science Fiction', 'Speculative fiction with science themes'),
('Mystery', 'Crime and detective fiction'),
('Biography', 'Life stories of real people'),
('History', 'Records of past events'),
('Technology', 'Works about technology and computing'),
('Romance', 'Love and relationship stories'),
('Fantasy', 'Magic and supernatural fiction'),
('Self-Help', 'Personal development books'),
('Business', 'Management and entrepreneurship'),
('Health', 'Medical and wellness topics'),
('Travel', 'Travel guides and memoirs'),
('Cooking', 'Recipe books and culinary arts'),
('Children', 'Books for young readers'),
('Young Adult', 'Teen fiction'),
('Poetry', 'Poetic works'),
('Drama', 'Plays and scripts'),
('Art', 'Art history and techniques'),
('Music', 'Music theory and history');

INSERT INTO publishers (name, address, website) VALUES
('Penguin Random House', 'New York, NY', 'https://www.penguinrandomhouse.com'),
('HarperCollins', 'New York, NY', 'https://www.harpercollins.com'),
('Macmillan Publishers', 'New York, NY', 'https://www.macmillan.com'),
('Simon & Schuster', 'New York, NY', 'https://www.simonandschuster.com'),
('Hachette Book Group', 'New York, NY', 'https://www.hachettebookgroup.com'),
('Oxford University Press', 'Oxford, UK', 'https://global.oup.com'),
('Cambridge University Press', 'Cambridge, UK', 'https://www.cambridge.org'),
('Springer Nature', 'Berlin, Germany', 'https://www.springernature.com'),
('Elsevier', 'Amsterdam, Netherlands', 'https://www.elsevier.com'),
('Wiley', 'Hoboken, NJ', 'https://www.wiley.com');

INSERT INTO books (org_id, author_id, category_id, publisher_id, title, isbn, description, category, language, publisher, publication_year, total_copies, available_copies, average_rating, rating_count) VALUES
(1, 1, 1, 1, '1984', '978-0451524935', 'A dystopian social science fiction novel', 'Fiction', 'English', 'Signet Classic', 1949, 8, 5, 4.5, 150),
(1, 2, 9, 2, 'Harry Potter and the Sorcerer\'s Stone', '978-0590353427', 'First book in the Harry Potter series', 'Fantasy', 'English', 'Scholastic', 1997, 12, 8, 4.8, 300),
(1, 3, 4, 3, 'The Shining', '978-0307743657', 'A horror novel about a haunted hotel', 'Horror', 'English', 'Doubleday', 1977, 6, 3, 4.3, 120),
(2, 4, 4, 4, 'Murder on the Orient Express', '978-0062073501', 'A detective novel featuring Hercule Poirot', 'Mystery', 'English', 'Collins Crime Club', 1934, 10, 6, 4.4, 180),
(2, 5, 1, 5, 'The Old Man and the Sea', '978-0684801223', 'A novella about an aging fisherman', 'Literary Fiction', 'English', 'Charles Scribner\'s Sons', 1952, 8, 4, 4.2, 95),
(1, 6, 1, 1, 'To Kill a Mockingbird', '978-0061120084', 'A novel about racial injustice', 'Fiction', 'English', 'J.B. Lippincott & Co.', 1960, 10, 7, 4.7, 250),
(2, 7, 1, 2, 'The Great Gatsby', '978-0743273565', 'A novel about the American Dream', 'Fiction', 'English', 'Charles Scribner\'s Sons', 1925, 8, 5, 4.4, 200),
(1, 8, 9, 3, 'The Hobbit', '978-0547928227', 'A fantasy novel about a hobbit', 'Fantasy', 'English', 'George Allen & Unwin', 1937, 10, 6, 4.6, 220),
(1, 9, 8, 4, 'Pride and Prejudice', '978-0141439518', 'A romantic novel of manners', 'Romance', 'English', 'T. Egerton', 1813, 8, 4, 4.5, 190),
(2, 10, 1, 5, 'The Adventures of Tom Sawyer', '978-0140390064', 'A novel about a young boy growing up', 'Adventure', 'English', 'American Publishing Company', 1876, 6, 3, 4.1, 85),
(3, 11, 2, 6, 'A Tale of Two Cities', '978-0141439600', 'A novel set during the French Revolution', 'Historical Fiction', 'English', 'Chapman & Hall', 1859, 8, 5, 4.3, 140),
(3, 12, 2, 7, 'War and Peace', '978-0143039990', 'A novel about Napoleon\'s invasion of Russia', 'Historical Fiction', 'English', 'The Russian Messenger', 1869, 6, 2, 4.6, 110),
(4, 13, 1, 1, 'Mrs Dalloway', '978-0156628706', 'A modernist novel', 'Fiction', 'English', 'Hogarth Press', 1925, 5, 3, 4.2, 75),
(4, 14, 1, 2, 'One Hundred Years of Solitude', '978-0060883287', 'A magical realism novel', 'Fiction', 'English', 'Harper & Row', 1967, 6, 4, 4.7, 160),
(5, 15, 1, 3, 'Beloved', '978-1400033416', 'A novel about slavery', 'Fiction', 'English', 'Alfred A. Knopf', 1987, 5, 2, 4.5, 90),
(5, 16, 9, 4, 'The Sandman', '978-1563890116', 'A graphic novel series', 'Fantasy', 'English', 'DC Comics', 1989, 4, 2, 4.8, 80),
(6, 17, 3, 5, 'Fahrenheit 451', '978-1451673319', 'A dystopian novel about censorship', 'Science Fiction', 'English', 'Ballantine Books', 1953, 8, 5, 4.6, 210),
(6, 18, 3, 1, 'The Martian', '978-0804139024', 'A science fiction novel about Mars', 'Science Fiction', 'English', 'Crown Publishing', 2011, 6, 4, 4.4, 130),
(7, 19, 1, 2, 'Hamlet', '978-0743477123', 'A tragedy play', 'Drama', 'English', 'Arden Shakespeare', 1603, 5, 3, 4.7, 145),
(7, 20, 1, 3, 'Macbeth', '978-0743477109', 'A tragedy play', 'Drama', 'English', 'Arden Shakespeare', 1606, 5, 2, 4.5, 125),
(8, 1, 1, 1, 'Animal Farm', '978-0451526342', 'A satirical allegorical novella', 'Fiction', 'English', 'Secker & Warburg', 1945, 6, 4, 4.4, 115),
(8, 3, 4, 3, 'Carrie', '978-0307743672', 'A horror novel', 'Horror', 'English', 'Doubleday', 1974, 4, 2, 4.2, 70),
(9, 2, 9, 2, 'Harry Potter and the Chamber of Secrets', '978-0439064873', 'Second book in the Harry Potter series', 'Fantasy', 'English', 'Scholastic', 1998, 10, 7, 4.7, 280),
(9, 2, 9, 2, 'Harry Potter and the Prisoner of Azkaban', '978-0439136358', 'Third book in the Harry Potter series', 'Fantasy', 'English', 'Scholastic', 1999, 10, 6, 4.8, 290),
(10, 8, 9, 3, 'The Lord of the Rings', '978-0544003415', 'An epic fantasy novel', 'Fantasy', 'English', 'George Allen & Unwin', 1954, 8, 5, 4.9, 310);

INSERT INTO book_issues (org_id, book_id, user_id, issued_at, due_date, returned_at, renewal_count, status) VALUES
(1, 1, 2, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 1, 'returned'),
(1, 2, 2, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 9 DAY), NULL, 0, 'issued'),
(2, 4, 3, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), 0, 'returned'),
(1, 6, 2, DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), 2, 'returned'),
(2, 7, 3, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY), NULL, 0, 'issued'),
(3, 11, 4, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), 0, 'returned'),
(4, 13, 5, DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), 1, 'returned'),
(5, 15, 6, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY), NULL, 0, 'issued'),
(6, 17, 7, DATE_SUB(NOW(), INTERVAL 22 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), 0, 'returned'),
(7, 19, 8, DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_ADD(NOW(), INTERVAL 6 DAY), NULL, 0, 'issued'),
(1, 3, 2, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY), NULL, 0, 'issued'),
(2, 5, 3, DATE_SUB(NOW(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 1, 'returned'),
(3, 12, 4, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY), 0, 'returned'),
(4, 14, 5, DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_ADD(NOW(), INTERVAL 9 DAY), NULL, 0, 'issued'),
(5, 16, 6, DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), 0, 'returned');

INSERT INTO reservations (org_id, book_id, user_id, reserved_at, status) VALUES
(1, 3, 2, DATE_SUB(NOW(), INTERVAL 3 DAY), 'active'),
(2, 5, 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 'active'),
(1, 8, 2, DATE_SUB(NOW(), INTERVAL 6 DAY), 'fulfilled'),
(2, 10, 3, DATE_SUB(NOW(), INTERVAL 4 DAY), 'active'),
(3, 11, 4, DATE_SUB(NOW(), INTERVAL 8 DAY), 'fulfilled'),
(4, 13, 5, DATE_SUB(NOW(), INTERVAL 5 DAY), 'active'),
(5, 15, 6, DATE_SUB(NOW(), INTERVAL 7 DAY), 'cancelled'),
(6, 17, 7, DATE_SUB(NOW(), INTERVAL 10 DAY), 'fulfilled'),
(7, 19, 8, DATE_SUB(NOW(), INTERVAL 3 DAY), 'active'),
(8, 20, 2, DATE_SUB(NOW(), INTERVAL 9 DAY), 'active');

INSERT INTO fines (user_id, issue_id, amount, reason, status) VALUES
(2, 1, 2.50, 'Late return', 'paid'),
(3, 3, 5.00, 'Late return', 'unpaid'),
(4, 7, 3.75, 'Late return', 'paid'),
(5, 9, 7.50, 'Late return', 'unpaid'),
(6, 11, 4.25, 'Late return', 'paid'),
(2, 4, 1.50, 'Late return', 'paid'),
(3, 12, 6.00, 'Late return', 'unpaid');

INSERT INTO reviews (book_id, user_id, rating, review_text) VALUES
(1, 2, 5, 'Excellent dystopian novel'),
(2, 2, 5, 'Amazing start to the series'),
(4, 3, 4, 'Classic mystery, well-written'),
(6, 2, 5, 'A timeless masterpiece'),
(8, 2, 5, 'Great adventure story'),
(9, 3, 4, 'Beautiful romance'),
(11, 4, 5, 'Historical fiction at its best'),
(12, 4, 5, 'Epic masterpiece'),
(17, 7, 4, 'Thought-provoking'),
(19, 8, 5, 'Classic tragedy'),
(2, 3, 5, 'Magical world building'),
(3, 5, 4, 'Genuinely frightening'),
(7, 3, 4, 'Captivating prose'),
(10, 2, 4, 'Classic American literature'),
(15, 6, 5, 'Powerful and moving'),
(20, 2, 5, 'Epic fantasy');

INSERT INTO readability_analysis (book_id, word_count, sentence_count, syllable_count, flesch_reading_ease, flesch_kincaid_grade, difficulty_level, estimated_reading_minutes) VALUES
(1, 88000, 4200, 120000, 65.5, 8.2, 'standard', 440),
(2, 77000, 3800, 95000, 72.3, 6.5, 'fairly_easy', 385),
(3, 95000, 4500, 145000, 55.8, 9.5, 'fairly_difficult', 475),
(4, 82000, 4000, 110000, 62.1, 7.8, 'standard', 410),
(5, 26000, 1500, 38000, 78.5, 5.2, 'easy', 130),
(6, 96000, 4600, 135000, 60.3, 8.8, 'standard', 480),
(7, 47000, 2300, 68000, 68.9, 7.1, 'standard', 235),
(8, 95000, 4400, 125000, 64.2, 8.0, 'standard', 475),
(9, 122000, 5800, 165000, 58.7, 9.0, 'fairly_difficult', 610),
(10, 70000, 3400, 98000, 71.4, 6.2, 'fairly_easy', 350),
(11, 135000, 6200, 185000, 52.3, 10.2, 'difficult', 675),
(12, 210000, 9800, 285000, 48.5, 11.5, 'very_difficult', 1050),
(13, 95000, 4700, 130000, 61.8, 8.5, 'standard', 475),
(14, 150000, 7200, 205000, 54.2, 9.8, 'fairly_difficult', 750),
(15, 110000, 5300, 150000, 56.9, 9.2, 'fairly_difficult', 550),
(16, 145000, 6900, 198000, 50.8, 10.8, 'difficult', 725),
(17, 78000, 3800, 108000, 67.4, 7.5, 'standard', 390),
(18, 92000, 4400, 125000, 65.1, 8.1, 'standard', 460),
(19, 85000, 4100, 118000, 59.3, 8.9, 'standard', 425),
(20, 130000, 6200, 178000, 55.6, 9.4, 'fairly_difficult', 650),
(21, 89000, 4300, 122000, 63.7, 7.9, 'standard', 445),
(22, 88000, 4200, 120000, 68.2, 7.3, 'standard', 440),
(23, 75000, 3600, 105000, 73.8, 6.1, 'fairly_easy', 375),
(24, 100000, 4800, 138000, 58.4, 9.1, 'fairly_difficult', 500),
(25, 125000, 6000, 172000, 53.7, 10.1, 'difficult', 625);

INSERT INTO favorites (user_id, book_id) VALUES
(2, 1), (2, 2), (2, 6), (2, 8), (2, 9),
(3, 4), (3, 5), (3, 7), (3, 10), (3, 11),
(4, 13), (4, 14), (4, 15), (4, 17), (4, 19),
(5, 16), (5, 18), (5, 20), (5, 21), (5, 22),
(6, 1), (6, 3), (6, 6), (6, 8), (6, 12),
(7, 4), (7, 5), (7, 9), (7, 10), (7, 13),
(8, 2), (8, 6), (8, 8), (8, 11), (8, 14),
(2, 17), (2, 19), (2, 20), (2, 23), (2, 24);

INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(2, 'Book Due Soon', '1984 is due in 1 day', 'warning', 0),
(2, 'Reservation Ready', 'The Shining is now available', 'success', 0),
(3, 'New Book Added', 'Great Gatsby is now available', 'info', 1),
(2, 'Fine Reminder', 'You have an unpaid fine of $5.00', 'danger', 0),
(4, 'Book Available', 'Mrs Dalloway is now available', 'success', 0),
(5, 'New Acquisition', 'The Sandman has been added', 'info', 1),
(6, 'Overdue Notice', 'Animal Farm is overdue', 'danger', 0),
(7, 'Reservation Fulfilled', 'Your reservation for Hamlet is fulfilled', 'success', 0),
(8, 'Book Due Tomorrow', 'Macbeth is due tomorrow', 'warning', 0),
(2, 'Welcome to LibriX', 'Thank you for joining our library', 'info', 1);

INSERT INTO auth_tokens (user_id, token, expires_at) VALUES
(2, 'token_abc123def456', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(3, 'token_xyz789uvw012', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(4, 'token_ghi345jkl678', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(5, 'token_mno901pqr234', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(6, 'token_stu567vwx890', DATE_ADD(NOW(), INTERVAL 7 DAY));

INSERT INTO status_checks (service, status, response_time) VALUES
('Database', 'operational', 15),
('API Server', 'operational', 45),
('Cache', 'operational', 8),
('File Storage', 'operational', 22),
('Email Service', 'operational', 35);

INSERT INTO tags (name) VALUES
('Classic'), ('Adventure'), ('Romance'), ('Mystery'), ('Horror'),
('Fantasy'), ('Science Fiction'), ('Historical'), ('Award Winner'), ('Bestseller'),
('Dystopian'), ('Magical Realism'), ('Modernist'), ('Epic'), ('Tragedy'),
('Graphic Novel'), ('Post-Apocalyptic'), ('Thriller'), ('Psychological'), ('Satire');

INSERT INTO book_tags (book_id, tag_id, count) VALUES
(1, 1, 12), (1, 10, 15), (1, 11, 8),
(2, 6, 15), (2, 10, 20), (2, 9, 12),
(3, 5, 10), (3, 10, 8), (3, 18, 6),
(4, 4, 14), (4, 1, 10), (4, 10, 11),
(6, 1, 18), (6, 10, 22), (6, 20, 5),
(8, 6, 11), (8, 1, 9), (8, 14, 8),
(9, 3, 8), (9, 1, 10), (9, 10, 12),
(11, 7, 9), (11, 1, 8), (11, 14, 7),
(12, 7, 12), (12, 1, 10), (12, 15, 15),
(17, 3, 7), (17, 16, 6), (17, 1, 9),
(18, 3, 8), (18, 1, 7), (18, 10, 10),
(21, 1, 10), (21, 10, 11), (21, 21, 5),
(22, 1, 9), (22, 5, 7), (22, 10, 8),
(25, 6, 8), (25, 7, 6), (25, 14, 9);

INSERT INTO org_join_requests (org_id, user_id, message, status) VALUES
(3, 2, 'I would like to join the community library', 'pending'),
(4, 3, 'Requesting access to high school library', 'approved'),
(5, 4, 'I work at the corporation', 'pending');

INSERT INTO librarian_requests (user_id, org_id, name, email, library_name, library_address, library_phone, message, status) VALUES
(2, 1, 'Alice Johnson', 'alice@city.com', 'City Public Library', '123 Main St', '555-0101', 'Requesting librarian access', 'approved'),
(3, 2, 'Bob Smith', 'bob@university.edu', 'University Library', '456 Campus Dr', '555-0202', 'I am a certified librarian', 'pending'),
(4, 3, 'Carol Davis', 'carol@community.org', 'Community Center', '789 Oak Ave', '555-0303', 'Would like to help manage the library', 'approved');

INSERT INTO audit_logs (user_id, org_id, action, entity_type, entity_id, description, ip_address) VALUES
(1, NULL, 'login', 'user', 1, 'Admin logged in', '192.168.1.1'),
(13, 1, 'update', 'book', 2, 'Updated book Harry Potter', '192.168.1.2'),
(14, 2, 'create', 'user', 3, 'Created new user Bob Smith', '192.168.1.3'),
(1, NULL, 'delete', 'book', 5, 'Deleted book Carrie', '192.168.1.4'),
(13, 1, 'issue', 'book_issue', 2, 'Issued book 1984 to Alice Johnson', '192.168.1.5'),
(14, 2, 'return', 'book_issue', 3, 'Bob Smith returned Great Gatsby', '192.168.1.6'),
(1, NULL, 'update', 'user', 4, 'Updated Carol Davis profile', '192.168.1.7'),
(13, 1, 'approve', 'org_join_request', 1, 'Approved org join request', '192.168.1.8'),
(15, 5, 'create', 'book', 16, 'Added new book The Sandman', '192.168.1.9'),
(1, NULL, 'logout', 'user', 2, 'Alice Johnson logged out', '192.168.1.10');

INSERT INTO rate_limits (rate_key, requests, reset_at) VALUES
('login_192.168.1.1', 3, UNIX_TIMESTAMP() + 3600),
('api_192.168.1.2', 10, UNIX_TIMESTAMP() + 60),
('register_192.168.1.3', 2, UNIX_TIMESTAMP() + 3600),
('api_192.168.1.4', 8, UNIX_TIMESTAMP() + 60),
('login_192.168.1.5', 1, UNIX_TIMESTAMP() + 3600);

INSERT INTO email_notifications (user_id, subject, body, status) VALUES
(2, 'Welcome to LibriX', 'Thank you for joining our library system', 'sent'),
(3, 'Book Available', 'Your reserved book is now available', 'pending'),
(4, 'Account Approved', 'Your account has been approved', 'sent'),
(5, 'Fine Notice', 'You have an overdue fine', 'pending'),
(6, 'Reservation Reminder', 'Your book is ready for pickup', 'sent');

INSERT INTO fine_payments (user_id, issue_id, amount, payment_method, payment_reference, status) VALUES
(2, 1, 2.50, 'cash', 'PAY001', 'completed'),
(4, 7, 3.75, 'card', 'PAY002', 'completed'),
(6, 11, 4.25, 'cash', 'PAY003', 'completed'),
(2, 4, 1.50, 'card', 'PAY004', 'completed');

INSERT INTO file_uploads (user_id, org_id, file_name, original_name, file_path, file_size, mime_type, upload_type) VALUES
(2, 1, 'profile_alice.jpg', 'profile.jpg', '/uploads/profile-pictures/profile_alice.jpg', 24576, 'image/jpeg', 'profile_picture'),
(3, 2, 'profile_bob.png', 'profile.png', '/uploads/profile-pictures/profile_bob.png', 16384, 'image/png', 'profile_picture'),
(1, 1, 'logo_city.png', 'logo.png', '/uploads/org-logos/logo_city.png', 8192, 'image/png', 'org_logo'),
(2, 2, 'logo_univ.jpg', 'logo.jpg', '/uploads/org-logos/logo_univ.jpg', 12288, 'image/jpeg', 'org_logo');

INSERT INTO book_recommendations (user_id, book_id, reason, score) VALUES
(2, 3, 'Based on your reading history', 0.85),
(2, 5, 'Similar to books you liked', 0.78),
(3, 6, 'Popular in your category', 0.72),
(4, 8, 'New in your favorite genre', 0.68),
(5, 10, 'Highly rated by similar users', 0.75),
(6, 12, 'Trending in your library', 0.65),
(7, 14, 'Award-winning recommendation', 0.82),
(8, 16, 'Based on your favorites', 0.77),
(2, 18, 'New release in your genre', 0.70),
(3, 20, 'Classic you might enjoy', 0.80);

INSERT INTO contact_inquiries (name, email, subject, message, status) VALUES
('Alice Smith', 'alice@example.com', 'borrowing', 'How do I renew a book?', 'pending'),
('Bob Jones', 'bob@example.com', 'account', 'Cannot reset password', 'pending'),
('Carol White', 'carol@example.com', 'recommendation', 'Suggest a good mystery book', 'pending'),
('David Brown', 'david@example.com', 'technical', 'Website not loading', 'resolved'),
('Emma Green', 'emma@example.com', 'fines', 'Question about my fine', 'pending');
