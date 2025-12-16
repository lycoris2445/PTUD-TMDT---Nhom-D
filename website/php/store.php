<?php
// ==================================================
// (1) KẾT NỐI DATABASE (CHƯA DÙNG – ĐỂ SAU)
// ==================================================
// Sau này dùng database thì:
// $conn = new mysqli("localhost", "root", "", "darling_db");

// ==================================================
// (2) DỮ LIỆU GIẢ LẬP – THAY THẾ DATABASE
// ==================================================

// Category filter (sau này lấy từ bảng categories)
$categories = [
  'Cleanser',
  'Toner',
  'Serum',
  'Moisturizer',
  'Sunscreen'
];

// Skin condition filter
$skinConditions = [
  'Acne',
  'Sensitive Skin',
  'Dry Skin',
  'Oily Skin'
];

// Featured filter
$featuredOptions = [
  'Best Seller',
  'New Arrival',
  'On Sale'
];

// Price filter
$priceRanges = [
  'Under $30',
  '$30 - $50',
  'Above $50'
];

// Product list (sau này là bảng products)
$products = [
  [
    'name' => 'Beauty La Mousse Off/On',
    'desc' => 'Refreshing cleanser for daily skincare.',
    'price' => 52,
    'category' => 'Cleanser',
    'condition' => 'Sensitive Skin',
    'featured' => 'Best Seller'
  ],
  [
    'name' => 'Hydrating Serum',
    'desc' => 'Deep hydration for sensitive skin.',
    'price' => 68,
    'category' => 'Serum',
    'condition' => 'Dry Skin',
    'featured' => 'New Arrival'
  ],
  [
    'name' => 'Vitamin C Essence',
    'desc' => 'Brightening and smoothing skin tone.',
    'price' => 72,
    'category' => 'Serum',
    'condition' => 'Oily Skin',
    'featured' => 'On Sale'
  ],
  [
    'name' => 'Moisturizing Cream',
    'desc' => 'Locks in moisture all day.',
    'price' => 60,
    'category' => 'Moisturizer',
    'condition' => 'Dry Skin',
    'featured' => 'Best Seller'
  ]
];

// ==================================================
// (3) NHẬN FILTER TỪ FORM (GET)
// ==================================================
$selectedCategories = $_GET['category'] ?? [];
$selectedConditions = $_GET['condition'] ?? [];
$selectedFeatured   = $_GET['featured'] ?? [];
$selectedPrices     = $_GET['price'] ?? [];

// ==================================================
// (4) LỌC SẢN PHẨM (GIẢ LẬP – SAU NÀY ĐỔI THÀNH SQL)
// ==================================================
$filteredProducts = array_filter($products, function ($p) use (
  $selectedCategories,
  $selectedConditions,
  $selectedFeatured,
  $selectedPrices
) {
  if ($selectedCategories && !in_array($p['category'], $selectedCategories)) {
    return false;
  }

  if ($selectedConditions && !in_array($p['condition'], $selectedConditions)) {
    return false;
  }

  if ($selectedFeatured && !in_array($p['featured'], $selectedFeatured)) {
    return false;
  }

  if ($selectedPrices) {
    $match = false;
    foreach ($selectedPrices as $range) {
      if ($range === 'Under $30' && $p['price'] < 30) $match = true;
      if ($range === '$30 - $50' && $p['price'] >= 30 && $p['price'] <= 50) $match = true;
      if ($range === 'Above $50' && $p['price'] > 50) $match = true;
    }
    if (!$match) return false;
  }

  return true;
});

include 'header.php';
?>

<!--nd trang-->
<main class="container my-5">

  <h1 class="mb-4 text-darling">Store</h1>


  <div class="row">

    <!-- FILTER SIDEBAR -->
    <aside class="col-md-3">
      <h5 class="text-darling">Filter</h5>

      <form method="get">

        <!-- CATEGORY -->
        <div class="mb-4">
          <strong>Category</strong>
          <?php foreach ($categories as $cat): ?>
            <div class="form-check">
              <input class="form-check-input"
                     type="checkbox"
                     name="category[]"
                     value="<?= $cat ?>"
                     <?= in_array($cat, $selectedCategories) ? 'checked' : '' ?>>
              <label class="form-check-label"><?= $cat ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- SKIN CONDITION -->
        <div class="mb-4">
          <strong>Skin Condition</strong>
          <?php foreach ($skinConditions as $cond): ?>
            <div class="form-check">
              <input class="form-check-input"
                     type="checkbox"
                     name="condition[]"
                     value="<?= $cond ?>"
                     <?= in_array($cond, $selectedConditions) ? 'checked' : '' ?>>
              <label class="form-check-label"><?= $cond ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- FEATURED -->
        <div class="mb-4">
          <strong>Featured</strong>
          <?php foreach ($featuredOptions as $opt): ?>
            <div class="form-check">
              <input class="form-check-input"
                     type="checkbox"
                     name="featured[]"
                     value="<?= $opt ?>"
                     <?= in_array($opt, $selectedFeatured) ? 'checked' : '' ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- PRICE -->
        <div class="mb-4">
          <strong>Price</strong>
          <?php foreach ($priceRanges as $range): ?>
            <div class="form-check">
              <input class="form-check-input"
                     type="checkbox"
                     name="price[]"
                     value="<?= $range ?>"
                     <?= in_array($range, $selectedPrices) ? 'checked' : '' ?>>
              <label class="form-check-label"><?= $range ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <button class="btn btn-outline-dark w-100">Apply Filter</button>
      </form>
    </aside>

    <!-- PRODUCT LIST -->
    <section class="col-md-9">
      <div class="row g-4">

        <?php if (empty($filteredProducts)): ?>
          <p>No products found.</p>
        <?php endif; ?>

        <?php foreach ($filteredProducts as $product): ?>
          <!--
            (5) RENDER PRODUCT
            Sau này thay bằng:
            while ($row = mysqli_fetch_assoc($result)) { ... }
          -->
          <div class="col-md-4">
            <div class="text-center border p-3">

              <div class="bg-light d-flex align-items-center justify-content-center"
                   style="height:200px;">
                [ Image ]
              </div>

              <h6 class="mt-3"><?= $product['name'] ?></h6>
              <p class="text-muted small"><?= $product['desc'] ?></p>
              <p class="text-darling fw-semibold">
                $<?= number_format($product['price'], 2) ?>
              </p>

            </div>
          </div>
        <?php endforeach; ?>

      </div>
    </section>

  </div>
</main>

<?php include 'footer.php'; ?>
