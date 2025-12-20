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

        echo json_encode(['ok' => true, 'id' => (int)$conn->lastInsertId()]);
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) fail(422, 'Invalid id');

        $stmt = $conn->prepare("SELECT id, spu, category_id, name, description, base_price, status, image_url FROM PRODUCTS WHERE id=:id");
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
