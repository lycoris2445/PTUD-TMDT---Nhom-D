<?php
// ../includes/function_store.php

/**
 * Get categories with hierarchical structure (parent => children)
 * Also returns a flat map of category name => id for easy lookup
 */
function getStoreCategories(mysqli $conn): array
{
    // Fetch all categories
    $allCategories = [];
    $rs = $conn->query("SELECT id, parent_id, name FROM categories ORDER BY parent_id, name");
    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $allCategories[] = $row;
        }
    }
    
    // Build hierarchical structure
    $categories = [];
    $childrenMap = [];
    
    // Group children by parent_id
    foreach ($allCategories as $cat) {
        $parentId = $cat['parent_id'] ?? null;
        if (!isset($childrenMap[$parentId])) {
            $childrenMap[$parentId] = [];
        }
        $childrenMap[$parentId][] = $cat;
    }
    
    // Build tree: parent categories with children
    $categories = $childrenMap[null] ?? []; // Root categories (parent_id IS NULL)
    
    // Add children to each parent
    foreach ($categories as &$parent) {
        $parent['children'] = $childrenMap[$parent['id']] ?? [];
    }
    
    return $categories;
}

/**
 * Get all category IDs including children of a parent category name
 */
function getCategoryIdsWithChildren(mysqli $conn, string $categoryName): array
{
    $ids = [];
    
    // Get parent category ID
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $parentId = $row['id'];
        $ids[] = $parentId;
        
        // Get all children of this parent
        $childStmt = $conn->prepare("SELECT id FROM categories WHERE parent_id = ?");
        $childStmt->bind_param("i", $parentId);
        $childStmt->execute();
        $childResult = $childStmt->get_result();
        
        while ($childRow = $childResult->fetch_assoc()) {
            $ids[] = $childRow['id'];
        }
        $childStmt->close();
    }
    $stmt->close();
    
    return $ids;
}

/**
 * Build SQL + params for store product listing
 */
function buildStoreProductQuery(array $filters, mysqli $conn): array
{
    $sql = "
        SELECT
            p.id,
            p.name,
            p.description AS `desc`,
            COALESCE(pv.price, p.base_price) AS price,
            c.name AS category,
            COALESCE(pv.image_url, p.image_url) AS image_url,
            pv.id AS variant_id,
            pv.sku_code
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_variants pv
            ON pv.id = (
                SELECT MIN(pv2.id)
                FROM product_variants pv2
                WHERE pv2.product_id = p.id
            )
        WHERE p.status = 'active'
    ";

    $where = [];
    $params = [];
    $types = "";

    // Category filter - include parent and all children
    if (!empty($filters['categories'])) {
        $allCategoryIds = [];
        foreach ($filters['categories'] as $catName) {
            $ids = getCategoryIdsWithChildren($conn, $catName);
            $allCategoryIds = array_merge($allCategoryIds, $ids);
        }
        
        // Remove duplicates
        $allCategoryIds = array_unique($allCategoryIds);
        
        if (!empty($allCategoryIds)) {
            $placeholders = implode(',', array_fill(0, count($allCategoryIds), '?'));
            $where[] = "p.category_id IN ($placeholders)";
            foreach ($allCategoryIds as $catId) {
                $params[] = $catId;
                $types .= "i";
            }
        }
    }

    // Price filter (theo UI bạn đang dùng)
    if (!empty($filters['prices'])) {
        $priceConds = [];
        foreach ($filters['prices'] as $range) {
            if ($range === 'Dưới 500K') {
                $priceConds[] = "COALESCE(pv.price, p.base_price) < 500000";
            } elseif ($range === '500K - 1 triệu') {
                $priceConds[] = "(COALESCE(pv.price, p.base_price) >= 500000 AND COALESCE(pv.price, p.base_price) <= 1000000)";
            } elseif ($range === 'Trên 1 triệu') {
                $priceConds[] = "COALESCE(pv.price, p.base_price) > 1000000";
            }
        }
        if (!empty($priceConds)) {
            $where[] = "(" . implode(" OR ", $priceConds) . ")";
        }
    }

    if (!empty($where)) {
        $sql .= " AND " . implode(" AND ", $where);
    }

    // Fix created_at (không có) => dùng id desc làm “mới nhất”
    $sql .= " ORDER BY p.id DESC";

    return [$sql, $types, $params];
}

function getStoreProducts(mysqli $conn, array $filters): array
{
    [$sql, $types, $params] = buildStoreProductQuery($filters, $conn);

    $products = [];

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rs = $stmt->get_result();
    } else {
        $rs = $conn->query($sql);
    }

    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $products[] = $row;
        }
    }

    return $products;
}