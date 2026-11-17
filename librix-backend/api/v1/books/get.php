<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}

$BookId = $bookId ?? ($parts[3] ?? ($_GET["id"] ?? null));

if ($BookId !== null) {

    $IdError = validatePositiveInteger($BookId, "Book ID");

    if ($IdError !== null) {
        errorResponse($IdError, 400);
    }

    try {
        $Stmt = $pdo->prepare(
            "SELECT
                books.id,
                books.author_id,
                authors.name AS author_name,
                books.title,
                books.isbn,
                books.description,
                books.category,
                books.publisher,
                books.publication_year,
                books.total_copies,
                books.available_copies,
                books.cover_image,
                books.created_at,
                books.updated_at,
                ra.flesch_kincaid_grade,
                ra.difficulty_level,
                ra.estimated_reading_minutes
             FROM books
             LEFT JOIN authors
                ON books.author_id = authors.id
             LEFT JOIN (
                SELECT ra1.book_id, ra1.flesch_kincaid_grade, ra1.difficulty_level, ra1.estimated_reading_minutes
                FROM readability_analysis ra1
                INNER JOIN (
                    SELECT book_id, MAX(id) AS max_id
                    FROM readability_analysis
                    GROUP BY book_id
                ) ra2 ON ra2.book_id = ra1.book_id AND ra2.max_id = ra1.id
             ) ra ON ra.book_id = books.id
             WHERE books.id = ?
             LIMIT 1"
        );

        $Stmt->execute([(int)$BookId]);

        $Book = $Stmt->fetch();

    } catch (PDOException $e) {
        errorResponse("Database error", 500);
    }

    if (!$Book) {
        notFoundResponse("Book not found");
    }

    successResponse($Book);
}

$Page = validatePageNumber($_GET["page"] ?? 1);
$Limit = validatePageLimit($_GET["limit"] ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
$Offset = ($Page - 1) * $Limit;

$Search = isset($_GET["search"]) ? trim($_GET["search"]) : null;
$Category = isset($_GET["category"]) ? trim($_GET["category"]) : null;
$AuthorId = isset($_GET["author_id"]) ? $_GET["author_id"] : null;
$AvailableParam = isset($_GET["available"]) ? strtolower((string)$_GET["available"]) : null;
$AvailableOnly = $AvailableParam !== null && in_array($AvailableParam, ["true", "1", "yes"], true);
$UnavailableOnly = $AvailableParam !== null && in_array($AvailableParam, ["false", "0", "no"], true);

$SortMap = [
    "title_asc" => ["books.title", "ASC"],
    "title_desc" => ["books.title", "DESC"],
    "year_asc" => ["books.publication_year", "ASC"],
    "year_desc" => ["books.publication_year", "DESC"],
    "available_asc" => ["books.available_copies", "ASC"],
    "available_desc" => ["books.available_copies", "DESC"],
    "newest" => ["books.created_at", "DESC"],
    "oldest" => ["books.created_at", "ASC"],
    "rating_desc" => ["books.average_rating", "DESC"]
];

$SortParam = strtolower(trim($_GET["sort"] ?? "created_at"));

if (isset($SortMap[$SortParam])) {
    [$SortColumn, $SortOrder] = $SortMap[$SortParam];
} else {
    $AllowedSortColumns = [
        "title" => "books.title",
        "created_at" => "books.created_at",
        "publication_year" => "books.publication_year",
        "available_copies" => "books.available_copies",
        "average_rating" => "books.average_rating",
        "id" => "books.id"
    ];

    $SortColumn = $AllowedSortColumns[$SortParam] ?? "books.id";

    $OrderParam = strtolower(trim($_GET["order"] ?? "desc"));
    $SortOrder = ($OrderParam === "asc") ? "ASC" : "DESC";
}

$WhereClauses = [];
$Bindings = [];

if ($Search !== null && $Search !== "") {
    $WhereClauses[] = "(books.title LIKE ? OR books.isbn LIKE ? OR authors.name LIKE ?)";
    $SearchTerm = "%" . $Search . "%";
    $Bindings[] = $SearchTerm;
    $Bindings[] = $SearchTerm;
    $Bindings[] = $SearchTerm;
}

if ($Category !== null && $Category !== "") {
    if (ctype_digit($Category)) {
        $WhereClauses[] = "(books.category_id = ? OR books.category = (SELECT name FROM categories WHERE id = ?))";
        $Bindings[] = (int)$Category;
        $Bindings[] = (int)$Category;
    } else {
        $WhereClauses[] = "(books.category = ? OR books.category_id = (SELECT id FROM categories WHERE name = ?))";
        $Bindings[] = $Category;
        $Bindings[] = $Category;
    }
}

if ($AuthorId !== null && $AuthorId !== "") {
    $WhereClauses[] = "books.author_id = ?";
    $Bindings[] = (int)$AuthorId;
}

if ($AvailableOnly) {
    $WhereClauses[] = "books.available_copies > 0";
}

if ($UnavailableOnly) {
    $WhereClauses[] = "books.available_copies <= 0";
}

$WhereSql = "";
if (!empty($WhereClauses)) {
    $WhereSql = "WHERE " . implode(" AND ", $WhereClauses);
}

try {
    $CountSql = "SELECT COUNT(*) AS total
                 FROM books
                 LEFT JOIN authors ON books.author_id = authors.id
                 $WhereSql";

    $Stmt = $pdo->prepare($CountSql);
    $Stmt->execute($Bindings);
    $TotalRow = $Stmt->fetch();
    $Total = (int)($TotalRow["total"] ?? 0);

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

try {
    $DataSql = "SELECT
                    books.id,
                    books.author_id,
                    authors.name AS author_name,
                    books.title,
                    books.isbn,
                    books.description,
                    books.category,
                    books.publisher,
                    books.publication_year,
                    books.total_copies,
                    books.available_copies,
                    books.cover_image,
                    books.created_at,
                    books.updated_at,
                    ra.flesch_kincaid_grade,
                    ra.difficulty_level,
                    ra.estimated_reading_minutes
                FROM books
                LEFT JOIN authors ON books.author_id = authors.id
                LEFT JOIN (
                    SELECT ra1.book_id, ra1.flesch_kincaid_grade, ra1.difficulty_level, ra1.estimated_reading_minutes
                    FROM readability_analysis ra1
                    INNER JOIN (
                        SELECT book_id, MAX(id) AS max_id
                        FROM readability_analysis
                        GROUP BY book_id
                    ) ra2 ON ra2.book_id = ra1.book_id AND ra2.max_id = ra1.id
                ) ra ON ra.book_id = books.id
                $WhereSql
                ORDER BY $SortColumn $SortOrder
                LIMIT ? OFFSET ?";

    $Stmt = $pdo->prepare($DataSql);

    $ParamIndex = 1;
    foreach ($Bindings as $Binding) {
        $Stmt->bindValue($ParamIndex++, $Binding);
    }
    $Stmt->bindValue($ParamIndex++, (int)$Limit, PDO::PARAM_INT);
    $Stmt->bindValue($ParamIndex++, (int)$Offset, PDO::PARAM_INT);

    $Stmt->execute();

    $Books = $Stmt->fetchAll();

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

paginatedResponse(
    $Books,
    $Page,
    $Limit,
    $Total
);

?>
