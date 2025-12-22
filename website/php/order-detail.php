<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit;
}

$pdo = require __DIR__ . '/../../config/db_connect.php';

$userId = $_SESSION['user_id'];
$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Get order details
$sql = "SELECT 
    o.*,
    p.status as payment_status,
    p.stripe_payment_intent_id,
    p.stripe_charge_id
FROM ORDERS o
LEFT JOIN PAYMENT p ON o.id = p.order_id
WHERE o.id = ? AND o.account_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items
$sql = "SELECT 
    oi.*,
    COALESCE(pv.sku_code, p.spu) as sku,
    COALESCE(pv.image_url, p.image_url) as image,
    p.name as product_name
FROM ORDER_ITEMS oi
LEFT JOIN product_variants pv ON oi.product_variant_id = pv.id
LEFT JOIN products p ON pv.product_id = p.id
WHERE oi.order_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse shipping address
$shippingAddress = json_decode($order['shipping_address_snapshot'], true);

$pageTitle = "Order #" . $order['id'];
$pageCss = "order-detail.css";

include '../includes/header.php';
?>

<main class="container py-5" data-page="order-detail">
    <div class="mb-4">
        <a href="orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>

    <div class="row g-4">
        <!-- Order Info -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Order #<?= $order['id'] ?></h4>
                    <small class="text-muted">Placed on <?= date('F d, Y \a\t g:i A', strtotime($order['created_at'])) ?></small>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Tracking Number:</strong><br>
                            <code class="fs-6"><?= htmlspecialchars($order['tracking_number']) ?></code>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong><br>
                            <?php
                            $statusClass = match($order['status']) {
                                'pending' => 'warning',
                                'on_hold' => 'info',
                                'processing' => 'primary',
                                'shipped' => 'info',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $statusClass ?> fs-6">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Payment:</strong><br>
                            <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?> fs-6">
                                <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                            </span>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Order Items</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if ($item['image']): ?>
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                    <?php if ($item['sku']): ?>
                                                        <br><small class="text-muted">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= $item['quantity'] ?></td>
                                        <td class="text-end">$<?= number_format($item['price_at_purchase'] ?? $item['price'], 2) ?></td>
                                        <td class="text-end">$<?= number_format(($item['price_at_purchase'] ?? $item['price']) * $item['quantity'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Shipping (<?= htmlspecialchars($order['shipping_carrier']) ?>):</strong></td>
                                    <td class="text-end">$<?= number_format($order['shipping_fee'], 2) ?></td>
                                </tr>
                                <tr class="table-active">
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong class="text-darling fs-5">$<?= number_format($order['final_amount'], 2) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Refund Section -->
            <?php if (
                $order['payment_status'] === 'paid' && 
                $order['payment_method'] === 'STRIPE' && 
                !empty($order['stripe_charge_id']) &&
                in_array($order['status'], ['pending', 'on_hold'])
            ): ?>
                <div class="card shadow-sm border-warning mt-4">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h5 class="mb-0"><i class="bi bi-arrow-counterclockwise"></i> Request Refund</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i> 
                            If you're not satisfied with your order, you can request a refund. 
                            Refunds are typically processed within 5-10 business days.
                        </div>
                        
                        <form id="refund-form">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="stripe_charge_id" value="<?= htmlspecialchars($order['stripe_charge_id']) ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Refund Amount (USD)</label>
                                <input type="number" name="amount" class="form-control" 
                                       step="0.01" min="0.01" max="<?= $order['final_amount'] ?>" 
                                       value="<?= $order['final_amount'] ?>" required>
                                <small class="text-muted">Maximum refund: $<?= number_format($order['final_amount'], 2) ?></small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Reason for Refund</label>
                                <textarea name="reason" class="form-control" rows="3" 
                                          placeholder="Please explain why you want a refund..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-arrow-counterclockwise"></i> Submit Refund Request
                            </button>
                            <div id="refund-message" class="mt-2"></div>
                        </form>
                    </div>
                </div>
            <?php elseif (
                $order['payment_method'] === 'STRIPE' && 
                $order['payment_status'] === 'paid' &&
                in_array($order['status'], ['processing', 'shipped', 'delivered', 'completed'])
            ): ?>
                <!-- Refund Not Available -->
                <div class="alert alert-warning mt-4">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Refund not available</strong><br>
                    This order is currently <strong><?= ucfirst($order['status']) ?></strong> and cannot be refunded. 
                    Refunds are only available for orders that have not been processed yet.
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Shipping Address -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Shipping Address</h5>
                </div>
                <div class="card-body">
                    <strong><?= htmlspecialchars($shippingAddress['fullname'] ?? 'N/A') ?></strong><br>
                    <?= htmlspecialchars($shippingAddress['phone'] ?? 'N/A') ?><br>
                    <?php if (!empty($shippingAddress['email'])): ?>
                        <?= htmlspecialchars($shippingAddress['email']) ?><br>
                    <?php endif; ?>
                    <hr class="my-2">
                    <?= htmlspecialchars($shippingAddress['address'] ?? 'N/A') ?><br>
                    <?php if (!empty($shippingAddress['district'])): ?>
                        <?= htmlspecialchars($shippingAddress['district']) ?>,
                    <?php endif; ?>
                    <?= htmlspecialchars($shippingAddress['city'] ?? 'N/A') ?>
                    
                    <?php if (!empty($shippingAddress['note'])): ?>
                        <hr class="my-2">
                        <small class="text-muted">
                            <strong>Note:</strong><br>
                            <?= htmlspecialchars($shippingAddress['note']) ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Method:</strong> <?= htmlspecialchars($order['payment_method']) ?>
                    </div>
                    <div class="mb-2">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                            <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                        </span>
                    </div>
                    <?php if ($order['stripe_payment_intent_id']): ?>
                        <div class="mb-2">
                            <small class="text-muted">
                                Payment Intent ID:<br>
                                <code style="font-size: 10px;"><?= htmlspecialchars($order['stripe_payment_intent_id']) ?></code>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('refund-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    const messageDiv = document.getElementById('refund-message');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    
    try {
        const response = await fetch('../../api/payments/create-refund.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            messageDiv.innerHTML = '<div class="alert alert-success">Refund request submitted successfully! Refund ID: ' + result.refund_id + '</div>';
            this.reset();
            setTimeout(() => location.reload(), 2000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger">Error: ' + result.message + '</div>';
        }
    } catch (error) {
        messageDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Submit Refund Request';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
