<?php
/**
 * Security Helper Functions
 * Các hàm hỗ trợ bảo mật cho ứng dụng
 */

/**
 * Tạo CSRF Token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Làm sạch input
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate Email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate Password Strength
 * Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Mật khẩu phải có ít nhất 8 ký tự";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 chữ hoa";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 chữ thường";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 chữ số";
    }
    
    return $errors;
}

/**
 * Rate Limiting - Kiểm tra số lần đăng nhập thất bại
 */
function check_login_attempts($identifier) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'login_attempts_' . md5($identifier);
    $max_attempts = 5;
    $lockout_time = 900; // 15 phút
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'last_attempt' => 0
        ];
    }
    
    $attempts = &$_SESSION[$key];
    
    // Reset nếu đã hết thời gian khóa
    if (time() - $attempts['last_attempt'] > $lockout_time) {
        $attempts['count'] = 0;
    }
    
    // Kiểm tra số lần thử
    if ($attempts['count'] >= $max_attempts) {
        $remaining_time = $lockout_time - (time() - $attempts['last_attempt']);
        if ($remaining_time > 0) {
            return [
                'allowed' => false,
                'remaining_time' => ceil($remaining_time / 60)
            ];
        }
    }
    
    return ['allowed' => true];
}

/**
 * Ghi nhận lần đăng nhập thất bại
 */
function record_failed_login($identifier) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'login_attempts_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
    }
    
    $_SESSION[$key]['count']++;
    $_SESSION[$key]['last_attempt'] = time();
}

/**
 * Reset login attempts sau khi đăng nhập thành công
 */
function reset_login_attempts($identifier) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'login_attempts_' . md5($identifier);
    unset($_SESSION[$key]);
}

/**
 * Kiểm tra xem user có đăng nhập không
 */
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Kiểm tra xem admin có đăng nhập không
 */
function is_admin_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_id']);
}

/**
 * Require login - chuyển hướng nếu chưa đăng nhập
 */
function require_login($redirect = 'login.php') {
    if (!is_logged_in()) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Require admin - chuyển hướng nếu không phải admin
 */
function require_admin($redirect = 'login.php') {
    if (!is_admin_logged_in()) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Regenerate Session ID để tránh session fixation
 */
function regenerate_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
}

/**
 * Hash password với bcrypt
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}
