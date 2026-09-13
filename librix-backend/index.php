<?php

// Configuration

require __DIR__ . "/config/config.php";
require __DIR__ . "/helpers/response.php";
require __DIR__ . "/helpers/validation.php";
require __DIR__ . "/helpers/functions.php";
require __DIR__ . "/middleware/auth.php";
require __DIR__ . "/middleware/admin.php";


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

if ($basePath !== "" && str_starts_with($path, $basePath)) {
    $path = trim(substr($path, strlen($basePath)), "/");
}


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
}


// Health Check Route

if (
    isset($parts[0], $parts[1], $parts[2]) &&
    $parts[0] === "api" &&
    $parts[1] === API_VERSION &&
    $parts[2] === "health"
) {
    require __DIR__ . "/api/v1/health.php";
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
                require __DIR__ . "/api/v1/auth/login.php";
                break;

            case "register":
                require __DIR__ . "/api/v1/auth/register.php";
                break;

            case "logout":
                require __DIR__ . "/api/v1/auth/logout.php";
                break;

            case "session":
                require __DIR__ . "/api/v1/auth/session.php";
                break;

            case "forgot-password":
                require __DIR__ . "/api/v1/auth/forgot-password.php";
                break;

            case "reset-password":
                require __DIR__ . "/api/v1/auth/reset-password.php";
                break;

            case "verify-email":
                require __DIR__ . "/api/v1/auth/verify-email.php";
                break;

            case "resend-verification":
                require __DIR__ . "/api/v1/auth/resend-verification.php";
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
        require __DIR__ . "/api/v1/books/get.php";
    }

    if ($method === "POST" && $bookId === null) {
        require __DIR__ . "/api/v1/books/create.php";
    }

    if ($method === "GET" && $bookId !== null) {
        require __DIR__ . "/api/v1/books/get.php";
    }

    if ($method === "PUT" && $bookId !== null) {
        require __DIR__ . "/api/v1/books/update.php";
    }

    if ($method === "DELETE" && $bookId !== null) {
        require __DIR__ . "/api/v1/books/delete.php";
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
        require __DIR__ . "/api/v1/authors/get.php";
    }

    if ($method === "POST" && $authorId === null) {
        require __DIR__ . "/api/v1/authors/create.php";
    }

    if ($method === "PUT" && $authorId !== null) {
        require __DIR__ . "/api/v1/authors/update.php";
    }

    if ($method === "DELETE" && $authorId !== null) {
        require __DIR__ . "/api/v1/authors/delete.php";
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
        require __DIR__ . "/api/v1/categories/get.php";
    }

    if ($method === "POST" && $categoryId === null) {
        require __DIR__ . "/api/v1/categories/create.php";
    }

    if ($method === "PUT" && $categoryId !== null) {
        require __DIR__ . "/api/v1/categories/update.php";
    }

    if ($method === "DELETE" && $categoryId !== null) {
        require __DIR__ . "/api/v1/categories/delete.php";
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
        require __DIR__ . "/api/v1/publishers/get.php";
    }

    if ($method === "POST" && $publisherId === null) {
        require __DIR__ . "/api/v1/publishers/create.php";
    }

    if ($method === "PUT" && $publisherId !== null) {
        require __DIR__ . "/api/v1/publishers/update.php";
    }

    if ($method === "DELETE" && $publisherId !== null) {
        require __DIR__ . "/api/v1/publishers/delete.php";
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
        require __DIR__ . "/api/v1/reviews/get.php";
    }

    if ($method === "POST") {
        require __DIR__ . "/api/v1/reviews/create.php";
    }

    if ($method === "DELETE") {
        require __DIR__ . "/api/v1/reviews/delete.php";
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
        require __DIR__ . "/api/v1/favorites/get.php";
    }

    if ($method === "POST") {
        require __DIR__ . "/api/v1/favorites/toggle.php";
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
        require __DIR__ . "/api/v1/notifications/get.php";
    }

    if ($method === "PUT" || $method === "POST") {
        require __DIR__ . "/api/v1/notifications/read.php";
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
        require __DIR__ . "/api/v1/readability/get.php";
    }

    if ($method === "POST") {
        require __DIR__ . "/api/v1/readability/analyze.php";
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
    switch $GLOBALS["parts"][3] {
        case "issue":
            require __DIR__ . "/api/v1/library/issue.php";
            break;

        case "return":
            require __DIR__ . "/api/v1/library/return.php";
            break;

        case "reserve":
            require __DIR__ . "/api/v1/library/reserve.php";
            break;

        case "cancel-reservation":
            require __DIR__ . "/api/v1/library/cancel-reservation.php";
            break;

        case "my-books":
            require __DIR__ . "/api/v1/library/my-books.php";
            break;

        case "renew":
            require __DIR__ . "/api/v1/library/renew.php";
            break;

        case "history":
            require __DIR__ . "/api/v1/library/history.php";
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
    switch $GLOBALS["parts"][3] {
        case "profile":
            require __DIR__ . "/api/v1/users/profile.php";
            break;

        case "update":
            require __DIR__ . "/api/v1/users/update.php";
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
        require __DIR__ . "/api/v1/status/status.php";
    }

    switch ($statusSubRoute) {
        case "services":
            require __DIR__ . "/api/v1/status/services.php";
            break;

        case "incidents":
            require __DIR__ . "/api/v1/status/incidents.php";
            break;

        case "uptime":
            require __DIR__ . "/api/v1/status/uptime.php";
            break;

        case "history":
            require __DIR__ . "/api/v1/status/history.php";
            break;

        case "maintenance":
            require __DIR__ . "/api/v1/status/maintenance.php";
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
    switch $GLOBALS["parts"][3] {
        case "users":
            require __DIR__ . "/api/v1/admin/users.php";
            break;

        case "books":
            require __DIR__ . "/api/v1/admin/books.php";
            break;

        case "statistics":
            require __DIR__ . "/api/v1/admin/statistics.php";
            break;

        case "issues":
            require __DIR__ . "/api/v1/admin/issues.php";
            break;

        case "reservations":
            require __DIR__ . "/api/v1/admin/reservations.php";
            break;

        case "fines":
            require __DIR__ . "/api/v1/admin/fines.php";
            break;

        case "status":
            $adminStatusSub = $parts[4] ?? null;
            if ($adminStatusSub === "incidents") {
                require __DIR__ . "/api/v1/admin/status/incidents.php";
            } elseif ($adminStatusSub === "maintenance") {
                require __DIR__ . "/api/v1/admin/status/maintenance.php";
            } else {
                notFoundResponse("Admin status route not found");
            }
            break;

        default:
            notFoundResponse("Admin route not found");
    }
}


// Route Not Found

notFoundResponse("Route not found");

?>