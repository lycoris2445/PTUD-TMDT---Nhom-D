<?php
// 1. Khởi tạo session và biến thông báo
session_start();
$error = "";

// 2. Kết nối Database
$host = 'localhost';
$db   = 'Darling_cosmetics';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// 3. Xử lý khi người dùng nhấn nút Log in
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập email và mật khẩu!";
    } else {
        // Tìm tài khoản theo email
        $stmt = $pdo->prepare("SELECT id, full_name, password_hash, status FROM ACCOUNTS WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Kiểm tra trạng thái tài khoản
            if ($user['status'] === 'suspended') {
                $error = "Tài khoản của bạn đã bị khóa!";
            } 
            // Xác thực mật khẩu
            else if (password_verify($password, $user['password_hash'])) {
                // Đăng nhập thành công: Lưu thông tin vào Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // Cập nhật thời gian đăng nhập cuối cùng
                $updateStmt = $pdo->prepare("UPDATE ACCOUNTS SET last_login_at = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                // Chuyển hướng về trang chủ hoặc dashboard
                header("Location: index.php"); 
                exit;
            } else {
                $error = "Mật khẩu không chính xác!";
            }
        } else {
            $error = "Email không tồn tại trong hệ thống!";
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
                        <h2>Multi-channel Super Sale</h2>
                        <p>Darling deals – every purchase comes with a gift.</p>
                        <button>Learn more</button>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>