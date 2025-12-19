<?php
// PHP file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
                <p class="subtitle">
                    Already have an account? <a href="login.php">Log in now</a>
                </p>

                <form class="auth-form" action="process_register.php" method="POST">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" placeholder="Enter your name" required>
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
                        <i class="fab fa-google"></i>
                        Sign up with Google
                    </a>
                    <a href="#" class="social-btn">
                        <i class="fab fa-facebook"></i>
                        Sign up with Facebook
                    </a>
                </form>
            </div>
        </section>
    </main>
</body>
</html>