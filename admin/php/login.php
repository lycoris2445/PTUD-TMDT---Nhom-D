<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="login-page">

  <div class="card login-card p-4">
    <div class="text-center mb-3">
      <div class="fw-bold fs-5" style="color: var(--darling-color);">Darling Admin</div>
      <div class="text-muted small">Đăng nhập quản trị</div>
    </div>

    <form>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" class="form-control" placeholder="admin@darling.com" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Mật khẩu</label>
        <input type="password" id="password" class="form-control" placeholder="••••••" required>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="remember">
          <label class="form-check-label" for="remember">Nhớ đăng nhập</label>
        </div>
        <a href="#" class="small">Quên mật khẩu?</a>
      </div>
      <button type="submit" class="btn btn-darling w-100">Đăng nhập</button>
    </form>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>