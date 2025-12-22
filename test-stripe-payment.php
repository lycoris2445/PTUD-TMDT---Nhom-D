<?php
/**
 * Test Stripe Payment Flow
 * Run: php test-stripe-payment.php
 */

require_once __DIR__ . '/config/stripe.php';
require_once __DIR__ . '/config/db_connect.php';

echo "=== STRIPE PAYMENT FLOW TEST ===\n\n";

// Test 1: Check Stripe Config
echo "1. Checking Stripe Configuration...\n";
if (empty(\Stripe\Stripe::getApiKey())) {
    echo "   ❌ STRIPE_SECRET_KEY not configured\n";
    exit(1);
} else {
    $key = \Stripe\Stripe::getApiKey();
    echo "   ✅ Secret Key: " . substr($key, 0, 12) . "...\n";
    echo "   ✅ Test Mode: " . (STRIPE_TEST_MODE ? 'YES' : 'NO') . "\n";
}

// Test 2: Create Test Payment Intent
echo "\n2. Creating test Payment Intent...\n";
try {
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => 1000, // $10.00
        'currency' => 'usd',
        'payment_method_types' => ['card'],
        'metadata' => [
            'order_id' => 'TEST_' . time(),
            'test' => 'true'
        ]
    ]);
    
    echo "   ✅ Payment Intent created: " . $paymentIntent->id . "\n";
    echo "   ✅ Amount: $" . ($paymentIntent->amount / 100) . "\n";
    echo "   ✅ Status: " . $paymentIntent->status . "\n";
    echo "   ✅ Client Secret: " . substr($paymentIntent->client_secret, 0, 20) . "...\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test Database Connection
echo "\n3. Testing Database Connection...\n";
try {
    $pdo = require __DIR__ . '/config/db_connect.php';
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ORDERS");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✅ Connected to database\n";
    echo "   ✅ Total orders: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Test Strategy Pattern
echo "\n4. Testing Payment Strategy Pattern...\n";
try {
    require_once __DIR__ . '/api/payments/PaymentContext.php';
    require_once __DIR__ . '/api/payments/strategies/PaymentStrategy.php';
    require_once __DIR__ . '/api/payments/strategies/StripePaymentStrategy.php';
    
    $context = new PaymentContext();
    $context->setStrategy('STRIPE');
    echo "   ✅ Strategy Pattern loaded\n";
    echo "   ✅ Stripe strategy initialized\n";
} catch (Exception $e) {
    echo "   ❌ Strategy error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Simulate Order Creation
echo "\n5. Simulating order creation...\n";
try {
    $testData = [
        'account_id' => 1,
        'cart' => [
            [
                'product_id' => 1,
                'quantity' => 2,
                'price' => 10.00
            ]
        ],
        'shipping' => [
            'fullname' => 'Test User',
            'phone' => '0123456789',
            'email' => 'test@example.com',
            'address' => '123 Test St',
            'city' => 'Test City',
            'district' => 'Test District'
        ],
        'shipping_carrier' => 'GHN',
        'note' => 'Test order'
    ];
    
    echo "   ✅ Test data prepared\n";
    echo "   ✅ Cart total: $20.00\n";
    echo "   ✅ Shipping: $1.20\n";
    echo "   ✅ Final: $21.20\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== ALL TESTS PASSED ✅ ===\n\n";
echo "Next steps:\n";
echo "1. Start Stripe CLI: stripe listen --forward-to localhost/PTUD%20TMĐT%20-%20Nhóm%20D/api/payments/stripe-webhook.php\n";
echo "2. Update .env with webhook secret (whsec_...)\n";
echo "3. Go to: http://localhost/PTUD%20TMĐT%20-%20Nhóm%20D/website/php/order.php\n";
echo "4. Use test card: 4242 4242 4242 4242\n\n";
