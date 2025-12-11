<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Liên hệ - Darling</title>

  <!-- Main CSS -->
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="../css/lien-he.css">

  <!-- Bootstrap 5.3.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>

<!-- === HEADER === -->
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
        <li><a href="chinh-sach.php">Chính sách</a></li>
        <li><a href="lien-he.php" class="active">Liên hệ</a></li>
        <li><a href="dang-nhap.php" class="login-icon" title="Đăng nhập">Đăng nhập</a></li>
      </ul>
    </nav>
  </div>
</header>

<!-- === MAIN === -->
<main>
  <div class="container-xl pt-5 pb-5">

    <!-- Title -->
    <h1 class="fw-bold mb-3">Contact Us</h1>

    <p class="text-secondary mb-5">
      Trung tâm Dịch vụ Khách hàng Darling rất hân hạnh được hỗ trợ bạn...
      <br>
      <small class="text-dark">
      Vui lòng điền vào biểu mẫu này để gửi yêu cầu của bạn.
      </small>
    </p>

    <!-- FORM -->
    <div class="row mb-5">
      <div class="col-12">
        <div class="card p-4 p-md-5 border-0 shadow-sm">

          <h5 class="card-title mb-4 fw-bold">
            <i class="bi bi-pencil-square me-2"></i> Liên hệ chúng tôi
          </h5>

          <form action="#" method="post">

            <h6 class="mb-3 fw-bold">Thông tin của quý khách</h6>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <select class="form-select">
                  <option selected>Chức danh</option>
                  <option>Ông</option>
                  <option>Bà</option>
                </select>
              </div>

              <div class="col-md-6"><input type="text" class="form-control" placeholder="Tên"></div>
              <div class="col-md-6"><input type="text" class="form-control" placeholder="Họ"></div>
              <div class="col-md-6"><select class="form-select"><option selected>Quốc gia/Vùng lãnh thổ</option></select></div>
              <div class="col-md-6"><select class="form-select"><option selected>Ngôn ngữ</option></select></div>
              <div class="col-md-6"><input type="email" class="form-control" placeholder="Email Address"></div>
              <div class="col-md-6"><input type="tel" class="form-control" placeholder="Phone Number"></div>
            </div>

            <h6 class="mb-3 fw-bold">Yêu cầu của quý khách</h6>

            <div class="mb-4">
              <input type="text" class="form-control mb-3" placeholder="Subject">

              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge text-bg-light border text-dark">Loại da</span>
                <span class="badge text-bg-light border text-dark">Ưu đãi</span>
                <span class="badge text-bg-light border text-dark">Hoàn trả</span>
                <span class="badge text-bg-danger text-white">Sản phẩm</span>
              </div>

              <textarea class="form-control" rows="4" placeholder="Your Text"></textarea>
              <div class="form-text text-end">0/500</div>
            </div>

            <div class="form-check mb-4">
              <input class="form-check-input" type="checkbox" id="policyCheck">
              <label class="form-check-label" for="policyCheck">
                Tôi đã đọc và đồng ý với chính sách quyền riêng tư của Darling
              </label>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-primary px-4 rounded-pill">Gửi</button>
            </div>

          </form>

        </div>
      </div>
    </div>

    <!-- 3 CARD SERVICES -->
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="card text-center h-100 p-3 border-0 shadow-sm">
          <div class="card-body">
            <i class="bi bi-chat-dots-fill display-5 mb-3"></i>
            <h5 class="card-title fw-bold">CHAT ONLINE</h5>
            <p class="card-text mb-3">...</p>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3">Tìm hiểu thêm</button>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-center h-100 p-3 border-0 shadow-sm">
          <div class="card-body">
            <i class="bi bi-telephone-fill display-5 mb-3"></i>
            <h5 class="card-title fw-bold">CALL US</h5>
            <p class="card-text mb-3">...</p>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3">Tìm hiểu thêm</button>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-center h-100 p-3 border-0 shadow-sm">
          <div class="card-body">
            <i class="bi bi-envelope-fill display-5 mb-3"></i>
            <h5 class="card-title fw-bold">INSTANT MESSAGE</h5>
            <p class="card-text mb-3">...</p>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3">Tìm hiểu thêm</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Icons Bottom -->
    <div class="d-flex flex-wrap justify-content-between text-center py-4 mb-5 border-top border-bottom bottom-icons">
      <div class="p-2">
        <i class="bi bi-emoji-heart-eyes-fill"></i>
        <p class="mt-2 small">Không thử nghiệm trên động vật</p>
      </div>
      <div class="p-2">
        <i class="bi bi-chat-left-text-fill"></i>
        <p class="mt-2 small">Không sử dụng nguyên liệu nguồn gốc động vật</p>
      </div>
      <div class="p-2">
        <i class="bi bi-shield-check"></i>
        <p class="mt-2 small">Không chứa gluten</p>
      </div>
      <div class="p-2">
        <i class="bi bi-recycle"></i>
        <p class="mt-2 small">Bao bì tái sử dụng</p>
      </div>
    </div>

  </div>
</main>

<!-- === FOOTER === -->
<footer>
  <div class="container">
    <div class="footer-content"></div>
    <div class="copyright">© 2025 Darling.</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>