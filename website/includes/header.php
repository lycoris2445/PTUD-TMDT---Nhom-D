<?php
// Kiểm tra session để lấy thông tin đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= $pageTitle ?? 'Darling' ?></title>

  <link rel="stylesheet" href="../css/style.css">

  <?php if (isset($pageCss)): ?>
    <link rel="stylesheet" href="../css/<?= $pageCss ?>">
  <?php endif; ?>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    /* Style cơ bản cho dropdown user */
    .user-dropdown { position: relative; display: inline-block; }
    .user-name { color: #333; font-weight: 600; text-decoration: none; cursor: pointer; }
    .logout-btn { color: #dc3545 !important; font-weight: bold; }
    
    /* Cart badge style */
    .cart-count {
      position: absolute;
      top: -8px;
      right: -10px;
      background: #dc3545;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: bold;
      line-height: 1;
    }
  </style>
</head>

<body>

<header class="site-header">
  <div class="header-inner">

    <a href="home.php" class="logo-link" aria-label="Darling home">
      <img src="../../icons/logo_darling.svg" class="site-logo" alt="Darling">
    </a>

    <button class="nav-toggle" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="primary-nav">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <nav id="primary-nav">
      <ul class="nav-menu">
        <li><a href="home.php">Home</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="store.php">Store</a></li>
        <li>
          <a href="cart.php" style="position: relative;">
            <i class="bi bi-cart3"></i> Cart
            <span id="cart-count" class="cart-count" style="display: none;">0</span>
          </a>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="../php/orders.php"><i class="bi bi-bag-check"></i> My Orders</a></li>
        <?php endif; ?>
        <li><a href="../php/order.php">Order</a></li>
        <li><a href="../php/policy.php">Policy</a></li>
        <li><a href="../php/contact.php">Contact</a></li>
        
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="user-dropdown">
            <span class="user-name">
              <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
            </span>
            <ul class="nav-menu-sub"> <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li>
            <a href="login.php" class="login-btn">Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>

  </div>
</header>