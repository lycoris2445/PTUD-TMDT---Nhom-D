<?php
$pageTitle = "Cart";
$pageCss = "../css/gio-hang.css";
include '../includes/header.php';
?>

<main class="container py-4" data-page="cart">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0 text-darling">Giỏ hàng</h1>
    <a href="store.php" class="btn btn-outline-dark btn-sm">Tiếp tục mua sắm</a>
  </div>

  <div id="cart-empty" class="alert alert-info d-none">
    Giỏ hàng của bạn đang trống. <a href="store.php" class="alert-link">Mua sắm ngay</a>
  </div>

  <div id="cart-table-wrap" class="row g-4">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th scope="col">Sản phẩm</th>
                  <th scope="col" class="text-end">Giá</th>
                  <th scope="col" class="text-center">Số lượng</th>
                  <th scope="col" class="text-end">Tạm tính</th>
                  <th scope="col" class="text-center">Xóa</th>
                </tr>
              </thead>
              <tbody id="cart-table-body"></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button id="btn-clear-cart" class="btn btn-outline-secondary">Xóa giỏ hàng</button>
        <a class="btn btn-outline-dark" href="store.php">Tiếp tục mua sắm</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm sticky-top" style="top: 90px;">
        <div class="card-body">
          <h5 class="card-title mb-3">Tổng kết</h5>
          <div class="d-flex justify-content-between py-1">
            <span>Tạm tính</span>
            <strong id="summary-subtotal">0₫</strong>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span>Phí vận chuyển</span>
            <strong id="summary-shipping">0₫</strong>
          </div>
          <hr>
          <div class="d-flex justify-content-between py-1 fs-5">
            <span>Tổng</span>
            <strong id="summary-total">0₫</strong>
          </div>
          <a class="btn btn-darling w-100 mt-3" href="order.php">Tiến hành thanh toán</a>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="../js/cart.js"></script>
<?php include '../includes/footer.php'; ?>