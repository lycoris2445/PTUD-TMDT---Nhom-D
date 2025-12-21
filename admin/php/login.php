<?php
// Khởi tạo session
session_start();

// Load security helper
require_once __DIR__ . '/../../config/security_helper.php';

// Nếu đã đăng nhập, chuyển đến dashboard
if (is_admin_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

// Kết nối Database
try {
    $pdo = require __DIR__ . '/../../config/db_connect.php';
} catch (Exception $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// Xử lý đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ email và mật khẩu!";
    } else {
        // Kiểm tra rate limiting
        $rate_limit = check_login_attempts($email);
        if (!$rate_limit['allowed']) {
            $error = "Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau " . $rate_limit['remaining_time'] . " phút.";
        } else {
            // Kiểm tra tài khoản và lấy thông tin role
            $stmt = $pdo->prepare("
                SELECT a.id, a.full_name, a.email, a.password_hash, a.status, r.role_name
                FROM ACCOUNTS a
                LEFT JOIN ACCOUNT_ROLES ar ON a.id = ar.account_id
                LEFT JOIN ROLES r ON ar.role_id = r.id
                WHERE a.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Kiểm tra trạng thái tài khoản
                if ($user['status'] === 'suspended') {
                    $error = "Tài khoản của bạn đã bị khóa!";
                    record_failed_login($email);
                }
                // Kiểm tra quyền admin
                elseif (!in_array($user['role_name'], ['admin', 'manager'])) {
                    $error = "Bạn không có quyền truy cập trang quản trị!";
                    record_failed_login($email);
                }
                // Xác thực mật khẩu
                elseif (verify_password($password, $user['password_hash'])) {
                    // Đăng nhập thành công
                    reset_login_attempts($email);
                    regenerate_session();
                    
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['full_name'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_role'] = $user['role_name'];
                    
                    // Cập nhật thời gian đăng nhập
                    $updateStmt = $pdo->prepare("UPDATE ACCOUNTS SET last_login_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);

                    // Xử lý "Nhớ đăng nhập" - tạo cookie kéo dài 30 ngày
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('admin_remember', $token, time() + (86400 * 30), "/", "", true, true);
                        // Lưu token vào database nếu cần
                    }

                    // Chuyển hướng đến dashboard
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = "Mật khẩu không chính xác!";
                    record_failed_login($email);
                }
            } else {
                $error = "Email không tồn tại trong hệ thống!";
                record_failed_login($email);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin-main.css">
</head>
<body class="login-page">

  <div class="card login-card p-4">
    <div class="text-center mb-3">
      <div class="fw-bold fs-5" style="color: var(--darling-color);">Darling Admin</div>
      <div class="text-muted small">Đăng nhập quản trị</div>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="admin@darling.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••" required>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
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