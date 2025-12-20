<?php
/* ===== DỮ LIỆU GIẢ LẬP (GIỐNG STORE) ===== */
$products = [
  [
    'id' => 1,
    'name' => 'Beauty La Mousse Off/On',
    'desc' => 'Refreshing cleanser for daily skincare.',
    'price' => 52,
    'category' => 'Cleanser'
  ],
  [
    'id' => 2,
    'name' => 'Hydrating Serum',
    'desc' => 'Deep hydration for sensitive skin.',
    'price' => 68,
    'category' => 'Serum'
  ],
  [
    'id' => 3,
    'name' => 'Vitamin C Essence',
    'desc' => 'Brightening and smoothing skin tone.',
    'price' => 72,
    'category' => 'Serum'
  ],
  [
    'id' => 4,
    'name' => 'Moisturizing Cream',
    'desc' => 'Locks in moisture all day.',
    'price' => 60,
    'category' => 'Moisturizer'
  ]
];

/* ===== LẤY ID TỪ STORE ===== */
$id = $_GET['id'] ?? null;
$product = null;

/* ===== TÌM SẢN PHẨM ===== */
foreach ($products as $p) {
  if ($p['id'] == $id) {
    $product = $p;
    break;
  }
}

/* ===== NẾU KHÔNG TÌM THẤY ===== */
if (!$product) {
  echo "<main class='container py-5'><p>Product not found</p></main>";
  include '../includes/footer.php';
  exit;
}

$pageTitle = 'Product Detail';
$pageCss   = 'product.css';
include '../includes/header.php';
?>

<main class="container py-5">

  <div class="row">

    <!-- IMAGE GALLERY -->
    <div class="col-md-6 d-flex">
      <div class="me-3">
        <img src="assets/img/placeholder.png" class="thumb-img">
        <img src="assets/img/placeholder.png" class="thumb-img">
        <img src="assets/img/placeholder.png" class="thumb-img">
      </div>

      <div class="flex-grow-1 text-center">
        <img src="assets/img/placeholder.png" class="main-img">
      </div>
    </div>

    <!-- PRODUCT INFO -->
    <div class="col-md-6">
      <h2 class="text-darling"><?= $product['name'] ?></h2>
      <p class="fw-semibold"><?= $product['category'] ?></p>
      <p class="text-muted"><?= $product['desc'] ?></p>

      <h3 class="my-3">$<?= number_format($product['price'], 2) ?></h3>

      <!-- VARIANT (GIẢ LẬP) -->
      <div class="mb-3">
        <button type="button" class="btn btn-secondary">30 ML</button>
        <button type="button" class="btn btn-secondary">50 ML</button>
      </div>

      <!-- ORDER -->
      <a href="cart.php" class="btn btn-darling w-100 py-3">
        Order
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
        Đây là nội dung chi tiết sản phẩm. Nếu nội dung dài hơn thì khung này
        sẽ scroll được. Sau này bạn có thể tách mỗi tab thành 1 field trong DB.
      </p>
    </div>
  </div>

</main>

<?php include '../includes/footer.php'; ?>

