<?php
/**
 * Stripe Configuration
 * Loads Stripe SDK and configures API keys from .env
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env
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

// Stripe API Key Configuration
$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
$stripePublishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';
$stripeWebhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

// Validate required keys
if (empty($stripeSecretKey)) {
    error_log('[STRIPE CONFIG] Warning: STRIPE_SECRET_KEY not set in .env');
}

// Initialize Stripe with secret key
\Stripe\Stripe::setApiKey($stripeSecretKey);

// Define constants for easy access
define('STRIPE_PUBLISHABLE_KEY', $stripePublishableKey);
define('STRIPE_WEBHOOK_SECRET', $stripeWebhookSecret);
define('STRIPE_CURRENCY', $_ENV['STRIPE_CURRENCY'] ?? 'usd');

// Check if running in test mode
define('STRIPE_TEST_MODE', strpos($stripeSecretKey, 'sk_test_') === 0);

if (STRIPE_TEST_MODE) {
    error_log('[STRIPE] Running in TEST MODE');
} else {
    error_log('[STRIPE] Running in LIVE MODE');
}

// API endpoint to get publishable key (for frontend)
if (isset($_GET['get_pk']) && $_GET['get_pk'] == '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'publishable_key' => $stripePublishableKey,
        'test_mode' => STRIPE_TEST_MODE
    ]);
    exit;
}

// Return configuration for scripts that require this file
return [
    'secret_key' => $stripeSecretKey,
    'publishable_key' => $stripePublishableKey,
    'webhook_secret' => $stripeWebhookSecret,
    'currency' => STRIPE_CURRENCY,
    'test_mode' => STRIPE_TEST_MODE
];
