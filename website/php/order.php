<?php
$pageTitle = "Thanh toán";
$pageCss = "thanh-toan.css";
include '../includes/header.php';
?>

<main class="container py-4" data-page="checkout">
  <h1 class="h4 mb-4 text-darling">Thanh toán</h1>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Thông tin giao hàng</h5>
          <form id="checkout-form" class="row g-3">
            <div class="col-12">
              <label class="form-label" for="fullname">Họ và tên</label>
              <input class="form-control" id="fullname" name="fullname" type="text" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">Số điện thoại</label>
              <input class="form-control" id="phone" name="phone" type="tel" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="email">Email (tuỳ chọn)</label>
              <input class="form-control" id="email" name="email" type="email">
            </div>
            <div class="col-12">
              <label class="form-label" for="address">Địa chỉ</label>
              <input class="form-control" id="address" name="address" type="text" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="city">Tỉnh/Thành</label>
              <input class="form-control" id="city" name="city" type="text">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="district">Quận/Huyện</label>
              <input class="form-control" id="district" name="district" type="text">
            </div>

            <div class="col-12">
              <label class="form-label d-block">Phương thức thanh toán</label>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="payment" id="pay-cod" value="COD" checked>
                <label class="form-check-label" for="pay-cod">Thanh toán khi nhận hàng (COD)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="payment" id="pay-bank" value="BANK">
                <label class="form-check-label" for="pay-bank">Chuyển khoản ngân hàng</label>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label" for="note">Ghi chú đơn hàng</label>
              <textarea class="form-control" id="note" name="note" rows="3" placeholder="Ví dụ: giao giờ hành chính..."></textarea>
            </div>

            <div class="col-12">
              <button class="btn btn-darling w-100" type="submit">Đặt hàng</button>
              <p id="order-note" class="text-success mt-2 small"></p>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div id="checkout-empty" class="alert alert-warning d-none">
        Chưa có sản phẩm nào trong giỏ hàng. <a href="store.php" class="alert-link">Quay lại mua sắm</a>
      </div>

      <div id="checkout-summary-wrap" class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Đơn hàng của bạn</h5>
          <ul id="checkout-items" class="list-group list-group-flush mb-3"></ul>
          <div class="d-flex justify-content-between py-1">
            <span>Tạm tính</span>
            <strong id="checkout-subtotal">0₫</strong>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span>Phí vận chuyển</span>
            <strong id="checkout-shipping">0₫</strong>
          </div>
          <hr>
          <div class="d-flex justify-content-between py-1 fs-5">
            <span>Tổng</span>
            <strong id="checkout-total">0₫</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="../js/cart.js"></script>
<?php include '../includes/footer.php'; ?>