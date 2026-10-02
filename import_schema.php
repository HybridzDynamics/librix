<?php

// Database Schema Import Script

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
    // Connect to MySQL server
    $pdo = new PDO(
        "mysql:host=$Host;port=$Port;charset=utf8mb4",
        $Username,
        $Password
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.\n";
    
    // Select the database
    $pdo->exec("USE $DbName");
    echo "Selected database: $DbName\n";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/librix-backend/db.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Remove CREATE DATABASE and USE statements since we already did that
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE\s+\w+;/i', '', $sql);
    
    // Split the SQL into individual statements
    $statements = explode(';', $sql);
    
    // Execute each statement
    $count = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $count++;
                echo "Executed statement $count\n";
            } catch (PDOException $e) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "Imported $count SQL statements successfully.\n";
    echo "Database schema imported successfully!\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
