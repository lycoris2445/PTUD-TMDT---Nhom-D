<?php
session_start();
$pageTitle = "Order Success";
$pageCss = "payment.css";
include '../includes/header.php';
$pdo = require __DIR__ . '/../../config/db_connect.php';

// Get order details from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$payment_method = isset($_GET['method']) ? $_GET['method'] : 'COD';

// Fetch order from database
$order = null;
$orderItems = [];

if ($order_id > 0) {
    try {
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   COALESCE(p.amount, 0) as payment_amount,
                   COALESCE(p.currency, 'VND') as payment_currency,
                   COALESCE(p.status, 'pending') as payment_status
            FROM ORDERS o
            LEFT JOIN PAYMENT p ON o.id = p.order_id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Get order items - support both product_id and product_variant_id
            $stmt = $pdo->prepare("
                SELECT oi.*, 
                       p.name as product_name, 
                       p.image_url,
                       COALESCE(oi.product_id, oi.product_variant_id) as product_ref_id
                FROM ORDER_ITEMS oi
                LEFT JOIN PRODUCT p ON COALESCE(oi.product_id, oi.product_variant_id) = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching order: " . $e->getMessage());
    }
}

// Format currency
function formatCurrency($amount, $currency = 'VND') {
    if ($currency === 'USD') {
        return '$' . number_format($amount, 2);
    }
    return number_format($amount, 0, ',', '.') . '₫';
}

// Get payment method label
function getPaymentMethodLabel($method) {
    switch($method) {
        case 'STRIPE': return 'Credit Card (Stripe)';
        case 'COD': return 'Cash on Delivery';
        case 'BANK': return 'Bank Transfer';
        default: return $method;
    }
}

// Get order status label
function getOrderStatusLabel($status) {
    switch($status) {
        case 'pending': return 'Pending';
        case 'on_hold': return 'On Hold';
        case 'processing': return 'Processing';
        case 'shipping': return 'Shipping';
        case 'completed': return 'Completed';
        case 'cancelled': return 'Cancelled';
        default: return ucfirst($status);
    }
}
?>

<main class="container py-5">
    <?php if ($order): ?>
        <!-- Success Header -->
        <div class="text-center mb-5">
            <div class="mb-3">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            </div>
            <h1 class="h3 mb-2">Order Success!</h1>
            <p class="text-muted">Thank you for shopping at Darling Cosmetics</p>
        </div>

        <div class="row g-4">
            <!-- Order Information -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Order ID:</div>
                            <div class="col-sm-8"><strong>#<?php echo $order_id; ?></strong></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Order Date:</div>
                            <div class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Status:</div>
                            <div class="col-sm-8">
                                <span class="badge bg-info"><?php echo getOrderStatusLabel($order['status']); ?></span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Phương thức thanh toán:</div>
                            <div class="col-sm-8"><?php echo getPaymentMethodLabel($order['payment_method']); ?></div>
                        </div>
                        <?php if ($order['payment_method'] === 'STRIPE'): ?>
                        <div class="row">
                            <div class="col-sm-4 text-muted">Payment:</div>
                            <div class="col-sm-8">
                                <span class="badge bg-success">
                                    <?php echo $order['payment_status'] === 'paid' ? 'Paid' : 'Pending'; ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Thông tin giao hàng</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong><?php echo htmlspecialchars($order['fullname']); ?></strong></p>
                        <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <?php if ($order['email']): ?>
                        <p class="mb-1"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($order['email']); ?></p>
                        <?php endif; ?>
                        <p class="mb-0">
                            <i class="bi bi-geo-alt"></i> 
                            <?php 
                            echo htmlspecialchars($order['address']);
                            if ($order['district']) echo ', ' . htmlspecialchars($order['district']);
                            if ($order['city']) echo ', ' . htmlspecialchars($order['city']);
                            ?>
                        </p>
                        <?php if ($order['note']): ?>
                        <hr>
                        <p class="mb-0 small text-muted">
                            <strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['note']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Products Ordered</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if ($item['image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                     class="rounded"
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <div><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                    <small class="text-muted">ID: <?php echo $item['product_id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">
                                            <?php echo formatCurrency($item['price'], $order['payment_currency']); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php echo formatCurrency($item['price'] * $item['quantity'], $order['payment_currency']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <strong><?php echo formatCurrency($order['total_amount'], $order['payment_currency']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Fee:</span>
                            <strong>Free</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5 mb-4">
                            <span><strong>Total:</strong></span>
                            <strong class="text-darling">
                                <?php echo formatCurrency($order['total_amount'], $order['payment_currency']); ?>
                            </strong>
                        </div>

                        <div class="d-grid gap-2">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="order-detail.php?id=<?= $order_id ?>" class="btn btn-darling">
                                    <i class="bi bi-receipt"></i> View Order Details
                                </a>
                                <a href="orders.php" class="btn btn-outline-darling">
                                    <i class="bi bi-bag-check"></i> My Orders
                                </a>
                            <?php endif; ?>
                            <a href="home.php" class="btn btn-outline-secondary">
                                <i class="bi bi-house"></i> Back to Home
                            </a>
                            <a href="store.php" class="btn btn-outline-darling">
                                <i class="bi bi-bag"></i> Continue Shopping
                            </a>
                        </div>

                        <hr>
                        <div class="text-center small text-muted">
                            <p class="mb-2">
                                <i class="bi bi-info-circle"></i> 
                                We have sent an order confirmation email
                            </p>
                            <p class="mb-0">
                                For any inquiries, please contact:<br>
                                <a href="tel:0123456789">0123.456.789</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Order Not Found -->
        <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
            <h2 class="h4 mt-3 mb-2">Order Not Found</h2>
            <p class="text-muted mb-4">The order does not exist or has been deleted.</p>
            <a href="home.php" class="btn btn-darling">Back to Home</a>
        </div>
    <?php endif; ?>
</main>

<!-- Clear cart on success page load -->
<script>
    if (window.Cart && <?php echo $order ? 'true' : 'false'; ?>) {
        // Clear cart after successful order
        window.Cart.clear();
        console.log('[Order Success] Cart cleared');
    }
</script>

<?php include '../includes/footer.php'; ?>
