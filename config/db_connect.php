<?php
// Database connection for Laragon (MySQL) and Docker
// Usage: $pdo = require __DIR__ . '/db_connect.php';

// LOAD .ENV FILE (for local development)
// Docker environment variables will override these
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and lines without =
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Only set if not already set by Docker
            if (!isset($_ENV[$key]) && !getenv($key)) {
                $_ENV[$key] = $value;
            }
        }
    }
}

// Configuration: Docker env vars take priority over .env file
// This allows seamless switching between local and Docker environments
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? throw new Exception('.env missing: DB_HOST required'));
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? throw new Exception('.env missing: DB_PORT required'));
$db   = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? throw new Exception('.env missing: DB_NAME required')));
$user = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? throw new Exception('.env missing: DB_USER required')));
$pass = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ''));
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Log connection attempt
    error_log("[DB] Attempting connection to {$db}@{$host}:{$port} as {$user}");
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Verify connection with a test query
    $pdo->query('SELECT 1');
    
    error_log("[DB] ✓ Connected successfully");

} catch (PDOException $e) {
    // Detailed error logging for development/debugging
    $errorMsg = $e->getMessage();
    $errorCode = $e->getCode();
    
    error_log("[DB] ✗ CONNECTION FAILED - Code: {$errorCode}, Message: {$errorMsg}");
    throw $e;
}

return $pdo;
