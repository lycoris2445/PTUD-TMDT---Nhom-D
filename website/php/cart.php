<?php
$pageTitle = "Shopping Cart";
$pageCss = "cart.css";
include '../includes/header.php';
?>

<main class="container py-4" data-page="cart">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0 text-darling">Shopping Cart</h1>
    <a href="store.php" class="btn btn-outline-dark btn-sm">Continue Shopping</a>
  </div>

  <div id="cart-empty" class="alert alert-info d-none">
    Your cart is empty. <a href="store.php" class="alert-link">Start shopping</a>
  </div>

  <div id="cart-table-wrap" class="row g-4">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th scope="col">Product</th>
                  <th scope="col" class="text-end">Price</th>
                  <th scope="col" class="text-center">Quantity</th>
                  <th scope="col" class="text-end">Subtotal</th>
                  <th scope="col" class="text-center">Remove</th>
                </tr>
              </thead>
              <tbody id="cart-table-body"></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button id="btn-clear-cart" class="btn btn-outline-secondary">Clear Cart</button>
        <a class="btn btn-outline-dark" href="store.php">Continue Shopping</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm sticky-top" style="top: 90px;">
        <div class="card-body">
          <h5 class="card-title mb-3">Order Summary</h5>
          <div class="d-flex justify-content-between py-2">
            <span class="text-muted">Subtotal</span>
            <strong id="summary-subtotal">$0.00</strong>
          </div>
          <div class="d-flex justify-content-between py-2">
            <span class="text-muted">Shipping</span>
            <strong id="summary-shipping">$0.00</strong>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between py-2 fs-5">
            <span class="fw-bold">Total</span>
            <strong class="text-darling" id="summary-total">$0.00</strong>
          </div>
          <div class="text-muted small mt-2 mb-3">
            <i class="bi bi-info-circle"></i> Free shipping on orders over $20
          </div>
          <a class="btn btn-darling w-100 py-3" href="order.php">
            <i class="bi bi-lock-fill me-2"></i>Proceed to Checkout
          </a>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="../js/cart.js"></script>
<?php include '../includes/footer.php'; ?>