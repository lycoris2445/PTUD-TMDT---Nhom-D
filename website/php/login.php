<?php 
// PHP file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
                    <p class="subtitle">
                        Don’t have an account yet? <a href="register.php">Sign up now</a>
                    </p>

                    <form class="auth-form" action="process_login.php" method="POST">
                        <label for="email">E-mail</label>
                        <div class="input-group">
                            <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
                        </div>

                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" placeholder="@#%^" required>
                        </div>

                        <div class="checkbox-row">
                            <label>
                                <input type="checkbox" name="remember">
                                Remember me
                            </label>
                            <a href="#">Forgot password?</a>
                        </div>

                        <button type="submit" class="primary-btn">Log in</button>
                    </form>

                    <div class="separator">OR</div>

                    <a href="#" class="social-btn">
                        <i class="fab fa-google"></i>
                        Sign in with Google
                    </a>
                    <a href="#" class="social-btn">
                        <i class="fab fa-facebook"></i>
                        Sign in with Facebook
                    </a>
                </div>

                <div class="auth-promo-column">
                    <div class="promo-support">
                        <a href="#">Support</a>
                    </div>
                    
                    <div class="promo-content">
                        <h2>Multi-channel Super Sale</h2>
                        <p>Darling offers a wide range of products from premium to affordable, meeting all customer needs.</p>
                        <br>
                        <p>Darling deals – every purchase comes with a gift.</p>
                        <button>Learn more</button>
                    </div>

                    <div class="new-features">
                        <h3>Lowest Prices of the Year</h3>
                        <p>Total vouchers worth 50 million VND – applicable to selected products only.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>