<?php
$pageTitle = "Payment - Checkout";
$pageCss = "thanh-toan.css";
include '../includes/header.php';
?>

<main class="container py-4" data-page="checkout">
  <h1 class="h4 mb-4 text-darling">Checkout</h1>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Shipping Information</h5>
          <form id="checkout-form" class="row g-3">
            <div class="col-12">
              <label class="form-label" for="fullname">Full Name</label>
              <input class="form-control" id="fullname" name="fullname" type="text" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">Phone Number</label>
              <input class="form-control" id="phone" name="phone" type="tel" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="email">Email (tuỳ chọn)</label>
              <input class="form-control" id="email" name="email" type="email">
            </div>
            <div class="col-12">
              <label class="form-label" for="address">Address</label>
              <input class="form-control" id="address" name="address" type="text" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="city">City</label>
              <input class="form-control" id="city" name="city" type="text">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="district">District</label>
              <input class="form-control" id="district" name="district" type="text">
            </div>

            <div class="col-12">
              <label class="form-label" for="shipping_carrier">Shipping Carrier</label>
              <select class="form-select" id="shipping_carrier" name="shipping_carrier" required>
                <option value="GHN" selected>FedEX</option>
                <option value="GHTK">UPS</option>
                <option value="VNPost">DHL</option>
                <option value="J&T">USPS</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label d-block fw-bold mb-3">Payment Method</label>
              
              <div class="form-check mb-3 p-3 border rounded">
                <input class="form-check-input" type="radio" name="payment" id="pay-cod" value="COD" checked>
                <label class="form-check-label w-100" for="pay-cod" style="cursor: pointer;">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-cash-coin fs-4 me-3 text-success"></i>
                    <div>
                      <strong>Cash on Delivery (COD)</strong>
                      <div class="small text-muted">Pay with cash upon delivery</div>
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
                <strong>Secure Payment</strong> - Card information is encrypted by Stripe
              </div>
              <label class="form-label fw-bold">Card Information</label>
              <div id="card-element" class="form-control" style="height:40px; padding:10px;"></div>
              <div id="card-errors" class="text-danger small mt-2"></div>
              <div class="mt-2 small text-muted">
                <i class="bi bi-info-circle"></i> Test card: 4242 4242 4242 4242 | Exp: 12/34 | CVC: 123
              </div>
            </div>

            <div class="col-12">
              <label class="form-label" for="note">Order Note</label>
              <textarea class="form-control" id="note" name="note" rows="3" placeholder="For example: deliver during business hours..."></textarea>
            </div>

            <div class="col-12">
              <button class="btn btn-darling w-100" type="submit" id="submit-btn">
                <span id="btn-text">Place Order</span>
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
        No products in the cart. <a href="store.php" class="alert-link">Back to shopping</a>
      </div>

      <div id="checkout-summary-wrap" class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Your Order</h5>
          <ul id="checkout-items" class="list-group list-group-flush mb-3"></ul>
          <div class="d-flex justify-content-between py-1">
            <span>Subtotal</span>
            <strong id="checkout-subtotal">$0</strong>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span>Shipping Fee</span>
            <strong id="checkout-shipping">$0</strong>
          </div>
          <hr>
          <div class="d-flex justify-content-between py-1 fs-5">
            <span>Total</span>
            <strong id="checkout-total">$0</strong>
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