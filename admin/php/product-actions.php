<?php
declare(strict_types=1);

session_start();

// 1. Kiểm tra quyền admin - CHỈ operation_staff
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || 
    !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'operation_staff') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Access denied - operation_staff only']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = require __DIR__ . '/../../config/db_connect.php'; // PDO
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB connect failed']);
    exit;
}

require_once __DIR__ . '/../../config/cloudinary.php';

function fail(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed');

$action = $_POST['action'] ?? '';

try {
    // --- HÀNH ĐỘNG: LẤY DỮ LIỆU (GET) ---
    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) fail(422, 'Invalid id');

        $stmt = $conn->prepare("SELECT * FROM PRODUCTS WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) fail(404, 'Not found');

        // Lấy biến thể kèm số lượng kho từ bảng INVENTORY
        $stmtVar = $conn->prepare("
            SELECT pv.*, COALESCE(i.quantity, 0) as quantity 
            FROM PRODUCT_VARIANTS pv
            LEFT JOIN INVENTORY i ON pv.id = i.product_variant_id
            WHERE pv.product_id = :pid
        ");
        $stmtVar->execute([':pid' => $id]);
        $variants = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'product' => $p, 'variants' => $variants]);
        exit;
    }

    // --- HÀNH ĐỘNG: THÊM MỚI (CREATE) HOẶC CẬP NHẬT (UPDATE) ---
    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['id'] ?? 0); // Chỉ dùng cho Update
        $name = trim((string)($_POST['name'] ?? ''));
        $spu = trim((string)($_POST['spu'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $basePrice = (float)($_POST['base_price'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'draft'));
        $description = trim((string)($_POST['description'] ?? ''));
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));

        // Dữ liệu mảng biến thể
        $variantIds = $_POST['variant_id'] ?? [];
        $variantSkus = $_POST['variant_sku'] ?? [];
        $variantPrices = $_POST['variant_price'] ?? [];
        $variantStocks = $_POST['variant_stock'] ?? []; 
        $variantImages = $_POST['variant_image'] ?? [];

        if ($name === '' || $spu === '' || $categoryId <= 0) fail(422, 'Missing required fields');

        // Xử lý upload ảnh sản phẩm (nếu có)
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $up = uploadToCloudinary($_FILES['image']['tmp_name'], 'darling/products');
            if (!empty($up['success'])) {
                $imageUrl = $up['url'];
            } else {
                fail(500, 'Cloudinary upload failed: ' . ($up['error'] ?? 'unknown'));
            }
        }

        $conn->beginTransaction();

        if ($action === 'create') {
            // INSERT PRODUCT
            $stmt = $conn->prepare("
                INSERT INTO PRODUCTS (spu, category_id, name, description, base_price, status, image_url)
                VALUES (:spu, :cid, :name, :des, :price, :st, :img)
            ");
            $stmt->execute([
                ':spu' => $spu, ':cid' => $categoryId, ':name' => $name, ':des' => $description,
                ':price' => $basePrice, ':st' => $status, ':img' => ($imageUrl !== '' ? $imageUrl : null)
            ]);
            $productId = (int)$conn->lastInsertId();
        } else {
            // UPDATE PRODUCT
            $productId = $id;
            $stmt = $conn->prepare("
                UPDATE PRODUCTS 
                SET spu=:spu, category_id=:cid, name=:name, description=:des, base_price=:price, status=:st, image_url=:img
                WHERE id=:id
            ");
            $stmt->execute([
                ':spu' => $spu, ':cid' => $categoryId, ':name' => $name, ':des' => $description,
                ':price' => $basePrice, ':st' => $status, ':img' => $imageUrl, ':id' => $productId
            ]);
        }

        // XỬ LÝ BIẾN THỂ VÀ KHO (Dùng cho cả Create và Update)
        foreach ($variantSkus as $index => $sku) {
            $vId    = !empty($variantIds[$index]) ? (int)$variantIds[$index] : null;
            $vSku   = trim($sku);
            $vPrice = (float)($variantPrices[$index] ?? 0);
            $vQty   = (int)($variantStocks[$index] ?? 0);
            $vImg   = trim($variantImages[$index] ?? '');

            if ($vId) {
                // Cập nhật biến thể cũ
                $stmt = $conn->prepare("UPDATE PRODUCT_VARIANTS SET sku_code = ?, price = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$vSku, $vPrice, $vImg, $vId]);
                $currentVId = $vId;
            } else {
                // Thêm biến thể mới
                $stmt = $conn->prepare("INSERT INTO PRODUCT_VARIANTS (product_id, sku_code, price, image_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$productId, $vSku, $vPrice, $vImg]);
                $currentVId = (int)$conn->lastInsertId();
            }

            // Cập nhật bảng INVENTORY (Dùng UPSERT)
            $stmtInv = $conn->prepare("
                INSERT INTO INVENTORY (product_variant_id, quantity, reserved_quantity)
                VALUES (:vid, :qty, 0)
                ON DUPLICATE KEY UPDATE quantity = :qty_update
            ");
            $stmtInv->execute([
                ':vid' => $currentVId,
                ':qty' => $vQty,
                ':qty_update' => $vQty
            ]);
        }

        $conn->commit();
        echo json_encode(['ok' => true, 'id' => $productId]);
        exit;
    }

    // --- HÀNH ĐỘNG: XÓA (DELETE) ---
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) fail(422, 'Invalid id');

        $stmt = $conn->prepare("DELETE FROM PRODUCTS WHERE id=:id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['ok' => true]);
        exit;
    }

    fail(400, 'Unknown action');

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    fail(500, $e->getMessage());
}