<?php
$pageTitle = "Store - Darling";
$pageCss   = 'store.css';
include '../includes/header.php';
$conn = require __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../config/cloudinary.php';
require_once __DIR__ . '/../includes/function_filter.php';
require_once __DIR__ . '/../includes/function_store.php';

// UI-only options (chưa có DB) -> hạn chế
$skinConditions = ['Acne', 'Sensitive Skin', 'Dry Skin', 'Oily Skin'];
$featuredOptions = ['Best Seller', 'New Arrival', 'On Sale'];
$priceRanges = ['Under $20', '$20 to $50', 'Over $50'];
// Parse filters
$filters = parseStoreFilters($_GET);

// Load categories & products from DB
$categories = getStoreCategories($conn);
$products = getStoreProducts($conn, $filters);
?>

<main class="container my-5">
  <h1 class="mb-4 text-darling">Store</h1>

  <div class="row align-items-start">
    <aside class="col-md-3 store-sidebar">
      <div class="filter-box"> 
        <h5 class="text-darling">Filter</h5>

        <form method="get">

            <!-- CATEGORY (Hierarchical) -->
            <div class="mb-4">
            <strong>Category</strong>
            <?php foreach ($categories as $parent): ?>
                <!-- Parent Category -->
                <div class="form-check">
                <input class="form-check-input"
                        type="checkbox"
                        name="category[]"
                        value="<?= htmlspecialchars($parent['name']) ?>"
                        <?= in_array($parent['name'], $filters['categories'], true) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold"><?= htmlspecialchars($parent['name']) ?></label>
                </div>
                
                <!-- Child Categories (nếu có) -->
                <?php if (!empty($parent['children'])): ?>
                    <div class="ps-4">
                    <?php foreach ($parent['children'] as $child): ?>
                        <div class="form-check">
                        <input class="form-check-input"
                                type="checkbox"
                                name="category[]"
                                value="<?= htmlspecialchars($child['name']) ?>"
                                <?= in_array($child['name'], $filters['categories'], true) ? 'checked' : '' ?>>
                        <label class="form-check-label"><?= htmlspecialchars($child['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            </div>

            <!-- SKIN CONDITION (UI only) -->
            <div class="mb-4">
            <strong>Skin Condition</strong>
            <?php foreach ($skinConditions as $cond): ?>
                <div class="form-check">
                <input class="form-check-input"
                        type="checkbox"
                        name="condition[]"
                        value="<?= htmlspecialchars($cond) ?>"
                        <?= in_array($cond, $filters['conditions'], true) ? 'checked' : '' ?>>
                <label class="form-check-label"><?= htmlspecialchars($cond) ?></label>
                </div>
            <?php endforeach; ?>
            </div>

            <!-- FEATURED (UI only) -->
            <div class="mb-4">
            <strong>Featured</strong>
            <?php foreach ($featuredOptions as $opt): ?>
                <div class="form-check">
                <input class="form-check-input"
                        type="checkbox"
                        name="featured[]"
                        value="<?= htmlspecialchars($opt) ?>"
                        <?= in_array($opt, $filters['featured'], true) ? 'checked' : '' ?>>
                <label class="form-check-label"><?= htmlspecialchars($opt) ?></label>
                </div>
            <?php endforeach; ?>
            </div>

            <!-- PRICE -->
            <div class="mb-4">
            <strong>Price</strong>
              <?php foreach ($priceRanges as $range): ?>
                <label class="d-flex align-items-center gap-2 mb-2">
                  <input
                    type="radio"
                    name="prices[]"
                    value="<?= htmlspecialchars($range) ?>"
                    <?= (!empty($_GET['prices']) && in_array($range, (array)$_GET['prices'], true)) ? 'checked' : '' ?>
                  />
                  <span><?= htmlspecialchars($range) ?></span>
                </label>
              <?php endforeach; ?>
            </div>

            <button class="btn btn-outline-dark w-100">Apply Filter</button>
        </form>
      </div>
    </aside>

    <!-- PRODUCT LIST -->
    <section class="col-md-9">
      <div class="row g-4 align-items-start">

        <?php if (empty($products)): ?>
          <p>No products found.</p>
        <?php endif; ?>

        <?php foreach ($products as $product): ?>
          <div class="col-md-4">
            <a href="product.php?id=<?= (int)$product['id'] ?>" class="text-decoration-none text-dark">
              <div class="product-card text-center border p-3">

                <div class="product-image-wrap bg-light d-flex align-items-center justify-content-center">
                  <?php if (!empty($product['image_url'])): ?>
                    <img class="product-image"
                        src="<?= htmlspecialchars($product['image_url']) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>">
                  <?php else: ?>
                    <span class="text-muted">[ No Image ]</span>
                  <?php endif; ?>
                </div>

                <h6 class="mt-3"><?= htmlspecialchars($product['name']) ?></h6>
                <p class="text-muted small"><?= htmlspecialchars($product['desc'] ?? '') ?></p>
                <p class="text-darling fw-semibold">
                  $<?= number_format((float)$product['price'], 2, '.', ',') ?>
                </p>
              </div>
            </a>

            <button class="btn btn-sm btn-darling w-100"
                    data-add-to-cart
                    data-id="<?= (int)$product['id'] ?>"
                    data-variant-id="<?= (int)($product['variant_id'] ?? 0) ?>"
                    data-name="<?= htmlspecialchars($product['name']) ?>"
                    data-price="<?= htmlspecialchars($product['price']) ?>"
                    data-image="<?= htmlspecialchars($product['image_url'] ?? '') ?>">
            </button>

          </div>
        <?php endforeach; ?>

      </div>
    </section>
  </div>
</main>

<?php include '../includes/footer.php'; ?>
