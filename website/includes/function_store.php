<?php
/**
 * Get all categories with their direct children (one level deep)
 */
function getStoreCategories(PDO $conn): array
{
    $sql = "SELECT id, parent_id, name FROM categories ORDER BY parent_id, name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by parent_id (NULL included)
    $childrenMap = [];
    foreach ($allCategories as $cat) {
        // parent_id can be null
        $parentKey = $cat['parent_id']; // null or int
        if (!array_key_exists($parentKey, $childrenMap)) {
            $childrenMap[$parentKey] = [];
        }
        $childrenMap[$parentKey][] = $cat;
    }

    // Root categories (parent_id IS NULL)
    $categories = $childrenMap[null] ?? [];

    // Attach children one level deep (đúng như UI bạn đang render)
    foreach ($categories as &$parent) {
        $pid = $parent['id'];
        $parent['children'] = $childrenMap[$pid] ?? [];
    }
    unset($parent);

    return $categories;
}

/**
 * Get all category IDs including children of a parent category name
 * (Parent + its direct children)
 */
function getCategoryIdsWithChildren(PDO $conn, string $categoryName): array
{
    $ids = [];

    // Get parent category id
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $stmt->execute([$categoryName]);
    $parentId = $stmt->fetchColumn();

    if ($parentId === false) {
        return [];
    }

    $parentId = (int)$parentId;
    $ids[] = $parentId;

    // Get direct children ids
    $stmt2 = $conn->prepare("SELECT id FROM categories WHERE parent_id = ?");
    $stmt2->execute([$parentId]);
    $childIds = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    foreach ($childIds as $cid) {
        $ids[] = (int)$cid;
    }

    return $ids;
}
/**
 * Build SQL query and parameters for fetching products with filters
 */
function buildStoreProductQuery(array $filters, PDO $conn): array
{
    $sql = "
        SELECT
            p.id,
            p.name,
            p.description AS `desc`,
            p.base_price AS price, -- Chỉ lấy giá gốc của SPU
            c.name AS category,
            COALESCE(pv.image_url, p.image_url) AS image_url, -- Vẫn giữ ảnh variant đầu tiên nếu có
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

    // Category filter (Giữ nguyên logic cũ)
    if (!empty($filters['categories'])) {
        $allCategoryIds = [];
        foreach ($filters['categories'] as $catName) {
            $ids = getCategoryIdsWithChildren($conn, (string)$catName);
            $allCategoryIds = array_merge($allCategoryIds, $ids);
        }
        $allCategoryIds = array_values(array_unique(array_map('intval', $allCategoryIds)));
        if (!empty($allCategoryIds)) {
            $placeholders = implode(',', array_fill(0, count($allCategoryIds), '?'));
            $where[] = "p.category_id IN ($placeholders)";
            $params = array_merge($params, $allCategoryIds);
        }
    }

    // Lọc theo Price - Đã cập nhật để dùng p.base_price
    if (!empty($filters['prices'])) {
        $priceConds = [];
        foreach ($filters['prices'] as $range) {
            if ($range === 'Under $20') {
                $priceConds[] = "p.base_price < 20";
            } elseif ($range === '$20 to $50') {
                $priceConds[] = "(p.base_price >= 20 AND p.base_price <= 50)";
            } elseif ($range === 'Over $50') {
                $priceConds[] = "p.base_price > 50";
            }
        }
        if (!empty($priceConds)) {
            $where[] = "(" . implode(" OR ", $priceConds) . ")";
        }
    }

    if (!empty($where)) {
        $sql .= " AND " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY p.id DESC";

    return [$sql, $params];
}
/**
 * Get products for Store page (PDO)
 */
function getStoreProducts(PDO $conn, array $filters): array
{
    [$sql, $params] = buildStoreProductQuery($filters, $conn);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
