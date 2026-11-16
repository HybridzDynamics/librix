<?php

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/helpers/response.php";
require_once __DIR__ . "/helpers/validation.php";
require_once __DIR__ . "/helpers/functions.php";
require_once __DIR__ . "/middleware/auth.php";
require_once __DIR__ . "/middleware/admin.php";
require_once __DIR__ . "/middleware/librarian.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$path = trim($path, "/");

$basePath = trim(dirname($_SERVER["SCRIPT_NAME"]), "/");

if ($basePath !== "" && str_starts_with($path, $basePath . "/")) {
    $path = substr($path, strlen($basePath) + 1);
}

if (str_starts_with($path, "index.php/")) {
    $path = substr($path, strlen("index.php/"));
} elseif ($path === "index.php") {
    $path = "";
}

$path = trim($path, "/");

$parts = $path === "" ? [] : explode("/", $path);

if (isset($parts[0]) && $parts[0] === "docs") {
    require_once __DIR__ . "/docs/index.php";
    exit;
}

if (empty($parts)) {
    successResponse([
        "name" => APP_NAME,
        "version" => APP_VERSION,
        "api" => API_VERSION,
        "status" => SERVER_STATUS
    ]);
}

$isApi = isset($parts[0], $parts[1]) && $parts[0] === "api" && $parts[1] === API_VERSION;

if ($isApi && count($parts) === 2) {
    successResponse([
        "name" => APP_NAME,
        "version" => APP_VERSION,
        "api" => API_VERSION,
        "status" => SERVER_STATUS
    ]);
}

if (!$isApi) {
    if (isset($parts[0]) && $parts[0] === "uploads") {
        $filePath = __DIR__ . "/" . implode("/", $parts);

        if (file_exists($filePath) && is_file($filePath)) {
            $mimeType = mime_content_type($filePath);
            header("Content-Type: $mimeType");
            header("Content-Length: " . filesize($filePath));
            readfile($filePath);
            exit;
        }

        notFoundResponse("File not found");
    }

    notFoundResponse("Route not found");
}

$route = $parts[2] ?? null;
$subRoute = $parts[3] ?? null;

switch ($route) {

    case "health":
        require_once __DIR__ . "/api/v1/health.php";
        break;

    case "auth":
        $authRoutes = [
            "login" => "login.php",
            "register" => "register.php",
            "logout" => "logout.php",
            "session" => "session.php",
            "forgot-password" => "forgot-password.php",
            "reset-password" => "reset-password.php",
            "verify-email" => "verify-email.php",
            "resend-verification" => "resend-verification.php",
            "upload_profile_picture" => "upload_profile_picture.php"
        ];

        if ($subRoute !== null && isset($authRoutes[$subRoute])) {
            require_once __DIR__ . "/api/v1/auth/" . $authRoutes[$subRoute];
        }

        notFoundResponse("Auth route not found");
        break;

    case "books":
        $bookId = $subRoute ?? $_GET["id"] ?? null;

        if ($method === "GET" && $subRoute === "search") {
            require_once __DIR__ . "/api/v1/books/search.php";
        } elseif ($method === "GET") {
            require_once __DIR__ . "/api/v1/books/get.php";
        } elseif ($method === "POST" && $bookId === null) {
            require_once __DIR__ . "/api/v1/books/create.php";
        } elseif ($method === "PUT" && $bookId !== null) {
            require_once __DIR__ . "/api/v1/books/update.php";
        } elseif ($method === "DELETE" && $bookId !== null) {
            require_once __DIR__ . "/api/v1/books/delete.php";
        }

        methodNotAllowedResponse(["GET", "POST", "PUT", "DELETE"]);
        break;

    case "authors":
    case "categories":
    case "publishers":
    case "organizations":
        $resourceId = $subRoute ?? $_GET["id"] ?? null;
        $authorId = $resourceId;
        $categoryId = $resourceId;
        $publisherId = $resourceId;
        $orgId = $resourceId;

        if ($method === "GET") {
            require_once __DIR__ . "/api/v1/$route/get.php";
        }

        if ($method === "POST" && $resourceId === null) {
            require_once __DIR__ . "/api/v1/$route/create.php";
        }

        if ($method === "PUT" && $resourceId !== null) {
            require_once __DIR__ . "/api/v1/$route/update.php";
        }

        if ($method === "DELETE" && $resourceId !== null) {
            require_once __DIR__ . "/api/v1/$route/delete.php";
        }

        methodNotAllowedResponse(["GET", "POST", "PUT", "DELETE"]);
        break;

    case "reviews":
        $reviewId = $subRoute ?? null;

        if ($method === "GET") {
            require_once __DIR__ . "/api/v1/reviews/get.php";
        }

        if ($method === "POST") {
            require_once __DIR__ . "/api/v1/reviews/create.php";
        }

        if ($method === "DELETE") {
            require_once __DIR__ . "/api/v1/reviews/delete.php";
        }

        methodNotAllowedResponse(["GET", "POST", "DELETE"]);
        break;

    case "favorites":
        if ($method === "GET") {
            require_once __DIR__ . "/api/v1/favorites/get.php";
        }

        if ($method === "POST") {
            require_once __DIR__ . "/api/v1/favorites/toggle.php";
        }

        methodNotAllowedResponse(["GET", "POST"]);
        break;

    case "notifications":
        if ($method === "GET") {
            require_once __DIR__ . "/api/v1/notifications/get.php";
        }

        if ($method === "PUT" || $method === "POST") {
            require_once __DIR__ . "/api/v1/notifications/read.php";
        }

        methodNotAllowedResponse(["GET", "PUT", "POST"]);
        break;

    case "readability":
        if ($method === "GET") {
            require_once __DIR__ . "/api/v1/readability/get.php";
        }

        if ($method === "POST") {
            require_once __DIR__ . "/api/v1/readability/analyze.php";
        }

        methodNotAllowedResponse(["GET", "POST"]);
        break;

    case "library":
        $libraryRoutes = [
            "issue" => "issue.php",
            "return" => "return.php",
            "reserve" => "reserve.php",
            "cancel-reservation" => "cancel-reservation.php",
            "my-books" => "my-books.php",
            "renew" => "renew.php",
            "history" => "history.php"
        ];

        if ($subRoute !== null && isset($libraryRoutes[$subRoute])) {
            require_once __DIR__ . "/api/v1/library/" . $libraryRoutes[$subRoute];
        }

        notFoundResponse("Library route not found");
        break;

    case "recommendations":
        if ($method === "GET" && $subRoute === null) {
            require_once __DIR__ . "/api/v1/recommendations/get.php";
        }

        if ($method === "GET" && $subRoute === "index") {
            require_once __DIR__ . "/api/v1/recommendations/index.php";
        }

        methodNotAllowedResponse(["GET"]);
        break;

    case "fines":
        if ($method === "GET" && $subRoute === null) {
            require_once __DIR__ . "/api/v1/fines/get.php";
        }

        if ($method === "POST" && $subRoute === "pay") {
            require_once __DIR__ . "/api/v1/fines/pay.php";
        }

        methodNotAllowedResponse(["GET", "POST"]);
        break;

    case "upload":
        if ($method === "POST") {
            require_once __DIR__ . "/api/v1/upload/index.php";
        }

        methodNotAllowedResponse(["POST"]);
        break;

    case "users":
        $userRoutes = [
            "get" => "get.php",
            "profile" => "profile.php",
            "update" => "update.php"
        ];

        if ($subRoute !== null && isset($userRoutes[$subRoute])) {
            require_once __DIR__ . "/api/v1/users/" . $userRoutes[$subRoute];
        }

        notFoundResponse("User route not found");
        break;

    case "status":
        if ($subRoute === null) {
            require_once __DIR__ . "/api/v1/status.php";
        }

        $statusRoutes = [
            "services" => "services.php",
            "incidents" => "incidents.php",
            "uptime" => "uptime.php",
            "history" => "history.php",
            "maintenance" => "maintenance.php",
            "status" => "status.php"
        ];

        if ($subRoute !== null && isset($statusRoutes[$subRoute])) {
            require_once __DIR__ . "/api/v1/status/" . $statusRoutes[$subRoute];
        }

        notFoundResponse("Status route not found");
        break;

    case "admin":
        $adminResourceId = $parts[4] ?? null;
        $adminAction = $parts[5] ?? null;

        switch ($subRoute) {
            case "users":
                if ($method === "PUT" || $method === "PATCH") {
                    require_once __DIR__ . "/api/v1/admin/users.php";
                } elseif ($adminResourceId !== null && $adminAction === "delete") {
                    require_once __DIR__ . "/api/v1/admin/users/delete.php";
                } else {
                    require_once __DIR__ . "/api/v1/admin/users.php";
                }
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

            case "audit-logs":
                require_once __DIR__ . "/api/v1/admin/audit-logs.php";
                break;

            case "status":
                $adminStatusSubRoute = $parts[4] ?? null;
                $adminStatusRoutes = [
                    "incidents" => "incidents.php",
                    "maintenance" => "maintenance.php"
                ];

                if ($adminStatusSubRoute !== null && isset($adminStatusRoutes[$adminStatusSubRoute])) {
                    require_once __DIR__ . "/api/v1/admin/status/" . $adminStatusRoutes[$adminStatusSubRoute];
                }

                notFoundResponse("Admin status route not found");
                break;

            default:
                notFoundResponse("Admin route not found");
        }
        break;

    case "librarian":
        $librarianRoutes = [
            "request" => "request.php",
            "requests" => "requests.php",
            "approve" => "approve.php",
            "books" => "books.php",
            "link_book" => "link_book.php",
            "search_books" => "search_books.php",
            "stats" => "stats.php",
            "set_org" => "set_org.php"
        ];

        if ($subRoute !== null && isset($librarianRoutes[$subRoute])) {
            require_once __DIR__ . "/api/v1/librarian/" . $librarianRoutes[$subRoute];
        }

        notFoundResponse("Librarian route not found");
        break;

    case "join":
        $joinRoutes = [
            "request" => "request.php",
            "requests" => "requests.php",
            "approve" => "approve.php"
        ];

        if ($subRoute !== null && isset($joinRoutes[$subRoute])) {
            require_once __DIR__ . "/api/v1/join/" . $joinRoutes[$subRoute];
        }

        notFoundResponse("Join route not found");
        break;

    case "tags":
        if ($method === "GET") {
            require_once __DIR__ . "/api/v1/tags/get.php";
        }

        methodNotAllowedResponse(["GET"]);
        break;

    case "contact":
        if ($method === "POST" && $subRoute === "submit") {
            require_once __DIR__ . "/api/v1/contact/submit.php";
        }

        notFoundResponse("Contact route not found");
        break;

    default:
        notFoundResponse("Route not found");
}

?>
