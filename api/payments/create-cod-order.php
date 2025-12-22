<?php
/**
 * Create COD Order API
 * Creates order with Cash on Delivery payment method
 * 
 * Usage:
 * POST /api/payments/create-cod-order.php
 * Body: { cart, shipping_info, total_amount, shipping_fee, note }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once __DIR__ . '/../../config/db_connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
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
    $shippingFee = (float)($input['shipping_fee'] ?? 0);
    $shippingCarrier = $input['shipping_carrier'] ?? 'GHN'; // Default: Giao hàng nhanh
    $note = $input['note'] ?? '';
    
    if (empty($cart)) {
        throw new Exception('Cart is empty');
    }
    
    if (empty($shippingInfo['fullname']) || empty($shippingInfo['phone']) || empty($shippingInfo['address'])) {
        throw new Exception('Full name, phone, and address are required');
    }
    
    if ($totalAmount <= 0) {
        throw new Exception('Invalid total amount');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Generate tracking number
    $trackingNumber = 'COD' . date('YmdHis') . rand(1000, 9999);
    
    // Calculate final amount
    $finalAmount = $totalAmount + $shippingFee;
    
    // Prepare shipping address as JSON
    $shippingAddressJson = json_encode([
        'fullname' => $shippingInfo['fullname'],
        'phone' => $shippingInfo['phone'],
        'email' => $shippingInfo['email'] ?? '',
        'address' => $shippingInfo['address'],
        'city' => $shippingInfo['city'] ?? '',
        'district' => $shippingInfo['district'] ?? '',
        'note' => $note
    ], JSON_UNESCAPED_UNICODE);
    
    // Get account_id from session if logged in
    $accountId = $_SESSION['user_id'] ?? null;
    
    // Insert into ORDERS table
    $sqlOrder = "INSERT INTO ORDERS (
        account_id,
        tracking_number,
        total_amount,
        payment_method,
        shipping_fee,
        shipping_carrier,
        final_amount,
        status,
        shipping_address_snapshot,
        created_at
    ) VALUES (?, ?, ?, 'COD', ?, ?, ?, 'pending', ?, NOW())";
    
    $stmtOrder = $pdo->prepare($sqlOrder);
    $stmtOrder->execute([
        $accountId,
        $trackingNumber,
        $totalAmount,
        $shippingFee,
        $shippingCarrier,
        $finalAmount,
        $shippingAddressJson
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // Insert order items
    // Check ORDER_ITEMS table structure
    $checkColumns = $pdo->query("SHOW COLUMNS FROM ORDER_ITEMS");
    $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
    
    $hasProductVariantId = in_array('product_variant_id', $columns);
    $hasPriceAtPurchase = in_array('price_at_purchase', $columns);
    
    $productCol = $hasProductVariantId ? 'product_variant_id' : 'product_id';
    $priceCol = $hasPriceAtPurchase ? 'price_at_purchase' : 'price';
    
    $sqlItem = "INSERT INTO ORDER_ITEMS (
        order_id,
        $productCol,
        quantity,
        $priceCol
    ) VALUES (?, ?, ?, ?)";
    
    $stmtItem = $pdo->prepare($sqlItem);
    
    foreach ($cart as $item) {
        $productId = (int)$item['id'];
        $quantity = (int)($item['qty'] ?? 1);
        $price = (float)$item['price'];
        
        $stmtItem->execute([$orderId, $productId, $quantity, $price]);
    }
    
    // Create PAYMENT record (minimal columns)
    $sqlPayment = "INSERT INTO PAYMENT (
        order_id,
        amount,
        status,
        created_at
    ) VALUES (?, ?, 'pending', NOW())";
    
    $stmtPayment = $pdo->prepare($sqlPayment);
    $stmtPayment->execute([$orderId, $finalAmount]);
    
    $paymentId = $pdo->lastInsertId();
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'tracking_number' => $trackingNumber,
        'payment_id' => $paymentId,
        'message' => 'Order created successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
