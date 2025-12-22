<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit;
}

$pdo = require __DIR__ . '/../../config/db_connect.php';

$userId = $_SESSION['user_id'];
$pageTitle = "My Orders";
$pageCss = "orders.css";

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT 
    o.id,
    o.tracking_number,
    o.total_amount,
    o.payment_method,
    o.shipping_fee,
    o.final_amount,
    o.status,
    o.created_at,
    p.status as payment_status
FROM ORDERS o
LEFT JOIN PAYMENT p ON o.id = p.order_id
WHERE o.account_id = ?";

$params = [$userId];

if ($status !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $sql .= " AND (o.tracking_number LIKE ? OR o.id LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<main class="container py-5" data-page="orders">
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Order placed successfully!</strong> 
            <?php if (isset($_GET['order_id'])): ?>
                Order #<?= htmlspecialchars($_GET['order_id']) ?> has been created.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-bag-check text-darling"></i> My Orders
            </h1>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="orders.php" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Orders</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="on_hold" <?= $status === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                                <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Order ID or Tracking Number" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-darling w-100">
                                <i class="bi bi-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders List -->
            <?php if (empty($orders)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No orders found. <a href="store.php">Start shopping</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <div class="order-id">
                                            <small class="text-muted d-block">Order #</small>
                                            <strong><?= $order['id'] ?></strong>
                                        </div>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($order['created_at'])) ?></small>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="tracking">
                                            <small class="text-muted d-block">Tracking</small>
                                            <code><?= htmlspecialchars($order['tracking_number']) ?></code>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2 text-center">
                                        <small class="text-muted d-block">Status</small>
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
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="col-md-2 text-center">
                                        <small class="text-muted d-block">Payment</small>
                                        <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?= $order['payment_method'] ?></small>
                                    </div>
                                    
                                    <div class="col-md-2 text-end">
                                        <div class="order-total mb-2">
                                            <small class="text-muted d-block">Total</small>
                                            <strong class="text-darling">$<?= number_format($order['final_amount'], 2) ?></strong>
                                        </div>
                                        <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-outline-darling btn-sm">
                                            <i class="bi bi-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
