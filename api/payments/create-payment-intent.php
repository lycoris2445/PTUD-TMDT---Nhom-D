<?php
/**
 * Create Payment Intent API
 * Creates Stripe PaymentIntent and Order record
 * 
 * Usage:
 * POST /api/payments/create-payment-intent.php
 * Body: { cart, shipping_info, total_amount }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once __DIR__ . '/../../config/stripe.php';
require_once __DIR__ . '/../../config/db_connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $cart = $input['cart'] ?? [];
    $shippingInfo = $input['shipping_info'] ?? [];
    $totalAmount = (float)($input['total_amount'] ?? 0);
    
    if (empty($cart)) {
        throw new Exception('Cart is empty');
    }
    
    if (empty($shippingInfo['fullname']) || empty($shippingInfo['phone']) || empty($shippingInfo['address'])) {
        throw new Exception('Missing required shipping information');
    }
    
    if ($totalAmount <= 0) {
        throw new Exception('Invalid total amount');
    }
    
    // Get database connection
    $pdo = require __DIR__ . '/../../config/db_connect.php';
    $pdo->beginTransaction();
    
    // Calculate amounts
    $shippingFee = 0; // Free shipping for demo
    $finalAmount = $totalAmount + $shippingFee;
    
    // Prepare address snapshot JSON
    $addressSnapshot = json_encode($shippingInfo);
    
    // Generate tracking number
    $trackingNumber = 'DAR-TRK-' . date('Ymd') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    
    // Create order in database (status = 'pending')
    $stmt = $pdo->prepare("
        INSERT INTO ORDERS (
            account_id, 
            tracking_number,
            total_amount, 
            payment_method,
            shipping_fee,
            final_amount,
            status, 
            shipping_address_snapshot,
            created_at
        ) VALUES (?, ?, ?, 'STRIPE', ?, ?, 'pending', ?, NOW())
    ");
    
    $accountId = $_SESSION['user_id'] ?? null; // Null for guest checkout
    
    $stmt->execute([
        $accountId,
        $trackingNumber,
        $totalAmount,
        $shippingFee,
        $finalAmount,
        $addressSnapshot
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // Insert order items
    $stmt = $pdo->prepare("
        INSERT INTO ORDER_ITEMS (
            order_id, 
            product_variant_id, 
            quantity, 
            price_at_purchase
        ) VALUES (?, ?, ?, ?)
    ");
    
    foreach ($cart as $item) {
        $productId = $item['id'] ?? null;
        $quantity = (int)($item['qty'] ?? 1);
        $price = (float)($item['price'] ?? 0);
        
        if ($productId && $quantity > 0 && $price > 0) {
            $stmt->execute([$orderId, $productId, $quantity, $price]);
        }
    }
    
    // Create Stripe PaymentIntent
    // Convert to cents for Stripe (USD)
    $amountInCents = (int)($finalAmount * 100);
    
    // Ensure minimum amount (50 cents = $0.50)
    if ($amountInCents < 50) {
        $amountInCents = 50;
    }
    
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amountInCents,
        'currency' => STRIPE_CURRENCY,
        'payment_method_types' => ['card'],
        'metadata' => [
            'order_id' => (string)$orderId,
            'customer_email' => $shippingInfo['email'] ?? '',
            'customer_name' => $shippingInfo['fullname'] ?? '',
            'customer_phone' => $shippingInfo['phone'] ?? ''
        ],
        'description' => "Order #$orderId - Darling Cosmetics",
        'automatic_payment_methods' => [
            'enabled' => true,
            'allow_redirects' => 'never'
        ]
    ]);
    
    // Save payment record to database
    $stmt = $pdo->prepare("
        INSERT INTO PAYMENT (
            order_id, 
            payment_method, 
            amount, 
            currency,
            status, 
            stripe_payment_intent_id,
            created_at
        ) VALUES (?, 'STRIPE', ?, ?, 'pending', ?, NOW())
    ");
    
    $stmt->execute([
        $orderId,
        $finalAmount,
        STRIPE_CURRENCY,
        $paymentIntent->id
    ]);
    
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'client_secret' => $paymentIntent->client_secret,
        'publishable_key' => STRIPE_PUBLISHABLE_KEY,
        'amount' => $finalAmount,
        'currency' => STRIPE_CURRENCY
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Stripe API error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('[STRIPE API ERROR] ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Payment service error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // General error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('[CREATE PAYMENT ERROR] ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
