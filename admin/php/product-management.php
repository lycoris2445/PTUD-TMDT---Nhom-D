<?php
require '../../config/db_connect.php';
require '../includes/function_product_management.php';

try {
    $conn = require '../../config/db_connect.php';
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Get category tree
$categoryTree = getCategoryTree($conn);

// Get selected category from GET param or first category
$selected_category = $_GET['category'] ?? null;
if ($selected_category === null && !empty($categoryTree)) {
    $selected_category = $categoryTree[0]['id'] ?? null;
}

// Get search term
$search = $_GET['search'] ?? '';

// Get products by selected category
$products = getProductsByCategory($conn, $selected_category, $search);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-main.css">
    <link rel="stylesheet" href="../css/product-management.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="content">
        <h2 class="page-title">Product Management</h2>

        <div class="product-layout">
            <!-- LEFT: CATEGORY TREE -->
            <div class="category-panel">
                <div class="category-search">
                    <input type="text" id="category-search" placeholder="Search categories..." class="form-control">
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus"></i> Add Category
                    </button>
                </div>

                <div class="category-tree">
                    <?php echo renderCategoryTree($categoryTree, $selected_category); ?>
                </div>
            </div>

            <!-- RIGHT: PRODUCTS GRID -->
            <div class="product-panel">
                <div class="product-header">
                    <h3>
                        Products
                        <?php if ($selected_category): ?>
                            <small class="text-muted">(<?php echo count($products); ?> items)</small>
                        <?php endif; ?>
                    </h3>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus"></i> Add Product
                    </button>
                </div>

                <div class="product-toolbar">
                    <input type="text" id="product-search" placeholder="Search by name or SKU..." class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                    <select id="status-filter" class="form-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="draft">Draft</option>
                    </select>
                    <button class="btn btn-outline-secondary" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>

                <div class="product-grid" id="product-grid">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                                <div class="product-image">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #e5e7eb; color: #9ca3af;">
                                            <i class="bi bi-image" style="font-size: 32px;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>

                                    <div class="product-meta">
                                        <div class="product-meta-item">
                                            <span>SKU:</span>
                                            <strong><?php echo htmlspecialchars($product['spu']); ?></strong>
                                        </div>
                                        <div class="product-meta-item">
                                            <span>Category:</span>
                                            <strong><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></strong>
                                        </div>
                                        <div class="product-meta-item">
                                            <span>Variants:</span>
                                            <strong><?php echo $product['variant_count']; ?></strong>
                                        </div>
                                    </div>

                                    <div class="product-price">₫<?php echo number_format($product['base_price'], 0, ',', '.'); ?></div>

                                    <!-- Stock Status -->
                                    <?php 
                                        $available = $product['available'];
                                        if ($available > 20) {
                                            $stockClass = 'stock-available';
                                            $stockText = 'In Stock: ' . $available;
                                        } elseif ($available > 0) {
                                            $stockClass = 'stock-low';
                                            $stockText = 'Low: ' . $available;
                                        } else {
                                            $stockClass = 'stock-empty';
                                            $stockText = 'Out of Stock';
                                        }
                                    ?>
                                    <div class="product-stock <?php echo $stockClass; ?>">
                                        <?php echo $stockText; ?>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="product-status">
                                        <?php echo getStatusBadge($product['status']); ?>
                                    </div>

                                    <!-- Actions -->
                                    <div class="product-actions">
                                        <button class="btn btn-sm btn-outline-secondary" title="View" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state w-100">
                            <i class="bi bi-inbox"></i>
                            <p>No products found</p>
                            <small class="text-muted">Select a category or create a new product</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL: Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
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
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Category (optional)</label>
                        <select class="form-select" name="parent_id">
                            <option value="">-- Root Level --</option>
                            <?php foreach (getAllCategories($conn) as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Add Product -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addProductForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SKU (SPU)</label>
                            <input type="text" class="form-control" name="spu" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select category...</option>
                            <?php foreach (getAllCategories($conn) as $cat): ?>
                                <?php if ($cat['parent_id'] === null): // Only show root categories ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Base Price</label>
                            <input type="number" class="form-control" name="base_price" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Image URL</label>
                        <input type="url" class="form-control" name="image_url" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Category tree click handler
document.querySelectorAll('.cat-item').forEach(item => {
    item.addEventListener('click', function(e) {
        if (e.target.closest('.cat-actions')) return;
        
        const categoryId = this.dataset.id;
        window.location.href = '?category=' + categoryId;
    });
});

// Category search
document.getElementById('category-search')?.addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('.cat-item').forEach(item => {
        const catName = item.querySelector('.cat-name').textContent.toLowerCase();
        item.style.display = catName.includes(searchTerm) ? 'flex' : 'none';
    });
});

// Functions
function viewProduct(productId) {
    alert('View product ' + productId + ' - Implementation pending');
}

function editProduct(productId) {
    alert('Edit product ' + productId + ' - Implementation pending');
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        alert('Delete product ' + productId + ' - Implementation pending');
    }
}

function resetFilters() {
    window.location.href = '?';
}

// Form submissions (placeholder - needs backend implementation)
document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Add category - Implementation pending');
});

document.getElementById('addProductForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Add product - Implementation pending');
});
</script>
</body>
</html>
