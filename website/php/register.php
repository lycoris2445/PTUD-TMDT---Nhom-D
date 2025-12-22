<?php
// Load security helper
require_once __DIR__ . '/../../config/security_helper.php';

$error = "";
$success = "";

// Kết nối Database - sử dụng file cấu hình chung
try {
    $pdo = require __DIR__ . '/../../config/db_connect.php';
} catch (Exception $e) {
    die("Fail to connect to database: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields!";
    } elseif (!validate_email($email)) {
        $error = "Invalid email address!";
    } else {
        // Hash mật khẩu để bảo mật
        $password_hash = hash_password($password);
        
        try {
            // Bắt đầu Transaction
            $pdo->beginTransaction();

            // 1. Chèn vào bảng ACCOUNTS
            $sql_account = "INSERT INTO ACCOUNTS (full_name, email, password_hash, status) VALUES (?, ?, ?, 'active')";
            $stmt_account = $pdo->prepare($sql_account);
            $stmt_account->execute([$full_name, $email, $password_hash]);

            // Lấy ID của tài khoản vừa tạo
            $new_account_id = $pdo->lastInsertId();

            // 2. Chèn vào bảng ACCOUNT_ROLES với role_id = 3
            $sql_role = "INSERT INTO ACCOUNT_ROLES (account_id, role_id) VALUES (?, 3)";
            $stmt_role = $pdo->prepare($sql_role);
            $stmt_role->execute([$new_account_id]);

            // Xác nhận hoàn tất cả 2 lệnh insert
            $pdo->commit();

            $success = "Registration successful! <a href='login.php'>Login now</a>";
        } catch (Exception $e) {
            // Nếu có lỗi ở bất kỳ bước nào, hủy bỏ mọi thay đổi
            $pdo->rollBack();
            $error = "An error occurred during registration. Please try again.";
            // Bạn có thể ghi log lỗi ở đây: error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Darling</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dnhap_dki.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="auth-page-body">
    <main>
        <section class="auth-section">
            <div class="register-container-single">
                <div class="logo-auth">
                    <div class="logo-icon"></div>
                    Darling
                </div>
                
                <h1>Create Account</h1>
                
                <?php if($error): ?>
                    <div style="color: red; margin-bottom: 15px;"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div style="color: green; margin-bottom: 15px;"><?= $success ?></div>
                <?php endif; ?>

                <p class="subtitle">
                    Already have an account? <a href="login.php">Log in now</a>
                </p>

                <form class="auth-form" action="register.php" method="POST">
                    <label for="full_name">Full Name</label>
                    <div class="input-group">
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                    </div>

                    <label for="email">E-mail</label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
                    </div>

                    <label for="password">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>

                    <button type="submit" class="primary-btn">Sign up</button>

                    <div class="separator">OR</div>

                    <a href="#" class="social-btn">
                        <i class="fab fa-google"></i> Sign up with Google
                    </a>
                    <a href="#" class="social-btn">
                        <i class="fab fa-facebook"></i> Sign up with Facebook
                    </a>
                </form>
            </div>
        </section>
    </main>
</body>
</html>