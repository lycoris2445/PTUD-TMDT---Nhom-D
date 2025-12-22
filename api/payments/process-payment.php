<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once __DIR__ . '/PaymentContext.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }

    // Get payment method
    $paymentMethod = strtoupper($input['payment_method'] ?? '');
    
    if (empty($paymentMethod)) {
        throw new Exception('Payment method is required');
    }

    // Create payment context with specified strategy
    $paymentContext = PaymentContext::createWithStrategy($paymentMethod);
    
    if ($paymentContext === null) {
        $availableMethods = (new PaymentContext())->getAvailablePaymentMethods();
        throw new Exception(
            'Invalid payment method. Available methods: ' . 
            implode(', ', $availableMethods)
        );
    }

    // Prepare order data
    $orderData = [
        'cart' => $input['cart'] ?? [],
        'shipping_info' => $input['shipping_info'] ?? [],
        'total_amount' => $input['total_amount'] ?? 0,
        'shipping_fee' => $input['shipping_fee'] ?? 0,
        'shipping_carrier' => $input['shipping_carrier'] ?? 'GHN',
        'note' => $input['note'] ?? ''
    ];

    // Validate order data
    $validation = $paymentContext->validate($orderData);
    
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validation['errors']
        ]);
        exit;
    }

    // Execute payment processing
    $result = $paymentContext->executePayment($orderData);

    // Set appropriate HTTP status code
    http_response_code($result['success'] ? 200 : 400);

    // Return result
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
