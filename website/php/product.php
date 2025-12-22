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

      <h3 class="my-3" id="display-price">$<?= number_format((float)$product['base_price'], 2) ?></h3>

      <!-- VARIANTS -->
      <?php if (!empty($variants)): ?>
        <div class="mb-3">
          <label class="d-block fw-semibold mb-2">Select Option:</label>
          <div id="variant-buttons">
            <?php foreach ($variants as $variant): ?>
              <?php 
                $stock = getVariantStock($conn, $variant['id']);
                $attrs = $variant['attributes'];
                $attrLabel = !empty($attrs) ? implode(', ', $attrs) : 'Variant #' . $variant['id'];
                // Prices are already in USD in database
                $priceUSD = (float)$variant['price'] > 0 ? (float)$variant['price'] : (float)$product['base_price'];
                $priceUSD = number_format($priceUSD, 2, '.', '');
              ?>
              <button type="button" 
                      class="btn btn-outline-secondary variant-btn me-2 mb-2"
                      data-variant-id="<?= (int)$variant['id'] ?>"
                      data-variant-name="<?= htmlspecialchars($product['name'] . ' - ' . $attrLabel) ?>"
                      data-variant-price="<?= $priceUSD ?>"
                      data-variant-image="<?= !empty($variant['image_url']) ? htmlspecialchars($variant['image_url']) : (!empty($images) ? htmlspecialchars($images[0]['url']) : '') ?>"
                      data-stock="<?= (int)$stock ?>"
                      <?= $stock <= 0 ? 'disabled' : '' ?>>
                <?= htmlspecialchars($attrLabel) ?> ($<?= $priceUSD ?>)
                <?php if ($stock <= 0): ?>(Out of stock)<?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
          <small class="text-muted d-block mt-1" id="variant-hint">Please select an option</small>
        </div>
      <?php endif; ?>

      <!-- QUANTITY -->
      <div class="mb-3">
        <label class="d-block fw-semibold mb-2">Quantity:</label>
        <div class="input-group" style="max-width: 150px;">
          <button class="btn btn-outline-secondary" type="button" id="qty-minus">-</button>
          <input type="number" class="form-control text-center" id="product-qty" value="1" min="1" max="99">
          <button class="btn btn-outline-secondary" type="button" id="qty-plus">+</button>
        </div>
      </div>

      <!-- ADD TO CART -->
      <button type="button" id="add-to-cart-btn" class="btn btn-darling w-100 py-3" disabled>
        <i class="bi bi-cart-plus me-2"></i>Add to Cart
      </button>
      <div class="alert alert-success mt-2 d-none" id="cart-success">
        <i class="bi bi-check-circle me-2"></i>Added to cart successfully!
      </div>

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
<script>
// Product page specific JavaScript
(function() {
  let selectedVariant = null;
  const qtyInput = document.getElementById('product-qty');
  const qtyMinus = document.getElementById('qty-minus');
  const qtyPlus = document.getElementById('qty-plus');
  const addToCartBtn = document.getElementById('add-to-cart-btn');
  const variantButtons = document.querySelectorAll('.variant-btn');
  const displayPrice = document.getElementById('display-price');
  const variantHint = document.getElementById('variant-hint');
  const cartSuccess = document.getElementById('cart-success');

  // Quantity controls
  if (qtyMinus) {
    qtyMinus.addEventListener('click', () => {
      const current = parseInt(qtyInput.value) || 1;
      if (current > 1) {
        qtyInput.value = current - 1;
      }
    });
  }

  if (qtyPlus) {
    qtyPlus.addEventListener('click', () => {
      const current = parseInt(qtyInput.value) || 1;
      const max = parseInt(qtyInput.max) || 99;
      if (current < max) {
        qtyInput.value = current + 1;
      }
    });
  }

  // Variant selection
  variantButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      // Remove active class from all
      variantButtons.forEach(b => b.classList.remove('active', 'btn-primary'));
      variantButtons.forEach(b => b.classList.add('btn-outline-secondary'));
      
      // Add active to selected
      btn.classList.remove('btn-outline-secondary');
      btn.classList.add('active', 'btn-primary');

      // Store selected variant
      selectedVariant = {
        id: btn.dataset.variantId,
        name: btn.dataset.variantName,
        price: parseFloat(btn.dataset.variantPrice),
        image: btn.dataset.variantImage,
        stock: parseInt(btn.dataset.stock)
      };

      // Update price display
      if (displayPrice) {
        displayPrice.textContent = '$' + selectedVariant.price.toFixed(2);
      }

      // Update quantity max
      if (qtyInput) {
        qtyInput.max = selectedVariant.stock;
        if (parseInt(qtyInput.value) > selectedVariant.stock) {
          qtyInput.value = selectedVariant.stock;
        }
      }

      // Enable add to cart button
      addToCartBtn.disabled = false;
      if (variantHint) {
        variantHint.textContent = 'Selected: ' + btn.textContent.trim();
        variantHint.classList.remove('text-muted');
        variantHint.classList.add('text-success');
      }
    });
  });

  // If only one variant, auto-select it
  if (variantButtons.length === 1) {
    variantButtons[0].click();
  }

  // Add to cart
  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', () => {
      if (!selectedVariant) {
        alert('Please select a product option first.');
        return;
      }

      const qty = parseInt(qtyInput.value) || 1;
      
      if (qty > selectedVariant.stock) {
        alert(`Only ${selectedVariant.stock} items available in stock.`);
        return;
      }

      // Add to cart using Cart API
      if (window.Cart) {
        window.Cart.add({
          id: selectedVariant.id,
          name: selectedVariant.name,
          price: selectedVariant.price, // Price already in USD
          image: selectedVariant.image
        }, qty);

        // Show success message
        if (cartSuccess) {
          cartSuccess.classList.remove('d-none');
          setTimeout(() => {
            cartSuccess.classList.add('d-none');
          }, 3000);
        }

        // Optional: Show toast notification
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:15px 25px;border-radius:5px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;animation:slideIn 0.3s ease;';
        toast.innerHTML = `<i class="bi bi-check-circle me-2"></i><strong>Added to cart!</strong><br><small>${qty}x ${selectedVariant.name}</small>`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
          toast.style.animation = 'slideOut 0.3s ease';
          setTimeout(() => toast.remove(), 300);
        }, 2500);

        // Reset quantity to 1
        qtyInput.value = 1;
      }
    });
  }

  // Add CSS for animations
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(400px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(400px); opacity: 0; }
    }
    .variant-btn.active {
      border-width: 2px;
      font-weight: 600;
    }
  `;
  document.head.appendChild(style);
})();
</script>