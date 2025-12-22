<?php
// 1. Khởi tạo session và biến thông báo
session_start();

// Load security helper
require_once __DIR__ . '/../../config/security_helper.php';

$error = "";

// Redirect nếu đã đăng nhập
if (is_logged_in()) {
    header("Location: home.php");
    exit;
}

// 2. Kết nối Database - sử dụng file cấu hình chung
try {
    $pdo = require __DIR__ . '/../../config/db_connect.php';
} catch (Exception $e) {
    die("Fail to connect database: " . $e->getMessage());
}

// 3. Xử lý khi người dùng nhấn nút Log in
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password!";
    } else {
        // Kiểm tra rate limiting
        $rate_limit = check_login_attempts($email);
        if (!$rate_limit['allowed']) {
            $error = "You have tried to log in too many times. Please try again after " . $rate_limit['remaining_time'] . " minutes.";
        } else {
            // Tìm tài khoản theo email
            $stmt = $pdo->prepare("SELECT id, full_name, password_hash, status FROM ACCOUNTS WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Kiểm tra trạng thái tài khoản
                if ($user['status'] === 'suspended') {
                    $error = "Your account has been suspended!";
                    record_failed_login($email);
                } 
                // Xác thực mật khẩu
                else if (verify_password($password, $user['password_hash'])) {
                
                // KIỂM TRA QUYỀN: Chỉ cho phép role_id = 3 (User) đăng nhập
                $roleStmt = $pdo->prepare("
                    SELECT role_id 
                    FROM ACCOUNT_ROLES 
                    WHERE account_id = ? AND role_id = 3
                ");
                $roleStmt->execute([$user['id']]);
                $isUser = $roleStmt->fetch();

                if (!$isUser) {
                    // Nếu không tìm thấy role_id = 3, đây là tài khoản Admin/Staff
                    $error = "Tài khoản Admin không được phép đăng nhập tại đây!";
                    record_failed_login($email);
                } else {
                    // ĐĂNG NHẬP THÀNH CÔNG CHO USER
                    reset_login_attempts($email);
                    regenerate_session();
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['role_id'] = 3; // Lưu role vào session để kiểm tra ở các trang khác

                    $updateStmt = $pdo->prepare("UPDATE ACCOUNTS SET last_login_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);

                    header("Location: home.php"); 
                    exit;
                } 
                } else {
                    $error = "Incorrect password!";
                    record_failed_login($email);
                }
            } else {
                $error = "Email does not exist in the system!";
                record_failed_login($email);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Darling</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dnhap_dki.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="auth-page-body">
    <main>
        <section class="auth-section">
            <div class="auth-container">
                <div class="auth-form-column">
                    <div class="logo-auth">
                        <div class="logo-icon"></div>
                        Darling
                    </div>
                    
                    <h1>Login</h1>
                    
                    <?php if(!empty($error)): ?>
                        <div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <p class="subtitle">
                        Don’t have an account yet? <a href="register.php">Sign up now</a>
                    </p>

                    <form class="auth-form" action="login.php" method="POST">
                        <label for="email">E-mail</label>
                        <div class="input-group">
                            <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
                        </div>

                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" placeholder="Enter password" required>
                        </div>

                        <button type="submit" class="primary-btn">Log in</button>
                    </form>

                    <div class="separator">OR</div>

                    <a href="#" class="social-btn"><i class="fab fa-google"></i> Sign in with Google</a>
                    <a href="#" class="social-btn"><i class="fab fa-facebook"></i> Sign in with Facebook</a>
                </div>

                <div class="auth-promo-column">
                    <div class="promo-content">
                        <h2>Administration Login page</h2>
                        <p>Are you an admin? Log in here.</p>
                        <a href="../../admin/php/admin-login.php" class="admin-btn-link">
                            <button type="button">Log in for admin</button>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>