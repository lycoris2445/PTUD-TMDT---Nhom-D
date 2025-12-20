<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB connect failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$parentRaw = $_POST['parent_id'] ?? '';
$parentId = ($parentRaw === '' ? null : (int)$parentRaw);

if ($name === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Category name required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO CATEGORIES (name, parent_id) VALUES (:name, :pid)");
$stmt->execute([':name' => $name, ':pid' => $parentId]);

echo json_encode(['ok' => true, 'id' => (int)$conn->lastInsertId()]);
