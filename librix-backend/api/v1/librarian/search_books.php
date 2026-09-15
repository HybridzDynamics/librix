<?php

// Librarian: Search global book catalogue (any org or unassigned)
// GET /api/v1/librarian/search_books?q=...&limit=20&page=1
// Allows librarians to search ALL books in the DB and add them to their org

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/librarian.php";

$User = requireAuth();
requireLibrarian($User);

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}

$Search  = trim($_GET["q"] ?? $_GET["search"] ?? "");
$Page    = max(1, (int)($_GET["page"] ?? 1));
$Limit   = min(50, max(1, (int)($_GET["limit"] ?? 20)));
$Offset  = ($Page - 1) * $Limit;

if (strlen($Search) < 1) {
    badRequestResponse("Search query 'q' is required (minimum 1 character)");
}

try {
    $SearchTerm = "%" . $Search . "%";

    // Count total
    $CountStmt = $pdo->prepare("
        SELECT COUNT(*) FROM books
        LEFT JOIN authors ON books.author_id = authors.id
        WHERE
            books.title   LIKE ? OR
            books.isbn    LIKE ? OR
            books.category LIKE ? OR
            authors.name  LIKE ?
    ");
    $CountStmt->execute([$SearchTerm, $SearchTerm, $SearchTerm, $SearchTerm]);
    $Total = (int)$CountStmt->fetchColumn();

    // Fetch paginated results
    $Stmt = $pdo->prepare("
        SELECT
            books.id,
            books.org_id,
            books.title,
            books.isbn,
            books.description,
            books.category,
            books.language,
            books.publication_year,
            books.total_copies,
            books.available_copies,
            books.cover_image,
            books.average_rating,
            books.rating_count,
            authors.id   AS author_id,
            authors.name AS author_name,
            categories.id   AS category_id,
            categories.name AS category_name,
            organizations.name AS current_org_name
        FROM books
        LEFT JOIN authors       ON books.author_id       = authors.id
        LEFT JOIN categories    ON books.category_id     = categories.id
        LEFT JOIN organizations ON books.org_id          = organizations.id
        WHERE
            books.title    LIKE ? OR
            books.isbn     LIKE ? OR
            books.category LIKE ? OR
            authors.name   LIKE ?
        ORDER BY books.average_rating DESC, books.rating_count DESC
        LIMIT ? OFFSET ?
    ");
    $Stmt->execute([$SearchTerm, $SearchTerm, $SearchTerm, $SearchTerm, $Limit, $Offset]);
    $Books = $Stmt->fetchAll();

    // Cast types
    foreach ($Books as &$B) {
        $B["id"]               = (int)$B["id"];
        $B["org_id"]           = $B["org_id"] ? (int)$B["org_id"] : null;
        $B["author_id"]        = $B["author_id"] ? (int)$B["author_id"] : null;
        $B["category_id"]      = $B["category_id"] ? (int)$B["category_id"] : null;
        $B["total_copies"]     = (int)$B["total_copies"];
        $B["available_copies"] = (int)$B["available_copies"];
        $B["average_rating"]   = (float)($B["average_rating"] ?? 0);
        $B["rating_count"]     = (int)($B["rating_count"] ?? 0);
    }
    unset($B);

    successResponse([
        "query"      => $Search,
        "total"      => $Total,
        "page"       => $Page,
        "limit"      => $Limit,
        "total_pages"=> max(1, (int)ceil($Total / $Limit)),
        "books"      => $Books
    ]);

} catch (PDOException $e) {
    logError("search_books DB error", ["error" => $e->getMessage()]);
    errorResponse("Database error", 500);
}
?>
