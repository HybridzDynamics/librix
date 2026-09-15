<?php

// Librarian Books Management (Org-Scoped CRUD)

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/librarian.php";

$User = requireAuth();
requireLibrarian($User);

$Method = $_SERVER["REQUEST_METHOD"];

// GET: List books scoped to librarian's org
if ($Method === "GET") {
    $OrgId = getOrgScope($User);
    $Search = $_GET["search"] ?? "";
    $CategoryId = $_GET["category_id"] ?? null;

    try {
        $Query = "
            SELECT 
                books.*,
                authors.name AS author_name,
                categories.name AS category_name,
                publishers.name AS publisher_name,
                organizations.name AS org_name,
                organizations.code AS org_code
            FROM books
            LEFT JOIN authors ON books.author_id = authors.id
            LEFT JOIN categories ON books.category_id = categories.id
            LEFT JOIN publishers ON books.publisher_id = publishers.id
            LEFT JOIN organizations ON books.org_id = organizations.id
            WHERE 1=1
        ";

        $Params = [];

        if ($OrgId) {
            $Query .= " AND (books.org_id = ? OR books.org_id IS NULL)";
            $Params[] = $OrgId;
        }

        if (!empty($Search)) {
            $Query .= " AND (books.title LIKE ? OR books.isbn LIKE ? OR authors.name LIKE ?)";
            $SearchTerm = "%" . $Search . "%";
            $Params[] = $SearchTerm;
            $Params[] = $SearchTerm;
            $Params[] = $SearchTerm;
        }

        if ($CategoryId) {
            $Query .= " AND books.category_id = ?";
            $Params[] = (int)$CategoryId;
        }

        $Query .= " ORDER BY books.id DESC";

        $Stmt = $pdo->prepare($Query);
        $Stmt->execute($Params);
        $Books = $Stmt->fetchAll();

        successResponse([
            "org_id" => $OrgId,
            "total" => count($Books),
            "books" => $Books
        ]);

    } catch (PDOException $e) {
        errorResponse("Database error", 500);
    }
}

// POST: Add a new book linked to this Org ID
if ($Method === "POST") {
    $Body = getRequestBody();
    $OrgId = isset($Body["org_id"]) ? (int)$Body["org_id"] : getOrgScope($User);

    if (empty($Body["title"])) {
        badRequestResponse("Book title is required");
    }

    $Title = trim($Body["title"]);
    $ISBN = !empty($Body["isbn"]) ? trim($Body["isbn"]) : null;
    $AuthorId = !empty($Body["author_id"]) ? (int)$Body["author_id"] : null;
    $CategoryId = !empty($Body["category_id"]) ? (int)$Body["category_id"] : null;
    $PublisherId = !empty($Body["publisher_id"]) ? (int)$Body["publisher_id"] : null;
    $Description = $Body["description"] ?? "";
    $Category = $Body["category"] ?? "";
    $Language = $Body["language"] ?? "English";
    $Publisher = $Body["publisher"] ?? "";
    $Year = !empty($Body["publication_year"]) ? (int)$Body["publication_year"] : date("Y");
    $TotalCopies = !empty($Body["total_copies"]) ? (int)$Body["total_copies"] : 1;
    $AvailableCopies = !empty($Body["available_copies"]) ? (int)$Body["available_copies"] : $TotalCopies;
    $CoverImage = $Body["cover_image"] ?? "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80";

    try {
        $Stmt = $pdo->prepare("
            INSERT INTO books (
                org_id, author_id, category_id, publisher_id,
                title, isbn, description, category, language,
                publisher, publication_year, total_copies, available_copies,
                cover_image
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $Stmt->execute([
            $OrgId, $AuthorId, $CategoryId, $PublisherId,
            $Title, $ISBN, $Description, $Category, $Language,
            $Publisher, $Year, $TotalCopies, $AvailableCopies,
            $CoverImage
        ]);

        $BookId = (int)$pdo->lastInsertId();

        // Add default readability metrics
        $StmtRead = $pdo->prepare("
            INSERT INTO readability_analysis (book_id, sample_text, word_count, sentence_count, syllable_count, flesch_reading_ease, flesch_kincaid_grade, difficulty_level, estimated_reading_minutes)
            VALUES (?, ?, 50, 4, 75, 70.0, 7.0, 'standard', 300)
            ON DUPLICATE KEY UPDATE flesch_reading_ease=70.0
        ");
        $StmtRead->execute([$BookId, substr($Description, 0, 200) ?: $Title]);

        successResponse([
            "message" => "Book successfully added to organization collection",
            "book_id" => $BookId,
            "org_id" => $OrgId
        ], 201);

    } catch (PDOException $e) {
        errorResponse("Database error: " . $e->getMessage(), 500);
    }
}

// DELETE: Remove book belonging to this Org ID
if ($Method === "DELETE") {
    $BookId = (int)($_GET["id"] ?? 0);
    if (!$BookId) {
        badRequestResponse("Book ID is required");
    }

    $OrgId = getOrgScope($User);

    try {
        // Verify book belongs to this librarian's org (or user is superadmin)
        if ($User["role"] !== "admin" && $OrgId) {
            $CheckStmt = $pdo->prepare("SELECT org_id FROM books WHERE id = ?");
            $CheckStmt->execute([$BookId]);
            $Book = $CheckStmt->fetch();

            if (!$Book) {
                notFoundResponse("Book not found");
            }

            if ((int)$Book["org_id"] !== $OrgId) {
                forbiddenResponse("You can only remove books belonging to your assigned organization");
            }
        }

        $DeleteStmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $DeleteStmt->execute([$BookId]);

        successResponse([
            "message" => "Book successfully removed from organization library",
            "book_id" => $BookId
        ]);

    } catch (PDOException $e) {
        errorResponse("Database error: " . $e->getMessage(), 500);
    }
}

methodNotAllowedResponse(["GET", "POST", "DELETE"]);
?>
