<?php
/**
 * Script để tạo password hash cho testing
 * Chạy: php generate_password_hash.php
 */

function generate_hash($password) {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    return $hash;
}

echo "=== PASSWORD HASH GENERATOR ===\n\n";

// Password mặc định cho tất cả tài khoản trong database
$default_password = 'Darling@2024';
echo "PASSWORD MẶC ĐỊNH CHO TẤT CẢ TÀI KHOẢN: $default_password\n";
echo str_repeat("=", 80) . "\n";

$hash = generate_hash($default_password);
echo "\nHash được tạo:\n";
echo "$hash\n\n";

// Verify để chắc chắn
$verify = password_verify($default_password, $hash);
echo "Verification: " . ($verify ? "✓ SUCCESS - Hash hợp lệ" : "✗ FAILED") . "\n";
echo str_repeat("=", 80) . "\n\n";

echo "SQL COMMAND để cập nhật tất cả tài khoản:\n";
echo "UPDATE ACCOUNTS SET password_hash = '$hash' WHERE id BETWEEN 1 AND 20;\n\n";

echo "\n=== DANH SÁCH TÀI KHOẢN TRONG DATABASE ===\n";
echo str_repeat("-", 80) . "\n";

$accounts = [
    'ADMIN ACCOUNTS (role: admin)' => [
        'superadmin01@darling.com' => 'Sophia Bennett',
        'superadmin02@darling.com' => 'Ethan Clarke',
        'superadmin03@darling.com' => 'Olivia Hayes',
        'superadmin04@darling.com' => 'Liam Turner',
        'superadmin05@darling.com' => 'Ava Mitchell (SUSPENDED)',
    ],
    'MANAGER ACCOUNTS (role: manager)' => [
        'ops01@darling.com' => 'Mason Reed',
        'ops02@darling.com' => 'Amelia Brooks',
        'ops03@darling.com' => 'Noah Foster',
        'ops04@darling.com' => 'Charlotte Price (SUSPENDED)',
        'ops05@darling.com' => 'James Collins',
    ],
    'CUSTOMER ACCOUNTS (role: customer)' => [
        'user01@gmail.com' => 'Emma Johnson',
        'user02@gmail.com' => 'William Anderson',
        'user03@gmail.com' => 'Mia Martinez',
        'user04@gmail.com' => 'Benjamin Taylor',
        'user05@gmail.com' => 'Harper Wilson (SUSPENDED)',
        'user06@gmail.com' => 'Daniel Thomas',
        'user07@gmail.com' => 'Evelyn Moore',
        'user08@gmail.com' => 'Logan Jackson',
        'user09@gmail.com' => 'Abigail White',
        'user10@gmail.com' => 'Henry Harris',
    ]
];

foreach ($accounts as $group => $users) {
    echo "\n$group:\n";
    foreach ($users as $email => $name) {
        echo "  - $email ($name)\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CÁCH SỬ DỤNG:\n";
echo "1. Chạy file update_password_hash.sql trong database\n";
echo "2. Hoặc copy SQL command ở trên và chạy trong phpMyAdmin/MySQL client\n";
echo "3. Đăng nhập với bất kỳ email nào ở trên, password: $default_password\n";
echo str_repeat("=", 80) . "\n\n";

// Test các password khác
echo "\n=== TEST HASH CÁC PASSWORD KHÁC ===\n";
$test_passwords = [
    'Admin@123',
    'Manager@123',
    'Customer@123',
];

foreach ($test_passwords as $pwd) {
    $h = generate_hash($pwd);
    $v = password_verify($pwd, $h);
    echo "\nPassword: $pwd\n";
    echo "Hash: $h\n";
    echo "Verify: " . ($v ? "✓" : "✗") . "\n";
}

// Chức năng nhập password tùy chỉnh
echo "\n" . str_repeat("=", 80) . "\n";
echo "=== CUSTOM PASSWORD GENERATOR ===\n";
if (php_sapi_name() === 'cli') {
    echo "Nhập password để hash (hoặc Enter để bỏ qua): ";
    $custom_password = trim(fgets(STDIN));
    
    if (!empty($custom_password)) {
        $custom_hash = generate_hash($custom_password);
        echo "\nPassword: $custom_password\n";
        echo "Hash: $custom_hash\n";
        
        $verify = password_verify($custom_password, $custom_hash);
        echo "Verification: " . ($verify ? "✓ SUCCESS" : "✗ FAILED") . "\n\n";
        
        echo "SQL Command:\n";
        echo "UPDATE ACCOUNTS SET password_hash = '$custom_hash' WHERE email = 'YOUR_EMAIL_HERE';\n";
    }
}

echo "\n✅ XONG! Sử dụng email + password: Darling@2024 để đăng nhập.\n";

