<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chính sách - Darling</title>

    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/chinh-sach.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>

<header>
    <div class="container">
        <a href="index.php" class="logo">Darling</a>
        <nav>
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="gioi-thieu.php">Giới thiệu</a></li>
                <li><a href="san-pham.php">Sản phẩm</a></li>
                <li><a href="gio-hang.php">Giỏ hàng</a></li>
                <li><a href="thanh-toan.php">Thanh toán</a></li>
                <li><a href="chinh-sach.php" class="active">Chính sách</a></li>
                <li><a href="lien-he.php">Liên hệ</a></li>
                <li><a href="dang-nhap.php" class="login-icon">Đăng nhập</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="py-5">
    <div class="container">
        <h2 class="section-title">Chính Sách & Quyền Riêng Tư</h2>

        <!-- PHẦN NỘI DUNG ACCORDION GIỮ NGUYÊN -->
        <?php include 'policy-content.php'; ?>
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-content"></div>
        <div class="copyright">&copy; 2025 Darling.</div>
    </div>
</footer>

<script src="../js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>