<?php

// Database Reset Script
// This script will drop and recreate the librix database

function librix_load_dotenv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === "" || str_starts_with($line, "#")) {
            continue;
        }

        [$key, $value] = array_pad(explode("=", $line, 2), 2, "");
        $key = trim($key);
        $value = trim($value);

        if ($key !== "") {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

librix_load_dotenv(__DIR__ . "/.env");

$Host = getenv("DB_HOST") ?: "127.0.0.1";
$Port = getenv("DB_PORT") ?: "3306";
$DbName = getenv("DB_NAME") ?: "librix";
$Username = getenv("DB_USERNAME") ?: "librix_user";
$Password = getenv("DB_PASSWORD") ?: "";

try {
    // Connect to MySQL server (without selecting database)
    $pdo = new PDO(
        "mysql:host=$Host;port=$Port;charset=utf8mb4",
        $Username,
        $Password
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.\n";
    
    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS $DbName");
    echo "Dropped existing database (if it existed).\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE $DbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Created new database: $DbName\n";
    
    echo "Database reset completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
