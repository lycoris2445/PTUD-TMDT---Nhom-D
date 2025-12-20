<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= $pageTitle ?? 'Darling' ?></title>

  <!-- GLOBAL CSS -->
  <link rel="stylesheet" href="../css/style.css">

  <!-- PAGE CSS (nếu có) -->
  <?php if (isset($pageCss)): ?>
    <link rel="stylesheet" href="../css/<?= $pageCss ?>">
  <?php endif; ?>

  <!-- BOOTSTRAP (đang dùng cho footer & content) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>

<header class="site-header">
  <div class="header-inner">

    <!-- LOGO -->
    <a href="index.php" class="logo-link" aria-label="Darling home">
      <img src="../../icons/logo_darling.svg" class="site-logo" alt="Darling">
    </a>

    <!-- MOBILE TOGGLE -->
    <button class="nav-toggle" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="primary-nav">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- NAV -->
    <nav id="primary-nav">
      <ul class="nav-menu">
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="store.php">Store</a></li>
        <li><a href="cart.php">Cart</a></li>
        <li><a href="order.php">Order</a></li>
        <li><a href="policy.php">Policy</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li>
          <a href="login.php" class="login-btn">Login</a>
        </li>
      </ul>
    </nav>

  </div>
</header>
