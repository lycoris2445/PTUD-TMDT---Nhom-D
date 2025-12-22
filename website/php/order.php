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
              <label class="form-label" for="shipping_carrier">Đơn vị vận chuyển</label>
              <select class="form-select" id="shipping_carrier" name="shipping_carrier" required>
                <option value="GHN" selected>Giao hàng nhanh (GHN)</option>
                <option value="GHTK">Giao hàng tiết kiệm (GHTK)</option>
                <option value="VNPost">VNPost</option>
                <option value="J&T">J&T Express</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label d-block fw-bold mb-3">Phương thức thanh toán</label>
              
              <div class="form-check mb-3 p-3 border rounded">
                <input class="form-check-input" type="radio" name="payment" id="pay-cod" value="COD" checked>
                <label class="form-check-label w-100" for="pay-cod" style="cursor: pointer;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-cash-coin fs-4 me-3 text-success"></i>
                    <div>
                      <strong>Thanh toán khi nhận hàng (COD)</strong>
                      <div class="small text-muted">Thanh toán bằng tiền mặt khi nhận hàng</div>
                    </div>
                  </div>
                </label>
              </div>
              
              <div class="form-check mb-3 p-3 border rounded">
                <input class="form-check-input" type="radio" name="payment" id="pay-stripe" value="STRIPE">
                <label class="form-check-label w-100" for="pay-stripe" style="cursor: pointer;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-credit-card fs-4 me-3 text-primary"></i>
                    <div>
                      <strong>Thanh toán bằng thẻ</strong>
                      <div class="small text-muted">Visa, Mastercard, American Express</div>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Stripe Card Element -->
            <div class="col-12" id="stripe-card-wrapper" style="display:none;">
              <div class="alert alert-info mb-3">
                <i class="bi bi-shield-check"></i> 
                <strong>Thanh toán an toàn</strong> - Thông tin thẻ được mã hóa bởi Stripe
              </div>
              <label class="form-label fw-bold">Thông tin thẻ</label>
              <div id="card-element" class="form-control" style="height:40px; padding:10px;"></div>
              <div id="card-errors" class="text-danger small mt-2"></div>
              <div class="mt-2 small text-muted">
                <i class="bi bi-info-circle"></i> Test card: 4242 4242 4242 4242 | Exp: 12/34 | CVC: 123
              </div>
            </div>

            <div class="col-12">
              <label class="form-label" for="note">Ghi chú đơn hàng</label>
              <textarea class="form-control" id="note" name="note" rows="3" placeholder="Ví dụ: giao giờ hành chính..."></textarea>
            </div>

            <div class="col-12">
              <button class="btn btn-darling w-100" type="submit" id="submit-btn">
                <span id="btn-text">Đặt hàng</span>
                <span id="btn-spinner" class="spinner-border spinner-border-sm ms-2" style="display:none;"></span>
              </button>
              <p id="order-note" class="mt-2 small"></p>
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

<!-- Stripe.js CDN -->
<script src="https://js.stripe.com/v3/"></script>
<script src="../js/cart.js"></script>
<script src="../js/checkout-stripe.js"></script>
<?php include '../includes/footer.php'; ?>