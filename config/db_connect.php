<?php
// Database connection for Laragon (MySQL)
// Usage: $pdo = require __DIR__ . '/db_connect.php';
// LOAD .ENV FILE
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Configuration from .env
$host = $_ENV['DB_HOST'] ?? throw new Exception('.env missing: DB_HOST required');
$port = $_ENV['DB_PORT'] ?? throw new Exception('.env missing: DB_PORT required');
$db   = $_ENV['DB_NAME'] ?? throw new Exception('.env missing: DB_NAME required');
$user = $_ENV['DB_USER'] ?? throw new Exception('.env missing: DB_USER required');
$pass = $_ENV['DB_PASS'] ?? '';  // password có thể empty
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Log connection attempt
    error_log("[DB] Attempting connection to {$db}@{$host}:{$port}");
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Verify connection with a test query
    $pdo->query('SELECT 1');

} catch (PDOException $e) {
    // Detailed error logging for development/debugging
    $errorMsg = $e->getMessage();
    $errorCode = $e->getCode();
    
    error_log("[DB] ✗ CONNECTION FAILED - Code: {$errorCode}, Message: {$errorMsg}");
    exit;
}

return $pdo;
