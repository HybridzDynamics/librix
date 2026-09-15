<?php

// LibriX Database Configuration

$Host = getenv("DB_HOST") ?: "localhost";
$Port = getenv("DB_PORT") ?: "3306";
$DbName = getenv("DB_NAME") ?: "librix";
$Username = getenv("DB_USERNAME") ?: "root";
$Password = getenv("DB_PASSWORD") ?: "admin";


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