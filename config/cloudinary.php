<?php
/**
 * Cloudinary Configuration & Helper Functions
 * 
 * Cách lấy thông tin:
 * 1. Đăng ký tài khoản tại https://cloudinary.com
 * 2. Vào Dashboard để lấy Cloud Name, API Key, API Secret
 * 3. Thay thế các giá trị bên dưới
 */

// ============================================
// CLOUDINARY CREDENTIALS - THAY ĐỔI Ở ĐÂY
// ============================================
define('CLOUDINARY_CLOUD_NAME', 'YOUR_CLOUD_NAME');
define('CLOUDINARY_API_KEY', 'YOUR_API_KEY');
define('CLOUDINARY_API_SECRET', 'YOUR_API_SECRET');
define('CLOUDINARY_UPLOAD_PRESET', 'darling_products'); // Unsigned upload preset (tạo trong Settings > Upload)

// ============================================
// DATABASE CONNECTION
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cosmetics_ecommerce');

/**
 * Kết nối database
 */
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Kết nối database thất bại: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

/**
 * Upload ảnh lên Cloudinary bằng cURL (không cần SDK)
 * 
 * @param string $filePath Đường dẫn file tạm từ $_FILES['file']['tmp_name']
 * @param string $folder Thư mục trên Cloudinary (vd: 'products')
 * @return array ['success' => bool, 'url' => string, 'public_id' => string, 'error' => string]
 */
function uploadToCloudinary($filePath, $folder = 'products') {
    $timestamp = time();
    
    // Tạo signature cho signed upload
    $params = [
        'folder' => $folder,
        'timestamp' => $timestamp
    ];
    ksort($params);
    
    $signatureString = '';
    foreach ($params as $key => $value) {
        $signatureString .= $key . '=' . $value . '&';
    }
    $signatureString = rtrim($signatureString, '&') . CLOUDINARY_API_SECRET;
    $signature = sha1($signatureString);
    
    // Chuẩn bị request
    $uploadUrl = 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload';
    
    $postFields = [
        'file' => new CURLFile($filePath),
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder' => $folder
    ];
    
    // Gửi request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['secure_url'])) {
        return [
            'success' => true,
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
            'width' => $result['width'] ?? 0,
            'height' => $result['height'] ?? 0
        ];
    }
    
    return [
        'success' => false,
        'error' => $result['error']['message'] ?? 'Upload thất bại'
    ];
}

/**
 * Xóa ảnh trên Cloudinary
 * 
 * @param string $publicId Public ID của ảnh cần xóa
 * @return array ['success' => bool, 'error' => string]
 */
function deleteFromCloudinary($publicId) {
    $timestamp = time();
    
    $signatureString = 'public_id=' . $publicId . '&timestamp=' . $timestamp . CLOUDINARY_API_SECRET;
    $signature = sha1($signatureString);
    
    $destroyUrl = 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/destroy';
    
    $postFields = [
        'public_id' => $publicId,
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $destroyUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['result']) && $result['result'] === 'ok') {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => $result['error']['message'] ?? 'Xóa thất bại'];
}

/**
 * Tạo URL ảnh với transformation (resize, crop, etc.)
 * 
 * @param string $url URL gốc từ Cloudinary
 * @param array $options ['width' => 300, 'height' => 300, 'crop' => 'fill']
 * @return string URL đã transform
 */
function cloudinaryTransform($url, $options = []) {
    if (empty($url) || strpos($url, 'cloudinary.com') === false) {
        return $url;
    }
    
    $transforms = [];
    
    if (!empty($options['width'])) {
        $transforms[] = 'w_' . $options['width'];
    }
    if (!empty($options['height'])) {
        $transforms[] = 'h_' . $options['height'];
    }
    if (!empty($options['crop'])) {
        $transforms[] = 'c_' . $options['crop'];
    }
    if (!empty($options['quality'])) {
        $transforms[] = 'q_' . $options['quality'];
    }
    
    if (empty($transforms)) {
        return $url;
    }
    
    $transformString = implode(',', $transforms);
    
    // Insert transformation vào URL
    return preg_replace(
        '/(\/upload\/)/',
        '/upload/' . $transformString . '/',
        $url
    );
}
?>
