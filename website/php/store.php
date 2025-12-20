<?php
require_once __DIR__ . '/../../config/cloudinary.php';
require_once __DIR__ . '/../includes/function_filter.php';
require_once __DIR__ . '/../includes/function_store.php';

$conn = getDBConnection();

// UI-only options (chưa có DB)
$skinConditions = ['Acne', 'Sensitive Skin', 'Dry Skin', 'Oily Skin'];
$featuredOptions = ['Best Seller', 'New Arrival', 'On Sale'];
$priceRanges = ['Dưới 500K', '500K - 1 triệu', 'Trên 1 triệu'];

// Parse filters
$filters = parseStoreFilters($_GET);

// Load categories & products from DB
$categories = getStoreCategories($conn);
$products = getStoreProducts($conn, $filters);

$pageTitle = "Store - Darling";
$pageCss   = 'store.css';
include '../includes/header.php';
?>

<main class="container my-5">
  <h1 class="mb-4 text-darling">Store</h1>

  <div class="row align-items-start">
    <?php include '../includes/store_filter_sidebar.php'; ?>

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
              <i class="bi bi-cart-plus"></i> Thêm vào giỏ
            </button>

          </div>
        <?php endforeach; ?>

      </div>
    </section>
  </div>
</main>

<?php include '../includes/footer.php'; ?>
