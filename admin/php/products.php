<?php
/**
 * Admin - Quản lý sản phẩm & Upload ảnh Cloudinary
 */

require_once __DIR__ . '/../../config/cloudinary.php';

$conn = getDBConnection();

// ============================================
// XỬ LÝ FORM ACTIONS
// ============================================
$message = '';
$messageType = '';

// --- Thêm sản phẩm mới ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Thêm Product mới
    if ($_POST['action'] === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $basePrice = floatval($_POST['base_price'] ?? 0);
        $categoryId = intval($_POST['category_id'] ?? 0) ?: null;
        $status = $_POST['status'] ?? 'DRAFT';
        
        if ($name && $basePrice > 0) {
            $stmt = $conn->prepare("INSERT INTO products (name, description, base_price, category_id, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdis", $name, $description, $basePrice, $categoryId, $status);
            
            if ($stmt->execute()) {
                $message = "Thêm sản phẩm thành công! ID: " . $conn->insert_id;
                $messageType = 'success';
            } else {
                $message = "Lỗi: " . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Vui lòng nhập đủ tên và giá sản phẩm.";
            $messageType = 'warning';
        }
    }
    
    // Thêm Variant và Upload ảnh
    if ($_POST['action'] === 'add_variant') {
        $productId = intval($_POST['product_id'] ?? 0);
        $skuCode = trim($_POST['sku_code'] ?? '');
        $price = floatval($_POST['variant_price'] ?? 0);
        $attributes = trim($_POST['attributes'] ?? '{}');
        
        $imageUrl = '';
        
        // Upload ảnh nếu có
        if (!empty($_FILES['variant_image']['tmp_name']) && $_FILES['variant_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $fileType = mime_content_type($_FILES['variant_image']['tmp_name']);
            
            if (in_array($fileType, $allowedTypes)) {
                $uploadResult = uploadToCloudinary($_FILES['variant_image']['tmp_name'], 'darling/products');
                
                if ($uploadResult['success']) {
                    $imageUrl = $uploadResult['url'];
                } else {
                    $message = "Upload ảnh thất bại: " . $uploadResult['error'];
                    $messageType = 'danger';
                }
            } else {
                $message = "Định dạng ảnh không hợp lệ. Chấp nhận: JPG, PNG, WebP, GIF.";
                $messageType = 'warning';
            }
        }
        
        // Nếu không có lỗi upload, insert variant
        if ($messageType !== 'danger' && $messageType !== 'warning') {
            if ($productId && $skuCode && $price > 0) {
                $stmt = $conn->prepare("INSERT INTO product_variants (product_id, sku_code, price, image_url, attributes) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isdss", $productId, $skuCode, $price, $imageUrl, $attributes);
                
                if ($stmt->execute()) {
                    $variantId = $conn->insert_id;
                    
                    // Tạo record inventory cho variant
                    $stmtInv = $conn->prepare("INSERT INTO inventory (product_variant_id, quantity) VALUES (?, 0)");
                    $stmtInv->bind_param("i", $variantId);
                    $stmtInv->execute();
                    $stmtInv->close();
                    
                    $message = "Thêm biến thể thành công!";
                    $messageType = 'success';
                } else {
                    $message = "Lỗi: " . $stmt->error;
                    $messageType = 'danger';
                }
                $stmt->close();
            } else {
                $message = "Vui lòng nhập đủ thông tin biến thể.";
                $messageType = 'warning';
            }
        }
    }
    
    // Cập nhật ảnh Variant
    if ($_POST['action'] === 'update_variant_image') {
        $variantId = intval($_POST['variant_id'] ?? 0);
        
        if ($variantId && !empty($_FILES['variant_image']['tmp_name']) && $_FILES['variant_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $fileType = mime_content_type($_FILES['variant_image']['tmp_name']);
            
            if (in_array($fileType, $allowedTypes)) {
                $uploadResult = uploadToCloudinary($_FILES['variant_image']['tmp_name'], 'darling/products');
                
                if ($uploadResult['success']) {
                    $stmt = $conn->prepare("UPDATE product_variants SET image_url = ? WHERE id = ?");
                    $stmt->bind_param("si", $uploadResult['url'], $variantId);
                    
                    if ($stmt->execute()) {
                        $message = "Cập nhật ảnh thành công!";
                        $messageType = 'success';
                    } else {
                        $message = "Lỗi database: " . $stmt->error;
                        $messageType = 'danger';
                    }
                    $stmt->close();
                } else {
                    $message = "Upload ảnh thất bại: " . $uploadResult['error'];
                    $messageType = 'danger';
                }
            } else {
                $message = "Định dạng ảnh không hợp lệ.";
                $messageType = 'warning';
            }
        }
    }
    
    // Xóa variant
    if ($_POST['action'] === 'delete_variant') {
        $variantId = intval($_POST['variant_id'] ?? 0);
        if ($variantId) {
            $stmt = $conn->prepare("DELETE FROM product_variants WHERE id = ?");
            $stmt->bind_param("i", $variantId);
            if ($stmt->execute()) {
                $message = "Đã xóa biến thể.";
                $messageType = 'success';
            }
            $stmt->close();
        }
    }
}

// ============================================
// LẤY DỮ LIỆU HIỂN THỊ
// ============================================

// Danh sách categories
$categories = [];
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY name");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Danh sách products với variants
$products = [];
$sql = "
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        p.description,
        p.base_price,
        p.status,
        c.name AS category_name,
        pv.id AS variant_id,
        pv.sku_code,
        pv.price AS variant_price,
        pv.image_url,
        pv.attributes,
        COALESCE(inv.quantity, 0) AS stock
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    LEFT JOIN inventory inv ON inv.product_variant_id = pv.id
    ORDER BY p.id DESC, pv.id ASC
";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pid = $row['product_id'];
        if (!isset($products[$pid])) {
            $products[$pid] = [
                'id' => $pid,
                'name' => $row['product_name'],
                'description' => $row['description'],
                'base_price' => $row['base_price'],
                'status' => $row['status'],
                'category' => $row['category_name'],
                'variants' => []
            ];
        }
        if ($row['variant_id']) {
            $products[$pid]['variants'][] = [
                'id' => $row['variant_id'],
                'sku' => $row['sku_code'],
                'price' => $row['variant_price'],
                'image_url' => $row['image_url'],
                'attributes' => $row['attributes'],
                'stock' => $row['stock']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .variant-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f1f1;
        }
        .variant-image-placeholder {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f1f1;
            border-radius: 8px;
            color: #999;
            font-size: 12px;
        }
        .upload-btn {
            position: relative;
            overflow: hidden;
        }
        .upload-btn input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .product-card {
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .product-header {
            padding: 16px;
            border-bottom: 1px solid #e5e5e5;
            background: #fafafa;
            border-radius: 12px 12px 0 0;
        }
        .variant-row {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
        }
        .variant-row:last-child {
            border-bottom: none;
        }
        .status-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .status-ACTIVE { background: #d1fae5; color: #065f46; }
        .status-DRAFT { background: #fef3c7; color: #92400e; }
        .status-INACTIVE { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">Darling Admin</div>
        <ul class="sidebar-menu">
            <li onclick="location.href='dashboard.php'">Dashboard</li>
            <li onclick="location.href='orders.php'">Orders</li>
            <li onclick="location.href='khach_hang.php'">Customers</li>
            <li class="active">Products</li>
            <li>Reports</li>
            <li>Settings</li>
        </ul>
    </aside>

    <main class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title mb-0">Quản lý Sản phẩm</h1>
            <button class="btn btn-darling" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg"></i> Thêm sản phẩm
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- DANH SÁCH SẢN PHẨM -->
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                Chưa có sản phẩm nào. <a href="#" data-bs-toggle="modal" data-bs-target="#addProductModal">Thêm sản phẩm đầu tiên</a>
            </div>
        <?php endif; ?>

        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-header d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="mb-1">
                            <?= htmlspecialchars($product['name']) ?>
                            <span class="status-badge status-<?= $product['status'] ?>"><?= $product['status'] ?></span>
                        </h5>
                        <div class="text-muted small">
                            ID: <?= $product['id'] ?> |
                            Danh mục: <?= $product['category'] ?: 'Chưa phân loại' ?> |
                            Giá gốc: <?= number_format($product['base_price'], 0, ',', '.') ?>₫
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#addVariantModal"
                            data-product-id="<?= $product['id'] ?>"
                            data-product-name="<?= htmlspecialchars($product['name']) ?>">
                        <i class="bi bi-plus"></i> Thêm biến thể
                    </button>
                </div>

                <?php if (empty($product['variants'])): ?>
                    <div class="p-3 text-muted small">Chưa có biến thể nào</div>
                <?php else: ?>
                    <?php foreach ($product['variants'] as $variant): ?>
                        <div class="variant-row d-flex align-items-center gap-3">
                            <!-- Ảnh -->
                            <div>
                                <?php if ($variant['image_url']): ?>
                                    <img src="<?= htmlspecialchars($variant['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($variant['sku']) ?>"
                                         class="variant-image">
                                <?php else: ?>
                                    <div class="variant-image-placeholder">No image</div>
                                <?php endif; ?>
                            </div>

                            <!-- Thông tin -->
                            <div class="flex-grow-1">
                                <div class="fw-semibold">SKU: <?= htmlspecialchars($variant['sku']) ?></div>
                                <div class="small text-muted">
                                    Giá: <?= number_format($variant['price'], 0, ',', '.') ?>₫ |
                                    Tồn kho: <?= $variant['stock'] ?>
                                </div>
                                <?php if ($variant['attributes'] && $variant['attributes'] !== '{}'): ?>
                                    <div class="small text-muted">
                                        Thuộc tính: <?= htmlspecialchars($variant['attributes']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2">
                                <form method="post" enctype="multipart/form-data" class="d-inline">
                                    <input type="hidden" name="action" value="update_variant_image">
                                    <input type="hidden" name="variant_id" value="<?= $variant['id'] ?>">
                                    <label class="btn btn-sm btn-outline-secondary upload-btn mb-0">
                                        <i class="bi bi-camera"></i>
                                        <input type="file" name="variant_image" accept="image/*" 
                                               onchange="this.form.submit()">
                                    </label>
                                </form>
                                <form method="post" class="d-inline" 
                                      onsubmit="return confirm('Xóa biến thể này?')">
                                    <input type="hidden" name="action" value="delete_variant">
                                    <input type="hidden" name="variant_id" value="<?= $variant['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>
</div>

<!-- MODAL: Thêm sản phẩm -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add_product">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm sản phẩm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên sản phẩm *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giá gốc (₫) *</label>
                            <input type="number" name="base_price" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Danh mục</label>
                            <select name="category_id" class="form-select">
                                <option value="">-- Chọn --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="DRAFT">Draft</option>
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-darling">Thêm sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Thêm biến thể -->
<div class="modal fade" id="addVariantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_variant">
                <input type="hidden" name="product_id" id="variantProductId">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm biến thể - <span id="variantProductName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Mã SKU *</label>
                        <input type="text" name="sku_code" class="form-control" required 
                               placeholder="VD: CLEANSER-001-RED">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Giá (₫) *</label>
                        <input type="number" name="variant_price" class="form-control" min="0" step="1000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Thuộc tính (JSON)</label>
                        <input type="text" name="attributes" class="form-control" 
                               placeholder='{"color": "Red", "size": "50ml"}'>
                        <div class="form-text">Để trống nếu không có thuộc tính đặc biệt</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ảnh sản phẩm</label>
                        <input type="file" name="variant_image" class="form-control" accept="image/*">
                        <div class="form-text">Hỗ trợ: JPG, PNG, WebP, GIF. Tối đa 10MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-darling">
                        <i class="bi bi-cloud-upload"></i> Thêm biến thể
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Pass product data to Add Variant modal
document.getElementById('addVariantModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const productId = button.getAttribute('data-product-id');
    const productName = button.getAttribute('data-product-name');
    
    document.getElementById('variantProductId').value = productId;
    document.getElementById('variantProductName').textContent = productName;
});
</script>
</body>
</html>
