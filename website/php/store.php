<?php
// ==================================================
// (1) KẾT NỐI DATABASE
// ==================================================
require_once __DIR__ . '/../../config/cloudinary.php';

$conn = getDBConnection();

// ==================================================
// (2) LẤY DỮ LIỆU TỪ DATABASE
// ==================================================

// Lấy danh sách categories
$categories = [];
$catResult = $conn->query("SELECT DISTINCT name FROM categories ORDER BY name");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['name'];
    }
}
// Fallback nếu chưa có categories trong DB
if (empty($categories)) {
    $categories = ['Cleanser', 'Toner', 'Serum', 'Moisturizer', 'Sunscreen'];
}

// Skin condition filter (giữ tĩnh vì chưa có trong DB)
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

// Price filter (VND)
$priceRanges = [
  'Dưới 500K',
  '500K - 1 triệu',
  'Trên 1 triệu'
];

// ==================================================
// (3) NHẬN FILTER TỪ FORM (GET)
// ==================================================
$selectedCategories = $_GET['category'] ?? [];
$selectedConditions = $_GET['condition'] ?? [];
$selectedFeatured   = $_GET['featured'] ?? [];
$selectedPrices     = $_GET['price'] ?? [];

// ==================================================
// (4) TRUY VẤN SẢN PHẨM TỪ DATABASE
// ==================================================
$sql = "
    SELECT 
        p.id,
        p.name,
        p.description AS `desc`,
        COALESCE(pv.price, p.base_price) AS price,
        c.name AS category,
        pv.image_url,
        pv.id AS variant_id,
        pv.sku_code
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    WHERE p.status = 'ACTIVE'
";

// Build WHERE conditions
$whereConditions = [];
$params = [];
$types = '';

if (!empty($selectedCategories)) {
    $placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));
    $whereConditions[] = "c.name IN ($placeholders)";
    foreach ($selectedCategories as $cat) {
        $params[] = $cat;
        $types .= 's';
    }
}

if (!empty($selectedPrices)) {
    $priceConditions = [];
    foreach ($selectedPrices as $range) {
        if ($range === 'Dưới 500K') {
            $priceConditions[] = "COALESCE(pv.price, p.base_price) < 500000";
        } elseif ($range === '500K - 1 triệu') {
            $priceConditions[] = "(COALESCE(pv.price, p.base_price) >= 500000 AND COALESCE(pv.price, p.base_price) <= 1000000)";
        } elseif ($range === 'Trên 1 triệu') {
            $priceConditions[] = "COALESCE(pv.price, p.base_price) > 1000000";
        }
    }
    if ($priceConditions) {
        $whereConditions[] = '(' . implode(' OR ', $priceConditions) . ')';
    }
}

if ($whereConditions) {
    $sql .= ' AND ' . implode(' AND ', $whereConditions);
}

$sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

// Execute query
$products = [];
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fallback: nếu không có sản phẩm trong DB, dùng dữ liệu mẫu
if (empty($products)) {
    $products = [
        [
            'id' => 1,
            'name' => 'Beauty La Mousse Off/On',
            'desc' => 'Refreshing cleanser for daily skincare.',
            'price' => 520000,
            'category' => 'Cleanser',
            'image_url' => ''
        ],
        [
            'id' => 2,
            'name' => 'Hydrating Serum',
            'desc' => 'Deep hydration for sensitive skin.',
            'price' => 680000,
            'category' => 'Serum',
            'image_url' => ''
        ],
        [
            'id' => 3,
            'name' => 'Vitamin C Essence',
            'desc' => 'Brightening and smoothing skin tone.',
            'price' => 720000,
            'category' => 'Serum',
            'image_url' => ''
        ],
        [
            'id' => 4,
            'name' => 'Moisturizing Cream',
            'desc' => 'Locks in moisture all day.',
            'price' => 600000,
            'category' => 'Moisturizer',
            'image_url' => ''
        ]
    ];
}

// Dùng $products trực tiếp, không cần filter thêm
$filteredProducts = $products;

$pageTitle = "Store - Darling";
$pageCss = "../css/san-pham.css";
include '../includes/header.php';
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
              <input class="form-check-input" type="checkbox" name="price[]"
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
          <div class="col-md-4">
            <div class="product-card text-center border p-3">

              <div class="product-image-wrap bg-light d-flex align-items-center justify-content-center"
                   style="height:200px; overflow:hidden; border-radius: 8px;">
                <?php if (!empty($product['image_url'])): ?>
                  <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                       alt="<?= htmlspecialchars($product['name']) ?>"
                       style="max-width:100%; max-height:100%; object-fit:cover;">
                <?php else: ?>
                  <span class="text-muted">[ No Image ]</span>
                <?php endif; ?>
              </div>

              <h6 class="mt-3"><?= htmlspecialchars($product['name']) ?></h6>
              <p class="text-muted small"><?= htmlspecialchars($product['desc'] ?? '') ?></p>
              <p class="text-darling fw-semibold">
                <?= number_format($product['price'], 0, ',', '.') ?>₫
              </p>

              <button class="btn btn-sm btn-darling"
                      data-add-to-cart
                      data-id="<?= $product['id'] ?>"
                      data-name="<?= htmlspecialchars($product['name']) ?>"
                      data-price="<?= $product['price'] ?>"
                      data-image="<?= htmlspecialchars($product['image_url'] ?? '') ?>">
                <i class="bi bi-cart-plus"></i> Thêm vào giỏ
              </button>

              </div>
            </a>
          </div>
        <?php endforeach; ?>

      </div>
    </section>

  </div>
</main>

<?php include '../includes/footer.php'; ?>
