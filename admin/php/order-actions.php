<?php
declare(strict_types=1);

session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || !isset($_SESSION['admin_role'])) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Unauthorized']));
}

$allowed_roles = ['super_admin', 'operation_staff'];
if (!in_array($_SESSION['admin_role'], $allowed_roles)) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Access denied']));
}

require_once __DIR__ . '/../includes/function-order-management.php';

try {
    /** @var PDO $conn */
    $conn = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection error: " . htmlspecialchars($e->getMessage()));
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect_back(): void
{
    // quay về trang management (giữ filter nếu có)
    $qs = $_POST['return_query'] ?? '';
    header('Location: order-management.php' . ($qs ? ('?' . $qs) : ''));
    exit;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
    flash('danger', 'CSRF token invalid.');
    redirect_back();
}

$action = (string)($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('danger', 'Invalid order id.');
    redirect_back();
}

if ($action === 'update') {
    $data = [
        'tracking_number'  => trim((string)($_POST['tracking_number'] ?? '')),
        'shipping_carrier' => trim((string)($_POST['shipping_carrier'] ?? '')),
        'status'           => trim((string)($_POST['status'] ?? '')),
    ];
    $note = trim((string)($_POST['note'] ?? ''));
    if ($note === '') $note = null;

    $current = get_order_by_id($conn, $id);
    if (!$current) {
        flash('danger', "Order #{$id} not found.");
        redirect_back();
    }

    $prev = (string)$current['status'];
    $to   = (string)$data['status'];

    if ($to !== '' && $to !== $prev && !can_transition_status($prev, $to)) {
        flash('danger', "Cannot change status from '{$prev}' to '{$to}'. Valid flow: new → processing → awaiting_pickup → shipping → shipped → completed.");
        redirect_back();
    }

    $ok = update_order($conn, $id, $data, $note);
    flash($ok ? 'success' : 'danger', $ok ? "Updated order #{$id}." : "Failed to update order #{$id}.");

    redirect_back();
}

if ($action === 'return_update') {
    $returnId = (int)($_POST['return_id'] ?? 0);
    
    if ($returnId <= 0) {
        flash('danger', 'Invalid return ID.');
        redirect_back();
    }
    
    // Get current return status
    $stmt = $conn->prepare("SELECT * FROM returns WHERE id = ? LIMIT 1");
    $stmt->execute([$returnId]);
    $currentReturn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentReturn) {
        flash('danger', "Return #{$returnId} not found.");
        redirect_back();
    }
    
    $status = trim((string)($_POST['return_status'] ?? ''));
    $adminNote = trim((string)($_POST['admin_note'] ?? ''));
    $refundAmount = trim((string)($_POST['refund_amount'] ?? ''));
    
    // Validate status transition
    $prevStatus = (string)$currentReturn['status'];
    
    if ($status !== '' && $status !== $prevStatus) {
        if (!can_transition_return_status($prevStatus, $status)) {
            flash('danger', "Invalid status transition from '{$prevStatus}' to '{$status}'.");
            redirect_back();
        }
    } else {
        $status = $prevStatus;
    }
    
    // If accepting refund, process actual refund via Stripe
    if ($status === 'accept_refund' && $prevStatus !== 'accept_refund') {
        // Get order and payment details
        $stmt = $conn->prepare("
            SELECT o.*, p.stripe_charge_id, p.id as payment_id
            FROM orders o
            LEFT JOIN PAYMENT p ON o.id = p.order_id
            WHERE o.id = ?
        ");
        $stmt->execute([$currentReturn['order_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            flash('danger', 'Order not found.');
            redirect_back();
        }
        
        // Determine refund amount
        $actualRefundAmount = !empty($refundAmount) ? (float)$refundAmount : (float)$currentReturn['refund_amount'];
        
        if ($actualRefundAmount <= 0) {
            flash('danger', 'Invalid refund amount.');
            redirect_back();
        }
        
        // Process refund based on payment method
        if ($order['payment_method'] === 'STRIPE' && !empty($order['stripe_charge_id'])) {
            // Process Stripe refund
            try {
                require_once __DIR__ . '/../../config/stripe.php';
                
                $refundAmountCents = (int)($actualRefundAmount * 100);
                
                $refund = \Stripe\Refund::create([
                    'charge' => $order['stripe_charge_id'],
                    'amount' => $refundAmountCents,
                    'reason' => 'requested_by_customer',
                    'metadata' => [
                        'order_id' => (string)$currentReturn['order_id'],
                        'return_id' => (string)$returnId,
                        'admin_note' => $adminNote
                    ]
                ]);
                
                // Insert refund record
                $stmt = $conn->prepare("
                    INSERT INTO REFUND (
                        payment_id,
                        order_id,
                        amount,
                        reason,
                        status,
                        refund_transaction_ref,
                        stripe_refund_id,
                        created_at,
                        refunded_at
                    ) VALUES (?, ?, ?, ?, 'completed', ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $order['payment_id'],
                    $currentReturn['order_id'],
                    $actualRefundAmount,
                    'Return approved - Return ID: ' . $returnId,
                    $refund->id,
                    $refund->id
                ]);
                
            } catch (\Stripe\Exception\ApiErrorException $e) {
                error_log('[ADMIN REFUND ERROR] ' . $e->getMessage());
                flash('danger', 'Stripe refund failed: ' . $e->getMessage());
                redirect_back();
            } catch (Exception $e) {
                error_log('[ADMIN REFUND ERROR] ' . $e->getMessage());
                flash('danger', 'Refund processing failed: ' . $e->getMessage());
                redirect_back();
            }
        } else if ($order['payment_method'] === 'COD') {
            // For COD, just record the refund intent
            $stmt = $conn->prepare("
                INSERT INTO REFUND (
                    payment_id,
                    order_id,
                    amount,
                    reason,
                    status,
                    created_at,
                    refunded_at
                ) VALUES (?, ?, ?, ?, 'completed', NOW(), NOW())
            ");
            
            $stmt->execute([
                $order['payment_id'] ?? null,
                $currentReturn['order_id'],
                $actualRefundAmount,
                'COD Return approved - Return ID: ' . $returnId
            ]);
        }
        
        // Restock inventory for returned items
        $stmt = $conn->prepare("
            SELECT ri.order_item_id, ri.quantity, oi.product_variant_id
            FROM RETURN_ITEMS ri
            JOIN ORDER_ITEMS oi ON ri.order_item_id = oi.id
            WHERE ri.return_id = ?
        ");
        $stmt->execute([$returnId]);
        $returnItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($returnItems as $item) {
            // Update product_variants stock
            $stmt = $conn->prepare("
                UPDATE product_variants 
                SET stock_quantity = stock_quantity + ? 
                WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_variant_id']]);
            
            // Log inventory movement
            $stmt = $conn->prepare("
                INSERT INTO INVENTORY (
                    product_variant_id,
                    quantity,
                    reason,
                    created_at
                ) VALUES (?, ?, 'Return_restock', NOW())
            ");
            $stmt->execute([
                $item['product_variant_id'],
                $item['quantity']
            ]);
        }
    }
    
    // Update return record
    $data = [
        'status' => $status,
        'admin_note' => $adminNote === '' ? null : $adminNote,
        'refund_amount' => $refundAmount === '' ? null : $refundAmount,
    ];
    
    $ok = update_return($conn, $returnId, $data);
    
    if ($ok) {
        $msg = "Return #{$returnId} updated successfully.";
        if ($status === 'accept_refund') {
            $msg .= " Refund processed and inventory restocked.";
        }
        flash('success', $msg);
    } else {
        flash('danger', "Failed to update return #{$returnId}.");
    }
    
    redirect_back();
}

if ($action === 'refund_approve') {
    $refundId = (int)($_POST['refund_id'] ?? 0);
    $refundAction = trim((string)($_POST['refund_action'] ?? ''));
    
    if ($refundId <= 0) {
        flash('danger', 'Invalid refund ID.');
        redirect_back();
    }
    
    if (!in_array($refundAction, ['approve', 'reject'], true)) {
        flash('danger', 'Invalid refund action.');
        redirect_back();
    }
    
    // Get refund details
    $stmt = $conn->prepare("
        SELECT r.*, o.payment_method, p.stripe_charge_id, p.id as payment_id
        FROM REFUND r
        JOIN ORDERS o ON r.order_id = o.id
        LEFT JOIN PAYMENT p ON o.id = p.order_id
        WHERE r.id = ?
    ");
    $stmt->execute([$refundId]);
    $refund = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$refund) {
        flash('danger', "Refund #{$refundId} not found.");
        redirect_back();
    }
    
    if ($refund['status'] !== 'pending') {
        flash('warning', "Refund #{$refundId} has already been processed (Status: {$refund['status']}).");
        redirect_back();
    }
    
    try {
        $conn->beginTransaction();
        
        if ($refundAction === 'reject') {
            // Simply update status to rejected
            $stmt = $conn->prepare("
                UPDATE REFUND 
                SET status = 'rejected' 
                WHERE id = ?
            ");
            $stmt->execute([$refundId]);
            
            // Log order history
            $stmt = $conn->prepare("
                INSERT INTO ORDER_HISTORY (order_id, previous_status, new_status, note, created_at)
                SELECT order_id, 'on_hold', 'on_hold', CONCAT('Refund request #', ?, ' rejected by admin'), NOW()
                FROM REFUND WHERE id = ?
            ");
            $stmt->execute([$refundId, $refundId]);
            
            $conn->commit();
            flash('success', "Refund request #{$refundId} has been rejected.");
            
        } else { // approve
            // Process actual refund via Stripe
            if ($refund['payment_method'] === 'STRIPE' && !empty($refund['stripe_charge_id'])) {
                try {
                    require_once __DIR__ . '/../../config/stripe.php';
                    
                    $refundAmountCents = (int)((float)$refund['amount'] * 100);
                    
                    $stripeRefund = \Stripe\Refund::create([
                        'charge' => $refund['stripe_charge_id'],
                        'amount' => $refundAmountCents,
                        'reason' => 'requested_by_customer',
                        'metadata' => [
                            'order_id' => (string)$refund['order_id'],
                            'refund_id' => (string)$refundId,
                            'approved_by' => $_SESSION['admin_username'] ?? 'admin'
                        ]
                    ]);
                    
                    // Update refund record with Stripe details
                    $refundStatus = $stripeRefund->status === 'succeeded' ? 'completed' : 'processing';
                    
                    $stmt = $conn->prepare("
                        UPDATE REFUND 
                        SET status = ?,
                            stripe_refund_id = ?,
                            refund_transaction_ref = ?,
                            refunded_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $refundStatus,
                        $stripeRefund->id,
                        $stripeRefund->id,
                        $refundId
                    ]);
                    
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $conn->rollBack();
                    error_log('[ADMIN REFUND APPROVE ERROR] ' . $e->getMessage());
                    flash('danger', 'Stripe refund failed: ' . $e->getMessage());
                    redirect_back();
                }
            } else if ($refund['payment_method'] === 'COD') {
                // For COD, just mark as completed
                $stmt = $conn->prepare("
                    UPDATE REFUND 
                    SET status = 'completed',
                        refunded_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$refundId]);
            } else {
                $conn->rollBack();
                flash('danger', 'Unsupported payment method for refund.');
                redirect_back();
            }
            
            // Update order status to cancelled
            $stmt = $conn->prepare("
                UPDATE ORDERS 
                SET status = 'cancelled' 
                WHERE id = ?
            ");
            $stmt->execute([$refund['order_id']]);
            
            // Log order history
            $stmt = $conn->prepare("
                INSERT INTO ORDER_HISTORY (order_id, previous_status, new_status, note, created_at)
                VALUES (?, 'on_hold', 'cancelled', ?, NOW())
            ");
            $stmt->execute([
                $refund['order_id'],
                "Refund approved and processed by admin. Refund ID: {$refundId}. Amount: \${$refund['amount']}"
            ]);
            
            $conn->commit();
            flash('success', "Refund request #{$refundId} approved and processed successfully. Amount: \${$refund['amount']}");
        }
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('[REFUND APPROVE ERROR] ' . $e->getMessage());
        flash('danger', 'Failed to process refund: ' . $e->getMessage());
    }
    
    redirect_back();
}

flash('danger', 'Unknown action.');
redirect_back();
