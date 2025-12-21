<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = require __DIR__ . '/../../config/db_connect.php'; // PDO
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB connect failed']);
    exit;
}

// Cloudinary helper của bạn (có uploadToCloudinary)
require_once __DIR__ . '/../../config/cloudinary.php';

function fail(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed');

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $name = trim((string)($_POST['name'] ?? ''));
        $spu = trim((string)($_POST['spu'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $basePrice = (float)($_POST['base_price'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'draft'));
        $description = trim((string)($_POST['description'] ?? ''));
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));
        $stockQty = (int)($_POST['stock_quantity'] ?? 0);
        if ($stockQty < 0) $stockQty = 0;

        if ($name === '' || $spu === '' || $categoryId <= 0) fail(422, 'Missing required fields');

        // upload file lên cloudinary nếu có
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $up = uploadToCloudinary($_FILES['image']['tmp_name'], 'darling/products');
            if (!empty($up['success'])) {
                $imageUrl = $up['url'];
            } else {
                fail(500, 'Cloudinary upload failed: ' . ($up['error'] ?? 'unknown'));
            }
        }

        $conn->beginTransaction();

        $stmt = $conn->prepare("
            INSERT INTO PRODUCTS (spu, category_id, name, description, base_price, status, image_url)
            VALUES (:spu, :cid, :name, :des, :price, :st, :img)
        ");
        $stmt->execute([
            ':spu' => $spu,
            ':cid' => $categoryId,
            ':name' => $name,
            ':des' => $description,
            ':price' => $basePrice,
            ':st' => $status,
            ':img' => ($imageUrl !== '' ? $imageUrl : null),
        ]);

        $productId = (int)$conn->lastInsertId();

        /**
         * Tạo biến thể mặc định (không làm attributes do thiếu thời gian)
         * sku_code: tạo theo SPU + '-DEFAULT'
         */
        $defaultSku = $spu . '-DEFAULT';

        // Nếu bảng PRODUCT_VARIANTS của bạn có thêm cột NOT NULL khác,
        // bạn cần bổ sung đúng theo schema của bạn.
        $stmtVar = $conn->prepare("
            INSERT INTO PRODUCT_VARIANTS (product_id, sku_code, attributes)
            VALUES (:pid, :sku, :attrs)
        ");
        $stmtVar->execute([
            ':pid' => $productId,
            ':sku' => $defaultSku,
            ':attrs' => null,
        ]);

        $variantId = (int)$conn->lastInsertId();

        /**
         * Tạo inventory cho biến thể mặc định
         */
        $stmtInv = $conn->prepare("
            INSERT INTO INVENTORY (product_variant_id, quantity, reserved_quantity)
            VALUES (:vid, :qty, 0)
        ");
        $stmtInv->execute([
            ':vid' => $variantId,
            ':qty' => $stockQty,
        ]);

        $conn->commit();

        echo json_encode(['ok' => true, 'id' => $productId]);
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) fail(422, 'Invalid id');

       $stmt = $conn->prepare("
            SELECT 
                p.id, p.spu, p.category_id, p.name, p.description, p.base_price, p.status, p.image_url,
                COALESCE(SUM(i.quantity), 0) AS stock_quantity
            FROM PRODUCTS p
            LEFT JOIN PRODUCT_VARIANTS pv ON pv.product_id = p.id
            LEFT JOIN INVENTORY i ON i.product_variant_id = pv.id
            WHERE p.id = :id
            GROUP BY p.id
        ");

        $stmt->execute([':id' => $id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) fail(404, 'Not found');

        echo json_encode(['ok' => true, 'product' => $p]);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) fail(422, 'Invalid id');

        $name = trim((string)($_POST['name'] ?? ''));
        $spu = trim((string)($_POST['spu'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $basePrice = (float)($_POST['base_price'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'draft'));
        $description = trim((string)($_POST['description'] ?? ''));
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));

        if ($name === '' || $spu === '' || $categoryId <= 0) fail(422, 'Missing required fields');

        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $up = uploadToCloudinary($_FILES['image']['tmp_name'], 'darling/products');
            if (!empty($up['success'])) {
                $imageUrl = $up['url'];
            } else {
                fail(500, 'Cloudinary upload failed: ' . ($up['error'] ?? 'unknown'));
            }
        }

        if ($imageUrl !== '') {
            $stmt = $conn->prepare("
                UPDATE PRODUCTS
                SET spu=:spu, category_id=:cid, name=:name, description=:des, base_price=:price, status=:st, image_url=:img
                WHERE id=:id
            ");
            $stmt->execute([
                ':spu' => $spu, ':cid' => $categoryId, ':name' => $name, ':des' => $description,
                ':price' => $basePrice, ':st' => $status, ':img' => $imageUrl, ':id' => $id
            ]);
        } else {
            $stmt = $conn->prepare("
                UPDATE PRODUCTS
                SET spu=:spu, category_id=:cid, name=:name, description=:des, base_price=:price, status=:st
                WHERE id=:id
            ");
            $stmt->execute([
                ':spu' => $spu, ':cid' => $categoryId, ':name' => $name, ':des' => $description,
                ':price' => $basePrice, ':st' => $status, ':id' => $id
            ]);
        }
        if ($stockQty >= 0) {
            // tìm 1 variant bất kỳ (ưu tiên variant nhỏ nhất) của product
            $stmtFindVar = $conn->prepare("SELECT id FROM PRODUCT_VARIANTS WHERE product_id = :pid ORDER BY id ASC LIMIT 1");
            $stmtFindVar->execute([':pid' => $id]);
            $vid = (int)($stmtFindVar->fetchColumn() ?: 0);

            if ($vid > 0) {
                // nếu đã có inventory thì update, chưa có thì insert
                $stmtFindInv = $conn->prepare("SELECT id FROM INVENTORY WHERE product_variant_id = :vid LIMIT 1");
                $stmtFindInv->execute([':vid' => $vid]);
                $invId = (int)($stmtFindInv->fetchColumn() ?: 0);

                if ($invId > 0) {
                    $stmtUpInv = $conn->prepare("UPDATE INVENTORY SET quantity = :qty WHERE id = :iid");
                    $stmtUpInv->execute([':qty' => $stockQty, ':iid' => $invId]);
                } else {
                    $stmtInsInv = $conn->prepare("
                        INSERT INTO INVENTORY (product_variant_id, quantity, reserved_quantity)
                        VALUES (:vid, :qty, 0)
                    ");
                    $stmtInsInv->execute([':vid' => $vid, ':qty' => $stockQty]);
                }
            }
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) fail(422, 'Invalid id');

        // FK cascade sẽ xoá variants/inventory theo schema bạn set
        $stmt = $conn->prepare("DELETE FROM PRODUCTS WHERE id=:id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['ok' => true]);
        exit;
    }

    fail(400, 'Unknown action');
} catch (Throwable $e) {
    fail(500, $e->getMessage());
}
