<?php
session_start();
$error = "";

// 1. Kết nối Database
try {
    $pdo = require __DIR__ . '/../../config/db_connect.php';
} catch (\PDOException $e) {
    die("Lỗi kết nối database: " . htmlspecialchars($e->getMessage()));
}

// 2. Nếu đã đăng nhập, chuyển đến dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: dashboard.php");
    exit;
}

// 3. Xử lý đăng nhập Admin
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        /* SỬ DỤNG JOIN ĐỂ LẤY ROLE TỪ BẢNG ROLES THÔNG QUA ACCOUNT_ROLES
        */
        $query = "SELECT a.id, a.full_name, a.password_hash, a.status, r.name as role_name 
                  FROM ACCOUNTS a
                  JOIN ACCOUNT_ROLES ar ON a.id = ar.account_id
                  JOIN ROLES r ON ar.role_id = r.id
                  WHERE a.email = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Kiểm tra mật khẩu (Sử dụng password_verify cho các hash $2b$10$...)
            if (password_verify($password, $user['password_hash'])) {
                
                // KIỂM TRA PHÂN QUYỀN: Chỉ cho phép super_admin hoặc operation_staff
                $allowed_roles = ['super_admin', 'operation_staff'];
                
                if (!in_array($user['role_name'], $allowed_roles)) {
                    $error = "Truy cập bị từ chối! Tài khoản này không có quyền quản trị.";
                } 
                // Kiểm tra trạng thái tài khoản
                else if ($user['status'] === 'suspended') {
                    $error = "Tài khoản quản trị này đã bị khóa (Suspended)!";
                } 
                else {
                    // ĐĂNG NHẬP THÀNH CÔNG
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['full_name'];
                    $_SESSION['admin_role'] = $user['role_name'];
                    $_SESSION['is_admin'] = true;
                    
                    // Cập nhật thời gian đăng nhập cuối cùng
                    $updateStmt = $pdo->prepare("UPDATE ACCOUNTS SET last_login_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);

                    header("Location: dashboard.php"); 
                    exit;
                }
            } else {
                $error = "Mật khẩu quản trị không chính xác!";
            }
        } else {
            $error = "Email quản trị không tồn tại!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Darling Cosmetics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-login.css">
</head>
<body>

<div class="login-container">
    <div class="logo">
        <i class="fas fa-crown"></i>
    </div>

    <?php if(!empty($error)): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="login-form">
        <form action="admin-login.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email Quản Trị</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autocomplete="email"
                    placeholder="admin@darling.com"
                >
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Nhập mật khẩu"
                >
            </div>

            <div class="remember-me">
                <input 
                    type="checkbox" 
                    id="remember" 
                    name="remember"
                >
                <label for="remember">Ghi nhớ email của tôi</label>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                Đăng nhập
            </button>
        </form>
    </div>

    <a href="../../website/php/login.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        Quay lại trang đăng nhập người dùng
    </a>
</div>

<script>
// LocalStorage key
const REMEMBER_EMAIL_KEY = 'admin_remember_email';

// Load saved email khi trang load
window.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const rememberCheckbox = document.getElementById('remember');
    const passwordInput = document.getElementById('password');
    
    // Lấy email đã lưu từ localStorage
    const savedEmail = localStorage.getItem(REMEMBER_EMAIL_KEY);
    
    if (savedEmail) {
        emailInput.value = savedEmail;
        rememberCheckbox.checked = true;
        // Focus vào password nếu email đã có
        passwordInput.focus();
    } else {
        // Focus vào email nếu chưa có
        emailInput.focus();
    }
});

// Xử lý submit form
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const emailInput = document.getElementById('email');
    const rememberCheckbox = document.getElementById('remember');
    const submitBtn = document.getElementById('submitBtn');
    
    // Lưu hoặc xóa email từ localStorage
    if (rememberCheckbox.checked) {
        localStorage.setItem(REMEMBER_EMAIL_KEY, emailInput.value);
    } else {
        localStorage.removeItem(REMEMBER_EMAIL_KEY);
    }
    
    // Thêm loading state
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.textContent = 'Đang xử lý...';
});

// Xử lý khi checkbox remember thay đổi
document.getElementById('remember').addEventListener('change', function() {
    if (!this.checked) {
        localStorage.removeItem(REMEMBER_EMAIL_KEY);
    }
});
</script>

</body>
</html>