<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/stripe.php';
require_once __DIR__ . '/../../config/db_connect.php';

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Login required']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = (int)($input['order_id'] ?? 0);
    $refundAmount = isset($input['amount']) ? (float)$input['amount'] : null;
    $reason = $input['reason'] ?? 'Requested by admin';
    
    if (!$orderId) {
        throw new Exception('Order ID is required');
    }
    
    // Get database connection
    $pdo = require __DIR__ . '/../../config/db_connect.php';
    
    // Verify order belongs to user (security check)
    $stmt = $pdo->prepare("SELECT account_id FROM ORDERS WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order || $order['account_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        throw new Exception('You do not have permission to refund this order');
    }
    
    // Get payment information
    $stmt = $pdo->prepare("
        SELECT p.*, o.final_amount, o.status as order_status, o.payment_method
        FROM PAYMENT p
        JOIN ORDERS o ON p.order_id = o.id
        WHERE p.order_id = ? 
            AND p.status = 'paid'
        ORDER BY p.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$orderId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment not found or not eligible for refund. Payment must be paid.');
    }
    
    // Check if payment method is Stripe
    if ($payment['payment_method'] !== 'STRIPE') {
        throw new Exception('Only Stripe payments can be refunded online. For COD orders, please contact support.');
    }
    
    if (empty($payment['stripe_charge_id'])) {
        throw new Exception('Missing Stripe charge ID. Cannot process refund.');
    }
    
    // Check if already refunded
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_refunded 
        FROM REFUND 
        WHERE order_id = ? AND status IN ('processing', 'completed')
    ");
    $stmt->execute([$orderId]);
    $refundCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRefunded = (float)($refundCheck['total_refunded'] ?? 0);
    
    // Determine refund amount
    if ($refundAmount === null) {
        // Full refund
        $refundAmount = $payment['final_amount'] - $totalRefunded;
    }
    
    // Validate refund amount
    if ($refundAmount <= 0) {
        throw new Exception('Invalid refund amount');
    }
    
    $maxRefundable = $payment['final_amount'] - $totalRefunded;
    if ($refundAmount > $maxRefundable) {
        throw new Exception("Refund amount exceeds maximum refundable amount ($maxRefundable)");
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert refund request record with status "pending"
    // Admin will approve/reject later
    $stmt = $pdo->prepare("
        INSERT INTO REFUND (
            payment_id,
            order_id,
            amount,
            reason,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $payment['id'],
        $orderId,
        $refundAmount,
        $reason
    ]);
    
    $refundId = $pdo->lastInsertId();
    
    // Update order status to on_hold to prevent further processing
    $stmt = $pdo->prepare("
        UPDATE ORDERS 
        SET status = 'on_hold' 
        WHERE id = ? AND status IN ('pending', 'on_hold')
    ");
    $stmt->execute([$orderId]);
    
    // Log order history
    $stmt = $pdo->prepare("
        INSERT INTO ORDER_HISTORY (order_id, previous_status, new_status, note, created_at)
        VALUES (?, ?, 'on_hold', ?, NOW())
    ");
    $stmt->execute([
        $orderId,
        $payment['order_status'],
        "Refund request submitted by customer. Refund ID: $refundId. Reason: $reason"
    ]);
    
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'refund_id' => $refundId,
        'amount' => $refundAmount,
        'status' => 'pending',
        'message' => 'Refund request submitted successfully. Our team will review it shortly.'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('[REFUND REQUEST ERROR] ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
