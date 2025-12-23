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

// Check if return request exists
$stmt = $pdo->prepare("SELECT * FROM RETURNS WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$orderId]);
$returnRequest = $stmt->fetch(PDO::FETCH_ASSOC);

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
                            Not satisfied with your order? Submit a refund request and our team will review it.
                            <strong>Please note:</strong> Refund requests require admin approval before processing.
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

            <!-- Return Request Section -->
            <?php if (in_array($order['status'], ['delivered', 'completed'])): ?>
                <?php if ($returnRequest): ?>
                    <!-- Existing Return Request -->
                    <div class="card shadow-sm border-info mt-4">
                        <div class="card-header bg-info bg-opacity-10">
                            <h5 class="mb-0"><i class="bi bi-box-seam"></i> Return Request Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Return ID:</strong> #<?= $returnRequest['id'] ?></p>
                                    <p><strong>Status:</strong> 
                                        <?php
                                        $returnStatusBadge = match($returnRequest['status']) {
                                            'request_return' => 'warning',
                                            'accept_return' => 'info',
                                            'decline_return' => 'danger',
                                            'receive_return_package' => 'primary',
                                            'accept_refund' => 'success',
                                            'decline_refund' => 'danger',
                                            default => 'secondary'
                                        };
                                        $returnStatusText = match($returnRequest['status']) {
                                            'request_return' => 'Pending Review',
                                            'accept_return' => 'Accepted - Please Ship',
                                            'decline_return' => 'Declined',
                                            'receive_return_package' => 'Package Received',
                                            'accept_refund' => 'Refunded',
                                            'decline_refund' => 'Refund Declined',
                                            default => ucfirst($returnRequest['status'])
                                        };
                                        ?>
                                        <span class="badge bg-<?= $returnStatusBadge ?>"><?= $returnStatusText ?></span>
                                    </p>
                                    <p><strong>Refund Amount:</strong> $<?= number_format($returnRequest['refund_amount'], 2) ?></p>
                                    <p><strong>Submitted:</strong> <?= date('M d, Y', strtotime($returnRequest['created_at'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Reason:</strong></p>
                                    <p class="text-muted"><?= htmlspecialchars($returnRequest['reason']) ?></p>
                                    <?php if ($returnRequest['admin_note']): ?>
                                        <p><strong>Admin Note:</strong></p>
                                        <p class="text-muted"><?= htmlspecialchars($returnRequest['admin_note']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- New Return Request Form -->
                    <div class="card shadow-sm border-info mt-4">
                        <div class="card-header bg-info bg-opacity-10">
                            <h5 class="mb-0"><i class="bi bi-box-seam"></i> Request Return</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle"></i> 
                                Not satisfied with your order? You can request a return within 30 days of delivery.
                                Please select the items you'd like to return and provide photos showing the issue.
                            </div>
                            
                            <form id="return-form" enctype="multipart/form-data">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                
                                <!-- Select Items to Return -->
                                <div class="mb-4">
                                    <label class="form-label"><strong>Select Items to Return:</strong></label>
                                    <?php foreach ($items as $item): ?>
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-1">
                                                        <input type="checkbox" class="form-check-input return-item-checkbox" 
                                                               name="return_items[]" 
                                                               value="<?= $item['id'] ?>"
                                                               data-max-qty="<?= $item['quantity'] ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <?php if ($item['image']): ?>
                                                            <img src="<?= htmlspecialchars($item['image']) ?>" 
                                                                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                        <?php if ($item['sku']): ?>
                                                            <br><small class="text-muted">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small mb-0">Quantity:</label>
                                                        <input type="number" 
                                                               class="form-control form-control-sm return-qty" 
                                                               name="return_qty[<?= $item['id'] ?>]" 
                                                               min="1" 
                                                               max="<?= $item['quantity'] ?>" 
                                                               value="<?= $item['quantity'] ?>" 
                                                               disabled>
                                                    </div>
                                                    <div class="col-md-2 text-end">
                                                        <small class="text-muted">Price: $<?= number_format($item['price_at_purchase'], 2) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Reason for Return <span class="text-danger">*</span></label>
                                    <select name="reason" class="form-select mb-2" id="return-reason-select" required>
                                        <option value="">Select a reason...</option>
                                        <option value="Defective or damaged product">Defective or damaged product</option>
                                        <option value="Wrong item received">Wrong item received</option>
                                        <option value="Product not as described">Product not as described</option>
                                        <option value="Changed my mind">Changed my mind</option>
                                        <option value="Quality not as expected">Quality not as expected</option>
                                        <option value="Other">Other (please specify below)</option>
                                    </select>
                                    <textarea name="reason_detail" class="form-control" rows="3" 
                                              placeholder="Please provide additional details about your return request..." required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Upload Proof Images <span class="text-danger">*</span></label>
                                    <input type="file" name="proof_images[]" class="form-control" 
                                           accept="image/*" multiple required>
                                    <small class="text-muted">
                                        Please upload clear photos showing the issue (max 5 images, 5MB each)
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-info" id="return-submit-btn">
                                    <i class="bi bi-box-seam"></i> Submit Return Request
                                </button>
                                <div id="return-message" class="mt-2"></div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
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
// Return items checkbox handler
document.querySelectorAll('.return-item-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const qtyInput = this.closest('.card-body').querySelector('.return-qty');
        qtyInput.disabled = !this.checked;
        if (!this.checked) {
            qtyInput.value = qtyInput.getAttribute('max');
        }
    });
});

// Return form submission
document.getElementById('return-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validate at least one item is selected
    const selectedItems = document.querySelectorAll('.return-item-checkbox:checked');
    if (selectedItems.length === 0) {
        alert('Please select at least one item to return');
        return;
    }
    
    // Validate proof images
    const fileInput = this.querySelector('input[name="proof_images[]"]');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Please upload at least one proof image');
        return;
    }
    
    // Validate file sizes
    let totalSize = 0;
    for (let i = 0; i < fileInput.files.length; i++) {
        totalSize += fileInput.files[i].size;
        if (fileInput.files[i].size > 5 * 1024 * 1024) {
            alert('Each image must be less than 5MB');
            return;
        }
    }
    
    if (fileInput.files.length > 5) {
        alert('Maximum 5 images allowed');
        return;
    }
    
    const formData = new FormData();
    formData.append('order_id', this.querySelector('input[name="order_id"]').value);
    
    // Build return items array
    const returnItems = [];
    selectedItems.forEach(checkbox => {
        const itemId = checkbox.value;
        const qtyInput = checkbox.closest('.card-body').querySelector('.return-qty');
        returnItems.push({
            order_item_id: itemId,
            quantity: qtyInput.value
        });
    });
    
    // Combine reason
    const reasonSelect = this.querySelector('#return-reason-select').value;
    const reasonDetail = this.querySelector('textarea[name="reason_detail"]').value;
    const fullReason = reasonSelect + (reasonDetail ? ': ' + reasonDetail : '');
    
    formData.append('reason', fullReason);
    formData.append('return_items', JSON.stringify(returnItems));
    
    // Add proof images
    for (let i = 0; i < fileInput.files.length; i++) {
        formData.append('proof_images[]', fileInput.files[i]);
    }
    
    const messageDiv = document.getElementById('return-message');
    const submitBtn = document.getElementById('return-submit-btn');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
    
    try {
        const response = await fetch('../../api/return/create-return-request.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            messageDiv.innerHTML = '<div class="alert alert-success">Return request submitted successfully! Return ID: #' + result.return_id + '</div>';
            this.reset();
            setTimeout(() => location.reload(), 2000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger">Error: ' + result.error + '</div>';
        }
    } catch (error) {
        messageDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-box-seam"></i> Submit Return Request';
    }
});

// Refund form submission
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
            messageDiv.innerHTML = '<div class="alert alert-success"><strong>Success!</strong> Your refund request has been submitted and is pending admin review. Refund ID: #' + result.refund_id + '<br><small class="text-muted">You will be notified once it is reviewed.</small></div>';
            this.reset();
            setTimeout(() => location.reload(), 3000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger"><strong>Error:</strong> ' + (result.error || result.message) + '</div>';
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
