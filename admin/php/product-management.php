<?php
declare(strict_types=1);
session_start();

// Cho phép cả super_admin và operation_staff truy cập Product Management
$allowed_roles = ['super_admin', 'operation_staff']; 

if (
    !isset($_SESSION['is_admin']) || 
    $_SESSION['is_admin'] !== true || 
    !in_array($_SESSION['admin_role'], $allowed_roles)
) {
    // Nếu không đúng quyền, chuyển hướng về trang login hoặc báo lỗi 403
    header("Location: admin-login.php");
    exit("Truy cập bị từ chối!");
}

require_once __DIR__ . '/../includes/function_product_management.php';

try {
    /** @var PDO $conn */
    $conn = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection error: " . $e->getMessage());
}

// Inputs
$selected_category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$selected_category = $selected_category ?: null;

$search = trim((string)($_GET['search'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

// Data
$categoryTree = getCategoryTree($conn);
$allCategories = getAllCategories($conn);

// Default category (first root) if none selected
if ($selected_category === null && !empty($categoryTree)) {
    $selected_category = (int)($categoryTree[0]['id'] ?? 0) ?: null;
}

$products = getProductsByCategory($conn, $selected_category, $search, $status,);

// Selected category name for breadcrumb/title
$selectedCategoryName = '';
if ($selected_category !== null) {
    foreach ($allCategories as $c) {
        if ((int)$c['id'] === (int)$selected_category) {
            $selectedCategoryName = (string)$c['name'];
            break;
        }
    }
}

// Helper: price format VND-like (you can customize)
function money_vnd($val): string {
    $n = (float)$val;
    if ($n == (int)$n) {
        return '$' . (string)(int)$n;
    }
    return '$' . number_format($n, 2, '.', ',');
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Product Management</title>

    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/product-management.css">
    <link rel="stylesheet" href="../css/admin-main.css">
</head>
<body>
<div class="admin-wrapper">

    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <!-- Main -->
    <main class="admin-main">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="page-title">Product Management</h2>
        </div>
        <div class="row g-3">
            <!-- Category Sidebar -->
            <div class="col-12 col-lg-4 col-xl-3">
                <div class="panel category-panel">
                    <input id="category-search" class="form-control mb-2" placeholder="Search categories..." />

                    <button class="btn btn-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg"></i> Add Category
                    </button>

                    <div class="category-scroll">
                        <?php
                        if (empty($categoryTree)) {
                            echo '<div class="muted">No categories yet.</div>';
                        } else {
                            echo renderCategoryTree($categoryTree, $selected_category);
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="col-12 col-lg-8 col-xl-9">
                <div class="panel products-panel">

                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div>
                            <div class="h4 mb-0">Products <span class="muted">(<?= count($products) ?> items)</span></div>
                            <?php if ($selectedCategoryName !== ''): ?>
                                <div class="muted">Category: <strong><?= h($selectedCategoryName) ?></strong></div>
                            <?php endif; ?>
                        </div>

                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal"
                                onclick="document.getElementById('addProductForm').dataset.mode='create'; document.getElementById('productModalTitle').innerText='Add Product'; document.getElementById('addProductForm').reset(); document.querySelector('#addProductForm [name=id]').value='';">
                            <i class="bi bi-plus-lg"></i> Add Product
                        </button>
                    </div>

                    <div class="mt-3">
                        <input id="product-search" class="form-control" placeholder="Search by name or SKU..." value="<?= h($search) ?>">
                    </div>

                    <div class="mt-2 d-flex gap-2 align-items-center flex-wrap">
                        <div style="min-width:240px;">
                            <select id="status-filter" class="form-select">
                                <option value="" <?= $status===''?'selected':''; ?>>All Status</option>
                                <option value="active" <?= $status==='active'?'selected':''; ?>>Active</option>
                                <option value="inactive" <?= $status==='inactive'?'selected':''; ?>>Inactive</option>
                                <option value="draft" <?= $status==='draft'?'selected':''; ?>>Draft</option>
                            </select>
                        </div>

                        <button class="btn btn-outline-secondary" id="btn-reset" type="button">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>

                    <hr class="my-3" />

                    <?php if ($selected_category === null): ?>
                        <div class="muted">Please select a category.</div>
                    <?php elseif (empty($products)): ?>
                        <div class="muted">No products found for this category.</div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($products as $p): ?>
                                <?php
                                $available = (int)($p['available'] ?? 0);
                                $reserved  = (int)($p['reserved_quantity'] ?? 0);

                                $pillClass = 'pill-in';
                                $pillText = 'In Stock: ' . $available;
                                if ($available <= 0) { $pillClass = 'pill-out'; }
                                elseif ($available <= 5) { $pillClass = 'pill-low'; }

                                $img = trim((string)($p['image_url'] ?? ''));
                                ?>
                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="product-card">
                                        <div class="product-img">
                                            <?php if ($img !== ''): ?>
                                                <img src="<?= h($img) ?>" alt="<?= h((string)$p['name']) ?>">
                                            <?php else: ?>
                                                <div class="muted text-center px-3">
                                                    <i class="bi bi-image" style="font-size:40px;"></i><br/>
                                                    No image
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="p-3">
                                            <div class="d-flex align-items-start justify-content-between gap-2">
                                                <div class="fw-bold"><?= h((string)$p['name']) ?></div>
                                                <div><?= getStatusBadge((string)$p['status']) ?></div>
                                            </div>

                                            <div class="mt-2 small">
                                                <div class="d-flex justify-content-between">
                                                    <span class="muted">SKU:</span>
                                                    <span class="fw-semibold"><?= h((string)$p['spu']) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="muted">Category:</span>
                                                    <span class="fw-semibold"><?= h((string)($p['category_name'] ?? '')) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="muted">Variants:</span>
                                                    <span class="fw-semibold"><?= (int)($p['variant_count'] ?? 0) ?></span>
                                                </div>
                                            </div>

                                            <div class="mt-3 fw-bold" style="font-size:18px;">
                                                <?= money_vnd((string)$p['base_price']) ?>
                                            </div>

                                            <div class="mt-2 pill-stock <?= $pillClass ?>">
                                                <?= h($pillText) ?>
                                                <?php if ($reserved > 0): ?>
                                                    <span class="muted fw-semibold"> • Reserved: <?= $reserved ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="mt-3 d-flex gap-2">
                                                <button class="btn btn-outline-secondary btn-icon" type="button"
                                                        onclick="viewProduct(<?= (int)$p['id'] ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                <button class="btn btn-outline-primary btn-icon" type="button"
                                                        onclick="editProduct(<?= (int)$p['id'] ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <button class="btn btn-danger btn-icon" type="button"
                                                        onclick="deleteProduct(<?= (int)$p['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>
</div>


<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="addCategoryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input class="form-control" name="name" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Parent Category (optional)</label>
                        <select class="form-select" name="parent_id">
                            <option value="">— None —</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"><?= h((string)$cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="small muted">
                        Sau khi thêm category, trang sẽ reload để cập nhật cây danh mục.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Add/Edit Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="addProductForm" enctype="multipart/form-data" data-mode="create">
                <div class="modal-body">
                    <input type="hidden" name="id" value="">

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Product Name</label>
                            <input class="form-control" name="name" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">SPU</label>
                            <input class="form-control" name="spu" required placeholder="SPUXXXX">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select category...</option>
                                <?php foreach ($allCategories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>"><?= h((string)$cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Base Price</label>
                            <input class="form-control" name="base_price" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Stock Quantity</label>
                            <input class="form-control" name="stock_quantity" type="number" step="1" min="0" value="0" required>
                            <small class="text-muted">Tồn kho sẽ lưu vào INVENTORY của biến thể mặc định.</small>
                        </div>
                    
                        <div class="col-12 col-md-6">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">Upload file (Cloudinary). Nếu không upload thì dán URL bên dưới.</small>
                            <input type="url" class="form-control mt-2" name="image_url" placeholder="https://...">
                        </div>
                    </div>

                </div>

                <hr>
                <h4>Product Variants (SKU)</h4>

                <table class="table table-bordered" id="variantTable">
                    <thead>
                        <tr>
                            <th>SKU Code</th>
                            <th>Price ($)</th>
                            <th>Image URL</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if (!empty($variants)): ?>
                        <?php foreach ($variants as $v): ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="variant_id[]" value="<?= (int)$v['id'] ?>">
                                    <input type="text" name="variant_sku[]" class="form-control"
                                        value="<?= htmlspecialchars($v['sku_code']) ?>" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="variant_price[]" class="form-control"
                                        value="<?= htmlspecialchars($v['price']) ?>" required>
                                </td>
                                <td>
                                    <input type="text" name="variant_image[]" class="form-control"
                                        value="<?= htmlspecialchars($v['image_url']) ?>">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    </tbody>
                </table>

                <button type="button" class="btn btn-primary btn-sm" onclick="addVariant()">+ Add Variant</button>


                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Bootstrap + JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Your page JS -->
<script src="../js/product-management.js"></script>
</body>
</html>
