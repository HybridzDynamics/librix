<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
}


// Request Data

$BookId = $bookId ?? ($parts[3] ?? ($_GET["id"] ?? null));


// Single Book

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
                books.updated_at
             FROM books
             LEFT JOIN authors
                ON books.author_id = authors.id
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


// Search, Filter & Pagination Parameters

$Page = validatePageNumber($_GET["page"] ?? 1);
$Limit = validatePageLimit($_GET["limit"] ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
$Offset = ($Page - 1) * $Limit;

$Search = isset($_GET["search"]) ? trim($_GET["search"]) : null;
$Category = isset($_GET["category"]) ? trim($_GET["category"]) : null;
$AuthorId = isset($_GET["author_id"]) ? $_GET["author_id"] : null;
$AvailableOnly = isset($_GET["available"]) && in_array(strtolower((string)$_GET["available"]), ["true", "1", "yes"], true);

// Sorting Whitelist
$AllowedSortColumns = [
    "title" => "books.title",
    "created_at" => "books.created_at",
    "publication_year" => "books.publication_year",
    "id" => "books.id"
];

$SortParam = strtolower(trim($_GET["sort"] ?? "created_at"));
$SortColumn = $AllowedSortColumns[$SortParam] ?? "books.id";

$OrderParam = strtolower(trim($_GET["order"] ?? "desc"));
$SortOrder = ($OrderParam === "asc") ? "ASC" : "DESC";


// Build Query

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
    $WhereClauses[] = "books.category = ?";
    $Bindings[] = $Category;
}

if ($AuthorId !== null && $AuthorId !== "") {
    $WhereClauses[] = "books.author_id = ?";
    $Bindings[] = (int)$AuthorId;
}

if ($AvailableOnly) {
    $WhereClauses[] = "books.available_copies > 0";
}

$WhereSql = "";
if (!empty($WhereClauses)) {
    $WhereSql = "WHERE " . implode(" AND ", $WhereClauses);
}


// Count Total

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


// Fetch Paginated Books

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
                    books.updated_at
                FROM books
                LEFT JOIN authors ON books.author_id = authors.id
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


// Response

paginatedResponse(
    $Books,
    $Page,
    $Limit,
    $Total
);

?>
