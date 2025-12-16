<?php
/**
 * Cloudinary Configuration & Helper Functions
 * Sử dụng Cloudinary PHP SDK v3
 */

// Load Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

// ============================================
// LOAD .ENV FILE
// ============================================
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// ============================================
// CLOUDINARY CREDENTIALS FROM .ENV
// ============================================
define('CLOUDINARY_CLOUD_NAME', $_ENV['CLOUDINARY_CLOUD_NAME'] ?? 'YOUR_CLOUD_NAME');
define('CLOUDINARY_API_KEY', $_ENV['CLOUDINARY_API_KEY'] ?? 'YOUR_API_KEY');
define('CLOUDINARY_API_SECRET', $_ENV['CLOUDINARY_API_SECRET'] ?? 'YOUR_API_SECRET');

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
 * Lấy instance Cloudinary SDK
 */
function getCloudinary() {
    static $cloudinary = null;
    if ($cloudinary === null) {
        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => CLOUDINARY_CLOUD_NAME,
                'api_key'    => CLOUDINARY_API_KEY,
                'api_secret' => CLOUDINARY_API_SECRET,
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }
    return $cloudinary;
}

/**
 * Upload ảnh lên Cloudinary bằng SDK
 * 
 * @param string $filePath Đường dẫn file tạm từ $_FILES['file']['tmp_name']
 * @param string $folder Thư mục trên Cloudinary (vd: 'products')
 * @return array ['success' => bool, 'url' => string, 'public_id' => string, 'error' => string]
 */
function uploadToCloudinary($filePath, $folder = 'darling/products') {
    try {
        $cloudinary = getCloudinary();
        $result = $cloudinary->uploadApi()->upload($filePath, [
            'folder' => $folder,
            'resource_type' => 'image'
        ]);
        
        return [
            'success' => true,
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
            'width' => $result['width'] ?? 0,
            'height' => $result['height'] ?? 0
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Xóa ảnh trên Cloudinary
 * 
 * @param string $publicId Public ID của ảnh cần xóa
 * @return array ['success' => bool, 'error' => string]
 */
function deleteFromCloudinary($publicId) {
    try {
        $cloudinary = getCloudinary();
        $result = $cloudinary->uploadApi()->destroy($publicId);
        
        if ($result['result'] === 'ok') {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Delete failed'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Tạo URL ảnh với transformation (resize, crop, etc.)
 * 
 * @param string $publicId Public ID của ảnh
 * @param array $options ['width' => 300, 'height' => 300, 'crop' => 'fill']
 * @return string URL đã transform
 */
function cloudinaryTransform($publicId, $options = []) {
    try {
        $cloudinary = getCloudinary();
        $transformation = [];
        
        if (!empty($options['width'])) {
            $transformation['width'] = $options['width'];
        }
        if (!empty($options['height'])) {
            $transformation['height'] = $options['height'];
        }
        if (!empty($options['crop'])) {
            $transformation['crop'] = $options['crop'];
        }
        if (!empty($options['quality'])) {
            $transformation['quality'] = $options['quality'];
        }
        
        return (string) $cloudinary->image($publicId)->toUrl($transformation);
    } catch (Exception $e) {
        return '';
    }
}
?>
