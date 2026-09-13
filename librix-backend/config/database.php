<?php

// LibriX Database Configuration

$Host = getenv("DB_HOST") ?: "localhost";
$Port = getenv("DB_PORT") ?: "3306";
$DbName = getenv("DB_NAME") ?: "librix";
$Username = getenv("DB_USERNAME") ?: "root";
$Password = getenv("DB_PASSWORD") ?: "admin";


// Database Connection

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

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => "Database connection failed"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

?>