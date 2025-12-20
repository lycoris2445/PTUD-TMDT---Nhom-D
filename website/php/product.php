<?php
require_once __DIR__ . '/../../config/cloudinary.php';
require_once __DIR__ . '/../includes/function_product.php';

$conn = getDBConnection();

// Get product ID from URL
$productId = (int)($_GET['id'] ?? 0);

if (!$productId) {
    echo "<main class='container py-5'><p>Product not found.</p></main>";
    include '../includes/footer.php';
    exit;
}

// Load product detail from database
$product = getProductDetail($conn, $productId);

if (!$product) {
    echo "<main class='container py-5'><p>Product not found.</p></main>";
    include '../includes/footer.php';
    exit;
}

// Load variants and images
$variants = getProductVariants($conn, $productId);
$images = getProductImages($conn, $productId);

$pageTitle = htmlspecialchars($product['name']) . ' - Darling';
$pageCss   = 'product.css';
include '../includes/header.php';
?>

<main class="container py-5">

  <div class="row">

    <!-- IMAGE GALLERY -->
    <div class="col-md-6 d-flex gap-3">
      <div class="d-flex flex-column gap-2" style="width: 80px;">
        <?php if (count($images) > 1): ?>
          <?php foreach (array_slice($images, 1, 3) as $img): ?>
            <img src="<?= htmlspecialchars($img['url']) ?>" class="thumb-img" alt="<?= htmlspecialchars($img['alt']) ?>">
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="flex-grow-1 text-center">
        <?php if (!empty($images)): ?>
          <img src="<?= htmlspecialchars($images[0]['url']) ?>" class="main-img" alt="<?= htmlspecialchars($images[0]['alt']) ?>">
        <?php else: ?>
          <img src="assets/img/placeholder.png" class="main-img" alt="No image">
        <?php endif; ?>
      </div>
    </div>

    <!-- PRODUCT INFO -->
    <div class="col-md-6">
      <h2 class="text-darling"><?= htmlspecialchars($product['name']) ?></h2>
      <p class="fw-semibold"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
      <p class="text-muted"><?= htmlspecialchars($product['description'] ?? '') ?></p>

      <h3 class="my-3">$<?= number_format((float)$product['base_price'], 2) ?></h3>

      <!-- VARIANTS -->
      <?php if (!empty($variants)): ?>
        <div class="mb-3">
          <label class="d-block fw-semibold mb-2">Options:</label>
          <?php foreach ($variants as $variant): ?>
            <?php 
              $stock = getVariantStock($conn, $variant['id']);
              $attrs = $variant['attributes'];
              $attrLabel = !empty($attrs) ? implode(', ', $attrs) : 'Variant #' . $variant['id'];
            ?>
            <button type="button" 
                    class="btn btn-secondary me-2 mb-2"
                    data-variant-id="<?= (int)$variant['id'] ?>"
                    data-variant-price="<?= htmlspecialchars($variant['price']) ?>"
                    data-stock="<?= (int)$stock ?>"
                    <?= $stock <= 0 ? 'disabled' : '' ?>>
              <?= htmlspecialchars($attrLabel) ?>
              <?php if ($stock <= 0): ?>(Out of stock)<?php endif; ?>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- ORDER -->
      <a href="cart.php" class="btn btn-darling w-100 py-3">
        Add to Cart
      </a>

      <!-- BENEFIT BOX -->
      <div class="benefit-box mt-4">
        <p>✔ Receive 2 Free Samples When You Spend $100</p>
        <p>✔ Receive $2 When You Return 5 Empty Containers</p>
        <p>✔ Receive Free 1-2-1 Expert Advice In Branches</p>
      </div>
    </div>

  </div>

  <!-- TABS -->
  <div class="mt-5">
    <ul class="nav nav-tabs">
      <li class="nav-item"><a class="nav-link active">Product Details</a></li>
      <li class="nav-item"><a class="nav-link">How To Apply</a></li>
      <li class="nav-item"><a class="nav-link">Ingredient</a></li>
      <li class="nav-item"><a class="nav-link">Specification</a></li>
    </ul>

    <div class="tab-box">
      <p>
        <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
      </p>
    </div>
  </div>

</main>

<?php include '../includes/footer.php'; ?>

<script src="../js/cart.js"></script>