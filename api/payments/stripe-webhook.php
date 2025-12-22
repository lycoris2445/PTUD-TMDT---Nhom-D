<?php
/**
 * Stripe Webhook Handler for Local Development
 * 
 * Setup with Stripe CLI:
 * 1. Install Stripe CLI: https://stripe.com/docs/stripe-cli
 * 2. Login: stripe login
 * 3. Forward webhooks: stripe listen --forward-to localhost/PTUD%20TMĐT%20-%20Nhóm%20D/api/payments/stripe-webhook.php
 * 4. Copy the webhook signing secret (whsec_...) to your .env as STRIPE_WEBHOOK_SECRET
 * 5. Test: stripe trigger payment_intent.succeeded
 */

require_once __DIR__ . '/../../config/stripe.php';

// Get raw POST body
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Set JSON response header
header('Content-Type: application/json');

// Log webhook received
error_log('[STRIPE WEBHOOK] Received event');

try {
    // Verify webhook signature
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        STRIPE_WEBHOOK_SECRET
    );
    
    error_log('[STRIPE WEBHOOK] Event type: ' . $event->type);
    error_log('[STRIPE WEBHOOK] Event ID: ' . $event->id);
    
    $pdo = require __DIR__ . '/../../config/db_connect.php';
    
    // Handle different event types
    switch ($event->type) {
        case 'payment_intent.succeeded':
            handlePaymentSuccess($pdo, $event->data->object);
            break;
            
        case 'payment_intent.payment_failed':
            handlePaymentFailed($pdo, $event->data->object);
            break;
            
        case 'charge.refunded':
            handleRefund($pdo, $event->data->object);
            break;
            
        default:
            error_log('[STRIPE WEBHOOK] Unhandled event type: ' . $event->type);
    }
    
    http_response_code(200);
    echo json_encode([
        'received' => true,
        'event_id' => $event->id,
        'type' => $event->type
    ]);
    
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    error_log('[STRIPE WEBHOOK] ⚠️ Signature verification failed: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit();
    
} catch (Exception $e) {
    error_log('[STRIPE WEBHOOK] ❌ Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Webhook handler error']);
}

/**
 * Handle successful payment
 */
function handlePaymentSuccess($pdo, $paymentIntent) {
    try {
        $pdo->beginTransaction();
        
        $orderId = $paymentIntent->metadata->order_id ?? null;
        
        if (!$orderId) {
            throw new Exception('Order ID not found in payment metadata');
        }
        
        error_log("[STRIPE WEBHOOK] Processing payment success for Order #$orderId");
        
        // Update payment status
        $stmt = $pdo->prepare("
            UPDATE PAYMENT 
            SET status = 'paid',
                stripe_charge_id = ?
            WHERE stripe_payment_intent_id = ?
        ");
        
        $stmt->execute([
            $paymentIntent->latest_charge ?? null,
            $paymentIntent->id
        ]);
        
        $updated = $stmt->rowCount();
        
        if ($updated === 0) {
            throw new Exception("Payment record not found for payment_intent: {$paymentIntent->id}");
        }
        
        // Update order status to 'on_hold' (awaiting fulfillment)
        $stmt = $pdo->prepare("
            UPDATE ORDERS 
            SET status = 'on_hold'
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$orderId]);
        
        // Try to log to ORDER_HISTORY if table exists
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ORDER_HISTORY (
                    order_id, 
                    previous_status, 
                    new_status, 
                    note,
                    created_at
                ) VALUES (?, 'pending', 'on_hold', 'Payment confirmed via Stripe webhook', NOW())
            ");
            $stmt->execute([$orderId]);
        } catch (Exception $e) {
            // ORDER_HISTORY table might not exist - that's okay
            error_log('[STRIPE WEBHOOK] Could not log to ORDER_HISTORY: ' . $e->getMessage());
        }
        
        $pdo->commit();
        
        error_log("[STRIPE WEBHOOK] ✅ Payment successful for Order #$orderId");
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[STRIPE WEBHOOK] ❌ Error handling payment success: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle failed payment
 */
function handlePaymentFailed($pdo, $paymentIntent) {
    try {
        $orderId = $paymentIntent->metadata->order_id ?? null;
        
        if (!$orderId) {
            error_log('[STRIPE WEBHOOK] Payment failed but no order_id in metadata');
            return;
        }
        
        error_log("[STRIPE WEBHOOK] Processing payment failure for Order #$orderId");
        
        // Update payment status
        $stmt = $pdo->prepare("
            UPDATE PAYMENT 
            SET status = 'failed'
            WHERE stripe_payment_intent_id = ?
        ");
        $stmt->execute([$paymentIntent->id]);
        
        // Update order status to 'cancelled'
        $stmt = $pdo->prepare("
            UPDATE ORDERS 
            SET status = 'cancelled'
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        // Try to log to ORDER_HISTORY
        try {
            $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';
            $stmt = $pdo->prepare("
                INSERT INTO ORDER_HISTORY (
                    order_id, 
                    previous_status, 
                    new_status, 
                    note,
                    created_at
                ) VALUES (?, 'pending', 'cancelled', ?, NOW())
            ");
            $stmt->execute([$orderId, "Payment failed: $errorMessage"]);
        } catch (Exception $e) {
            error_log('[STRIPE WEBHOOK] Could not log to ORDER_HISTORY: ' . $e->getMessage());
        }
        
        error_log("[STRIPE WEBHOOK] ⚠️ Payment failed for Order #$orderId");
        
    } catch (Exception $e) {
        error_log('[STRIPE WEBHOOK] ❌ Error handling payment failure: ' . $e->getMessage());
    }
}

/**
 * Handle refund processed
 */
function handleRefund($pdo, $charge) {
    try {
        $pdo->beginTransaction();
        
        error_log("[STRIPE WEBHOOK] Processing refund for charge: {$charge->id}");
        
        // Find payment by charge ID
        $stmt = $pdo->prepare("
            SELECT p.id as payment_id, p.order_id
            FROM PAYMENT p
            WHERE p.stripe_charge_id = ?
        ");
        $stmt->execute([$charge->id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            error_log("[STRIPE WEBHOOK] ⚠️ Payment not found for charge: {$charge->id}");
            $pdo->rollBack();
            return;
        }
        
        // Update refund records to 'completed'
        foreach ($charge->refunds->data as $refund) {
            $stmt = $pdo->prepare("
                UPDATE REFUND
                SET status = 'completed',
                    refunded_at = NOW()
                WHERE stripe_refund_id = ?
            ");
            $stmt->execute([$refund->id]);
            
            error_log("[STRIPE WEBHOOK] Updated refund: {$refund->id}");
        }
        
        // Check if fully refunded
        if ($charge->amount_refunded >= $charge->amount) {
            // Update order status to 'refunded'
            $stmt = $pdo->prepare("
                UPDATE ORDERS
                SET status = 'refunded'
                WHERE id = ?
            ");
            $stmt->execute([$payment['order_id']]);
            
            error_log("[STRIPE WEBHOOK] Order #{$payment['order_id']} marked as refunded");
        }
        
        $pdo->commit();
        
        error_log("[STRIPE WEBHOOK] ✅ Refund processed for Order #{$payment['order_id']}");
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[STRIPE WEBHOOK] ❌ Error handling refund: ' . $e->getMessage());
    }
}
