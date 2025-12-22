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
        // Validate password strength - comment out nếu muốn đơn giản hơn
        $password_errors = validate_password_strength($password);
        if (!empty($password_errors)) {
            $error = implode(". ", $password_errors);
        } else {

    // Kiểm tra email đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT id FROM ACCOUNTS WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
            $error = "This Email has already been registered!";
    } else {
            // Hash mật khẩu để bảo mật - sử dụng bcrypt với cost 12
            $password_hash = hash_password($password);
        
        // Chèn vào database
        $sql = "INSERT INTO ACCOUNTS (full_name, email, password_hash, status) VALUES (?, ?, ?, 'active')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$full_name, $email, $password_hash])) {
                $success = "Registration successful! <a href='login.php'>Login now</a>";
        } else {
                $error = "An error occurred, please try again.";
            }
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