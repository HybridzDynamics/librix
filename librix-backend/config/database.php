<?php

// LibriX Database Configuration

function librix_load_dotenv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || str_starts_with($line, "#")) {
            continue;
        }

        [$key, $value] = array_pad(explode("=", $line, 2), 2, "");
        $key = trim($key);
        $value = trim($value);

        if ($key === "") {
            continue;
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }

        putenv("{$key}={$value}");
    }
}

librix_load_dotenv(dirname(__DIR__, 2) . "/.env");

function librix_env(string $key, string $default = ""): string
{
    $value = getenv($key);
    if ($value === false || $value === null || $value === "") {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    return is_string($value) ? $value : $default;
}

$Host = librix_env("DB_HOST", "127.0.0.1");
$Port = librix_env("DB_PORT", "3306");
$DbName = librix_env("DB_NAME", "librix");
$Username = librix_env("DB_USERNAME", "librix_user");
$Password = librix_env("DB_PASSWORD", "");


// Database Connection

$pdo = null;
$pdo_error = null;

try {

    $pdo = new PDO(
        "mysql:host=$Host;port=$Port;dbname=$DbName;charset=utf8mb4",
        $Username,
        $Password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    $pdo->setAttribute(
        PDO::ATTR_EMULATE_PREPARES,
        false
    );

} catch (PDOException $e) {
    $pdo = null;
    $pdo_error = $e->getMessage();

    // If this is a direct script execution or non-status/health check, return standardized JSON error
    $currentScript = $_SERVER["SCRIPT_NAME"] ?? "";
    $requestUri = $_SERVER["REQUEST_URI"] ?? "";
    $isHealthOrStatus = str_contains($currentScript, "health.php") || 
                        str_contains($currentScript, "status.php") ||
                        str_contains($requestUri, "/health") ||
                        str_contains($requestUri, "/status");

    if (!$isHealthOrStatus) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Database connection failed"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

?>