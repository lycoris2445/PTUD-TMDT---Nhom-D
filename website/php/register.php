<?php
$error = "";
$success = "";

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Kiểm tra email đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT id FROM ACCOUNTS WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $error = "Email này đã được đăng ký!";
    } else {
        // Hash mật khẩu để bảo mật
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Chèn vào database
        $sql = "INSERT INTO ACCOUNTS (full_name, email, password_hash, status) VALUES (?, ?, ?, 'active')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$full_name, $email, $password_hash])) {
            $success = "Đăng ký thành công! <a href='login.php'>Đăng nhập ngay</a>";
        } else {
            $error = "Có lỗi xảy ra, vui lòng thử lại.";
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