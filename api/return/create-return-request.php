<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../config/cloudinary.php';

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Login required']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Parse JSON or form data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        // Handle multipart form data for file uploads
        $input = $_POST;
    }
    
    $orderId = (int)($input['order_id'] ?? 0);
    $reason = trim($input['reason'] ?? '');
    $returnItems = $input['return_items'] ?? []; // Array of {order_item_id, quantity}
    
    if (!$orderId) {
        throw new Exception('Order ID is required');
    }
    
    if (empty($reason)) {
        throw new Exception('Return reason is required');
    }
    
    if (empty($returnItems) || !is_array($returnItems)) {
        throw new Exception('At least one item must be selected for return');
    }
    
    // Get database connection
    $pdo = require __DIR__ . '/../../config/db_connect.php';
    
    // Verify order belongs to user and is eligible for return
    $stmt = $pdo->prepare("
        SELECT o.*, p.status as payment_status 
        FROM ORDERS o
        LEFT JOIN PAYMENT p ON o.id = p.order_id
        WHERE o.id = ? AND o.account_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(403);
        throw new Exception('Order not found or you do not have permission');
    }
    
    // Check if order is eligible for return
    // Can only return orders that are delivered or completed
    if (!in_array($order['status'], ['delivered', 'completed'])) {
        throw new Exception('Order must be delivered or completed before requesting a return. Current status: ' . $order['status']);
    }
    
    // Check if return request already exists
    $stmt = $pdo->prepare("SELECT id FROM RETURNS WHERE order_id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    if ($stmt->fetch()) {
        throw new Exception('A return request already exists for this order');
    }
    
    // Validate return items exist in order
    $orderItemIds = array_column($returnItems, 'order_item_id');
    $placeholders = str_repeat('?,', count($orderItemIds) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT id, quantity, price_at_purchase
        FROM ORDER_ITEMS 
        WHERE order_id = ? AND id IN ($placeholders)
    ");
    $stmt->execute(array_merge([$orderId], $orderItemIds));
    $validItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($validItems) !== count($orderItemIds)) {
        throw new Exception('Some items are not valid for this order');
    }
    
    // Validate quantities
    $itemsMap = [];
    foreach ($validItems as $item) {
        $itemsMap[$item['id']] = $item;
    }
    
    $totalRefundAmount = 0;
    foreach ($returnItems as $returnItem) {
        $itemId = (int)$returnItem['order_item_id'];
        $quantity = (int)$returnItem['quantity'];
        
        if (!isset($itemsMap[$itemId])) {
            throw new Exception("Invalid item ID: $itemId");
        }
        
        if ($quantity <= 0 || $quantity > $itemsMap[$itemId]['quantity']) {
            throw new Exception("Invalid quantity for item ID $itemId");
        }
        
        // Calculate refund amount for this item
        $totalRefundAmount += $itemsMap[$itemId]['price_at_purchase'] * $quantity;
    }
    
    // Handle proof images upload to Cloudinary
    $proofImagesUrls = [];
    
    if (!empty($_FILES['proof_images'])) {
        $files = $_FILES['proof_images'];
        
        // Handle multiple files
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$i];
                    $originalName = $files['name'][$i];
                    
                    // Upload to Cloudinary
                    try {
                        $result = \Cloudinary\Uploader::upload($tmpName, [
                            'folder' => 'returns/proof_images',
                            'public_id' => 'return_' . $orderId . '_' . time() . '_' . $i,
                            'resource_type' => 'image'
                        ]);
                        
                        $proofImagesUrls[] = $result['secure_url'];
                    } catch (Exception $e) {
                        error_log('[CLOUDINARY UPLOAD ERROR] ' . $e->getMessage());
                        // Continue even if one image fails
                    }
                }
            }
        } else {
            // Single file
            if ($files['error'] === UPLOAD_ERR_OK) {
                try {
                    $result = \Cloudinary\Uploader::upload($files['tmp_name'], [
                        'folder' => 'returns/proof_images',
                        'public_id' => 'return_' . $orderId . '_' . time(),
                        'resource_type' => 'image'
                    ]);
                    
                    $proofImagesUrls[] = $result['secure_url'];
                } catch (Exception $e) {
                    error_log('[CLOUDINARY UPLOAD ERROR] ' . $e->getMessage());
                }
            }
        }
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert return record
    $stmt = $pdo->prepare("
        INSERT INTO RETURNS (
            order_id,
            account_id,
            reason,
            status,
            proof_images,
            refund_amount,
            created_at
        ) VALUES (?, ?, ?, 'request_return', ?, ?, NOW())
    ");
    
    $stmt->execute([
        $orderId,
        $_SESSION['user_id'],
        $reason,
        json_encode($proofImagesUrls),
        $totalRefundAmount
    ]);
    
    $returnId = $pdo->lastInsertId();
    
    // Insert return items
    $stmt = $pdo->prepare("
        INSERT INTO RETURN_ITEMS (return_id, order_item_id, quantity)
        VALUES (?, ?, ?)
    ");
    
    foreach ($returnItems as $returnItem) {
        $stmt->execute([
            $returnId,
            (int)$returnItem['order_item_id'],
            (int)$returnItem['quantity']
        ]);
    }
    
    // Update order status to indicate return requested
    // We don't change the main status, but could add a flag or note
    $stmt = $pdo->prepare("
        INSERT INTO ORDER_HISTORY (order_id, previous_status, new_status, note, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $orderId,
        $order['status'],
        $order['status'],
        'Return request submitted by customer. Return ID: ' . $returnId
    ]);
    
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'return_id' => $returnId,
        'message' => 'Return request submitted successfully. Our team will review it shortly.',
        'proof_images_count' => count($proofImagesUrls)
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('[RETURN REQUEST ERROR] ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
