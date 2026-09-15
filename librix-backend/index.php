<?php

// Configuration

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/helpers/response.php";
require_once __DIR__ . "/helpers/validation.php";
require_once __DIR__ . "/helpers/functions.php";
require_once __DIR__ . "/middleware/auth.php";
require_once __DIR__ . "/middleware/admin.php";


// Headers

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: " . CORS_ORIGIN);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// OPTIONS Request

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}


// Request Method

$method = $_SERVER["REQUEST_METHOD"];


// Request Path

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

$path = trim($path, "/");


// Remove Project Path

$basePath = trim(dirname($_SERVER["SCRIPT_NAME"]), "/");

// Don't remove the basePath if it contains the API path
if ($basePath !== "" && !str_contains($path, "api")) {
    if (str_starts_with($path, $basePath)) {
        $path = trim(substr($path, strlen($basePath)), "/");
    }
}


// Docs Route (HTML documentation)

if (
    isset($parts[0]) &&
    $parts[0] === "docs"
) {
    require_once __DIR__ . "/docs/index.php";
    exit;
}

// Remove index.php from path if present
if (str_starts_with($path, "index.php")) {
    $path = trim(substr($path, strlen("index.php")), "/");
}

// Remove leading slash if present
$path = ltrim($path, "/");


// Path Parts

$parts = $path === "" ? [] : explode("/", $path);


// Home

if (empty($parts)) {
    successResponse([
        "name" => APP_NAME,
        "version" => APP_VERSION,
        "api" => API_VERSION,
        "status" => SERVER_STATUS
    ]);
}


// API Check

if (
    isset($parts[0]) &&
    $parts[0] === "api" &&
    isset($parts[1]) &&
    $parts[1] === API_VERSION
) {
    if (count($parts) === 2) {
        successResponse([
            "name" => APP_NAME,
            "version" => APP_VERSION,
            "api" => API_VERSION,
            "status" => SERVER_STATUS
        ]);
    }
    
    // If we have more than 2 parts, continue to specific routes
}


// Health Check Route

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "health"
) {
    require_once __DIR__ . "/api/v1/health.php";
}


// Auth Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "auth"
) {
    if ($parts[3] ?? null) {
        $route = $parts[3];

        switch ($route) {
            case "login":
                require_once __DIR__ . "/api/v1/auth/login.php";
                break;

            case "register":
                require_once __DIR__ . "/api/v1/auth/register.php";
                break;

            case "logout":
                require_once __DIR__ . "/api/v1/auth/logout.php";
                break;

            case "session":
                require_once __DIR__ . "/api/v1/auth/session.php";
                break;

            case "forgot-password":
                require_once __DIR__ . "/api/v1/auth/forgot-password.php";
                break;

            case "reset-password":
                require_once __DIR__ . "/api/v1/auth/reset-password.php";
                break;

            case "verify-email":
                require_once __DIR__ . "/api/v1/auth/verify-email.php";
                break;

            case "resend-verification":
                require_once __DIR__ . "/api/v1/auth/resend-verification.php";
                break;

            default:
                notFoundResponse("Auth route not found");
        }
    }

    notFoundResponse("Auth route not found");
}


// Books Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "books"
) {
    $bookId = $parts[3] ?? null;

    if ($method === "GET" && $bookId === null) {
        require_once __DIR__ . "/api/v1/books/get.php";
    }

    if ($method === "POST" && $bookId === null) {
        require_once __DIR__ . "/api/v1/books/create.php";
    }

    if ($method === "GET" && $bookId !== null) {
        require_once __DIR__ . "/api/v1/books/get.php";
    }

    if ($method === "PUT" && $bookId !== null) {
        require_once __DIR__ . "/api/v1/books/update.php";
    }

    if ($method === "DELETE" && $bookId !== null) {
        require_once __DIR__ . "/api/v1/books/delete.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST",
        "PUT",
        "DELETE"
    ]);
}


// Authors Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "authors"
) {
    $authorId = $parts[3] ?? null;

    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/authors/get.php";
    }

    if ($method === "POST" && $authorId === null) {
        require_once __DIR__ . "/api/v1/authors/create.php";
    }

    if ($method === "PUT" && $authorId !== null) {
        require_once __DIR__ . "/api/v1/authors/update.php";
    }

    if ($method === "DELETE" && $authorId !== null) {
        require_once __DIR__ . "/api/v1/authors/delete.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST",
        "PUT",
        "DELETE"
    ]);
}


// Categories Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "categories"
) {
    $categoryId = $parts[3] ?? null;

    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/categories/get.php";
    }

    if ($method === "POST" && $categoryId === null) {
        require_once __DIR__ . "/api/v1/categories/create.php";
    }

    if ($method === "PUT" && $categoryId !== null) {
        require_once __DIR__ . "/api/v1/categories/update.php";
    }

    if ($method === "DELETE" && $categoryId !== null) {
        require_once __DIR__ . "/api/v1/categories/delete.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST",
        "PUT",
        "DELETE"
    ]);
}


// Publishers Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "publishers"
) {
    $publisherId = $parts[3] ?? null;

    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/publishers/get.php";
    }

    if ($method === "POST" && $publisherId === null) {
        require_once __DIR__ . "/api/v1/publishers/create.php";
    }

    if ($method === "PUT" && $publisherId !== null) {
        require_once __DIR__ . "/api/v1/publishers/update.php";
    }

    if ($method === "DELETE" && $publisherId !== null) {
        require_once __DIR__ . "/api/v1/publishers/delete.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST",
        "PUT",
        "DELETE"
    ]);
}


// Reviews Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "reviews"
) {
    $reviewId = $parts[3] ?? null;

    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/reviews/get.php";
    }

    if ($method === "POST") {
        require_once __DIR__ . "/api/v1/reviews/create.php";
    }

    if ($method === "DELETE") {
        require_once __DIR__ . "/api/v1/reviews/delete.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST",
        "DELETE"
    ]);
}


// Favorites Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "favorites"
) {
    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/favorites/get.php";
    }

    if ($method === "POST") {
        require_once __DIR__ . "/api/v1/favorites/toggle.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST"
    ]);
}


// Notifications Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "notifications"
) {
    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/notifications/get.php";
    }

    if ($method === "PUT" || $method === "POST") {
        require_once __DIR__ . "/api/v1/notifications/read.php";
    }

    methodNotAllowedResponse([
        "GET",
        "PUT",
        "POST"
    ]);
}


// Readability Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "readability"
) {
    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/readability/get.php";
    }

    if ($method === "POST") {
        require_once __DIR__ . "/api/v1/readability/analyze.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST"
    ]);
}


// Library Routes

if (
    isset($parts[0], $parts[1], $parts[2], $parts[3]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "library"
) {
    switch ($parts[3]) {
        case "issue":
            require_once __DIR__ . "/api/v1/library/issue.php";
            break;

        case "return":
            require_once __DIR__ . "/api/v1/library/return.php";
            break;

        case "reserve":
            require_once __DIR__ . "/api/v1/library/reserve.php";
            break;

        case "cancel-reservation":
            require_once __DIR__ . "/api/v1/library/cancel-reservation.php";
            break;

        case "my-books":
            require_once __DIR__ . "/api/v1/library/my-books.php";
            break;

        case "renew":
            require_once __DIR__ . "/api/v1/library/renew.php";
            break;

        case "history":
            require_once __DIR__ . "/api/v1/library/history.php";
            break;

        default:
            notFoundResponse("Library route not found");
    }
}


// User Routes

if (
    isset($parts[0], $parts[1], $parts[2], $parts[3]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "users"
) {
    switch ($parts[3]) {
        case "profile":
            require_once __DIR__ . "/api/v1/users/profile.php";
            break;

        case "update":
            require_once __DIR__ . "/api/v1/users/update.php";
            break;

        default:
            notFoundResponse("User route not found");
    }
}


// Status Routes (Public)

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "status"
) {
    $statusSubRoute = $parts[3] ?? null;

    if ($statusSubRoute === null) {
        require_once __DIR__ . "/api/v1/status/status.php";
    }

    switch ($statusSubRoute) {
        case "services":
            require_once __DIR__ . "/api/v1/status/services.php";
            break;

        case "incidents":
            require_once __DIR__ . "/api/v1/status/incidents.php";
            break;

        case "uptime":
            require_once __DIR__ . "/api/v1/status/uptime.php";
            break;

        case "history":
            require_once __DIR__ . "/api/v1/status/history.php";
            break;

        case "maintenance":
            require_once __DIR__ . "/api/v1/status/maintenance.php";
            break;

        default:
            notFoundResponse("Status route not found");
    }
}


// Admin Routes

if (
    isset($parts[0], $parts[1], $parts[2], $parts[3]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "admin"
) {
    switch ($parts[3]) {
        case "users":
            require_once __DIR__ . "/api/v1/admin/users.php";
            break;

        case "books":
            require_once __DIR__ . "/api/v1/admin/books.php";
            break;

        case "statistics":
            require_once __DIR__ . "/api/v1/admin/statistics.php";
            break;

        case "issues":
            require_once __DIR__ . "/api/v1/admin/issues.php";
            break;

        case "reservations":
            require_once __DIR__ . "/api/v1/admin/reservations.php";
            break;

        case "fines":
            require_once __DIR__ . "/api/v1/admin/fines.php";
            break;

        case "status":
            $adminStatusSub = $parts[4] ?? null;
            if ($adminStatusSub === "incidents") {
                require_once __DIR__ . "/api/v1/admin/status/incidents.php";
            } elseif ($adminStatusSub === "maintenance") {
                require_once __DIR__ . "/api/v1/admin/status/maintenance.php";
            } else {
                notFoundResponse("Admin status route not found");
            }
            break;

        default:
            notFoundResponse("Admin route not found");
    }
}


// Organizations Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "organizations"
) {
    $orgId = $parts[3] ?? null;

    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/organizations/get.php";
    }

    if ($method === "POST" && $orgId === null) {
        require_once __DIR__ . "/api/v1/organizations/create.php";
    }

    if ($method === "PUT" && $orgId !== null) {
        require_once __DIR__ . "/api/v1/organizations/update.php";
    }

    if ($method === "DELETE" && $orgId !== null) {
        require_once __DIR__ . "/api/v1/organizations/delete.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST",
        "PUT",
        "DELETE"
    ]);
}


// Librarian Routes

if (
    isset($parts[0], $parts[1], $parts[2], $parts[3]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "librarian"
) {
    switch ($parts[3]) {
        case "request":
            require_once __DIR__ . "/api/v1/librarian/request.php";
            break;

        case "requests":
            require_once __DIR__ . "/api/v1/librarian/requests.php";
            break;

        case "approve":
            require_once __DIR__ . "/api/v1/librarian/approve.php";
            break;

        default:
            notFoundResponse("Librarian route not found");
    }
}


// Organization Join Routes

if (
    isset($parts[0], $parts[1], $parts[2], $parts[3]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "join"
) {
    switch ($parts[3]) {
        case "request":
            require_once __DIR__ . "/api/v1/join/request.php";
            break;

        case "requests":
            require_once __DIR__ . "/api/v1/join/requests.php";
            break;

        case "approve":
            require_once __DIR__ . "/api/v1/join/approve.php";
            break;

        default:
            notFoundResponse("Join route not found");
    }
}

// Organizations Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "organizations"
) {
    $orgId = $parts[3] ?? null;

    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/organizations/get.php";
    }

    if ($method === "POST" && $orgId === null) {
        require_once __DIR__ . "/api/v1/organizations/create.php";
    }

    if ($method === "PUT" && $orgId !== null) {
        require_once __DIR__ . "/api/v1/organizations/update.php";
    }

    if ($method === "DELETE" && $orgId !== null) {
        require_once __DIR__ . "/api/v1/organizations/delete.php";
    }

    methodNotAllowedResponse([
        "GET",
        "POST",
        "PUT",
        "DELETE"
    ]);
}


// Librarian Routes

if (
    isset($parts[0], $parts[1], $parts[2], $parts[3]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "librarian"
) {
    switch ($parts[3]) {
        case "request":
            require_once __DIR__ . "/api/v1/librarian/request.php";
            break;

        case "requests":
            require_once __DIR__ . "/api/v1/librarian/requests.php";
            break;

        case "approve":
            require_once __DIR__ . "/api/v1/librarian/approve.php";
            break;

        default:
            notFoundResponse("Librarian route not found");
    }
}


// Organization Join Routes

if (
    isset($parts[0], $parts[1], $parts[2], $parts[3]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "join"
) {
    switch ($parts[3]) {
        case "request":
            require_once __DIR__ . "/api/v1/join/request.php";
            break;

        case "requests":
            require_once __DIR__ . "/api/v1/join/requests.php";
            break;

        case "approve":
            require_once __DIR__ . "/api/v1/join/approve.php";
            break;

        default:
            notFoundResponse("Join route not found");
    }
}


// Tags Routes

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "tags"
) {
    if ($method === "GET") {
        require_once __DIR__ . "/api/v1/tags/get.php";
    }

    methodNotAllowedResponse(["GET"]);
}


// Route Not Found

notFoundResponse("Route not found");

?>