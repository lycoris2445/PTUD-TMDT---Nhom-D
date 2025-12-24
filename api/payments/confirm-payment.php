<?php
/**
 * Manual Payment Confirmation
 * Use this to manually confirm payment after successful Stripe charge
 * (Alternative to webhook in development)
 * 
 * Usage:
 * POST /api/payments/confirm-payment.php
 * Body: { payment_intent_id }
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/stripe.php';
require_once __DIR__ . '/../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $paymentIntentId = $input['payment_intent_id'] ?? '';
    
    if (empty($paymentIntentId)) {
        throw new Exception('Payment Intent ID is required');
    }
    
    // Retrieve PaymentIntent from Stripe
    $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
    
    if ($paymentIntent->status !== 'succeeded') {
        throw new Exception('Payment has not succeeded. Status: ' . $paymentIntent->status);
    }
    
    $pdo = require __DIR__ . '/../../config/db_connect.php';
    $pdo->beginTransaction();
    
    // Find payment record
    $stmt = $pdo->prepare("
        SELECT p.*, o.id as order_id
        FROM PAYMENT p
        JOIN ORDERS o ON p.order_id = o.id
        WHERE p.stripe_payment_intent_id = ?
    ");
    $stmt->execute([$paymentIntentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment record not found');
    }
    
    if ($payment['status'] === 'paid') {
        throw new Exception('Payment already confirmed');
    }
    
    $orderId = $payment['order_id'];
    
    // Update payment status
    $stmt = $pdo->prepare("
        UPDATE PAYMENT 
        SET status = 'paid',
            stripe_charge_id = ?
        WHERE stripe_payment_intent_id = ?
    ");
    
    $stmt->execute([
        $paymentIntent->latest_charge ?? null,
        $paymentIntentId
    ]);
    
    // Update order status
    $stmt = $pdo->prepare("
        UPDATE ORDERS 
        SET status = 'new'
        WHERE id = ?
    ");
    $stmt->execute([$orderId]);
    
    // Log order history (if table exists)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO ORDER_HISTORY (
                order_id, 
                previous_status, 
                new_status, 
                note,
                created_at
            ) VALUES (?, 'pending', 'on_hold', 'Payment confirmed via Stripe', NOW())
        ");
        $stmt->execute([$orderId]);
    } catch (PDOException $e) {
        // Table might not exist, just log it
        error_log('[ORDER_HISTORY] Table not found or error: ' . $e->getMessage());
    }
    
    // TODO: Reduce inventory
    // TODO: Send confirmation email
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'payment_status' => 'paid',
        'order_status' => 'on_hold',
        'message' => 'Payment confirmed successfully'
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('[STRIPE CONFIRM ERROR] ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Stripe error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('[CONFIRM PAYMENT ERROR] ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
