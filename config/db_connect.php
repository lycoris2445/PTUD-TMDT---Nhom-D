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
    
    // Log successful connection
    error_log("[DB] ✓ Connected successfully to database '{$db}'");
    
    // Bật lên để check connect db, xong rồi nhớ tắt ehe
    if (php_sapi_name() !== 'cli') {
        echo '<div style="position:fixed;top:10px;right:10px;background:#4caf50;color:white;padding:12px 20px;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.2);z-index:9999;font-family:Arial,sans-serif;font-size:14px;">';
        echo '✓ Database Connected: <strong>' . htmlspecialchars($db) . '</strong>';
        echo '</div>';
        echo "<script>console.log('[DB] ✓ Connected to database: {$db}');</script>";
    }
    
} catch (PDOException $e) {
    // Detailed error logging for development/debugging
    $errorMsg = $e->getMessage();
    $errorCode = $e->getCode();
    
    error_log("[DB] ✗ CONNECTION FAILED - Code: {$errorCode}, Message: {$errorMsg}");
    
    // Browser console error (only in web context)
    if (php_sapi_name() !== 'cli') {
        echo "<script>console.error('[DB] ✗ Connection failed: {$errorMsg}');</script>";
    }
    
    // Display error message
    echo '<div style="background:#ffebee;border-left:4px solid #c62828;padding:15px;margin:10px;font-family:monospace;">';
    echo '<strong>Database Connection Error</strong><br>';
    echo 'Database: ' . htmlspecialchars($db) . '<br>';
    echo 'Host: ' . htmlspecialchars($host) . ':' . htmlspecialchars($port) . '<br>';
    echo 'Error: ' . htmlspecialchars($errorMsg) . '<br>';
    echo '<br><em>Kiểm tra:</em><br>';
    echo '</div>';
    
    exit;
}

return $pdo;
