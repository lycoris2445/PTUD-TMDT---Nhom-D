<?php
function getProductDetail(mysqli $conn, int $productId): ?array
{
    $sql = "
        SELECT
            p.id,
            p.spu,
            p.name,
            p.description,
            p.base_price,
            p.image_url,
            c.id AS category_id,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.status = 'active'
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $product = $result->fetch_assoc();
    $stmt->close();
    
    return $product;
}

/**
 * Get all variants of a product
 */
function getProductVariants(mysqli $conn, int $productId): array
{
    $variants = [];
    
    $sql = "
        SELECT
            pv.id,
            pv.sku_code,
            pv.price,
            pv.image_url,
            pv.attributes,
            inv.quantity,
            inv.reserved_quantity
        FROM product_variants pv
        LEFT JOIN inventory inv ON pv.id = inv.product_variant_id
        WHERE pv.product_id = ?
        ORDER BY pv.id
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Parse JSON attributes if exists
        if (!empty($row['attributes'])) {
            $row['attributes'] = json_decode($row['attributes'], true);
        } else {
            $row['attributes'] = [];
        }
        $variants[] = $row;
    }
    
    $stmt->close();
    
    return $variants;
}

/**
 * Get product images (main + thumbnails)
 * Returns product main image + variant images
 */
function getProductImages(mysqli $conn, int $productId): array
{
    $images = [];
    
    // Get main product image
    $sql = "SELECT image_url FROM products WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['image_url'])) {
            $images[] = [
                'url' => $row['image_url'],
                'alt' => 'Product main image'
            ];
        }
    }
    $stmt->close();
    
    // Get variant images
    $sql = "
        SELECT DISTINCT image_url 
        FROM product_variants 
        WHERE product_id = ? AND image_url IS NOT NULL
        LIMIT 10
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['image_url'], array_column($images, 'url'))) {
            $images[] = [
                'url' => $row['image_url'],
                'alt' => 'Product variant image'
            ];
        }
    }
    $stmt->close();
    
    return $images;
}

/**
 * Get available stock for a variant
 */
function getVariantStock(mysqli $conn, int $variantId): int
{
    $sql = "
        SELECT (quantity - reserved_quantity) AS available 
        FROM inventory 
        WHERE product_variant_id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    
    $stmt->bind_param("i", $variantId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return max(0, (int)$row['available']);
    }
    
    $stmt->close();
    return 0;
}
