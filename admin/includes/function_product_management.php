<?php
/**
 * Product Management Functions
 * Handles categories, products, variants, and inventory queries
 */

/**
 * Build hierarchical category tree
 * @param PDO $conn
 * @param int|null $parent_id
 * @return array Nested array of categories
 */
function getCategoryTree($conn, $parent_id = null) {
    try {
        $sql = "SELECT id, parent_id, name FROM categories WHERE parent_id " . ($parent_id === null ? "IS NULL" : "= :parent_id") . " ORDER BY name";
        $stmt = $conn->prepare($sql);
        if ($parent_id !== null) {
            $stmt->bindValue(':parent_id', $parent_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $categories = $stmt->fetchAll();

        foreach ($categories as &$cat) {
            $cat['children'] = getCategoryTree($conn, $cat['id']);
        }
        return $categories;
    } catch (Exception $e) {
        error_log("getCategoryTree error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all categories (flat list)
 * @param PDO $conn
 * @return array
 */
function getAllCategories($conn) {
    try {
        $sql = "SELECT id, parent_id, name FROM categories ORDER BY parent_id, name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("getAllCategories error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get products by category with inventory data
 * @param PDO $conn
 * @param int|null $category_id
 * @param string $search
 * @return array
 */
function getProductsByCategory($conn, $category_id = null, $search = '') {
    try {
        $sql = "
            SELECT 
                p.id,
                p.spu,
                p.name,
                p.base_price,
                p.image_url,
                p.status,
                c.id as category_id,
                c.name as category_name,
                COUNT(DISTINCT pv.id) as variant_count,
                COALESCE(SUM(i.quantity), 0) as total_quantity,
                COALESCE(SUM(i.reserved_quantity), 0) as total_reserved,
                (COALESCE(SUM(i.quantity), 0) - COALESCE(SUM(i.reserved_quantity), 0)) as available
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_variants pv ON p.id = pv.product_id
            LEFT JOIN inventory i ON pv.id = i.product_variant_id
        ";

        $params = [];
        $conditions = [];

        if ($category_id !== null && $category_id !== '') {
            $conditions[] = "p.category_id = :category_id";
            $params[':category_id'] = $category_id;
        }

        if (!empty($search)) {
            $conditions[] = "(p.name LIKE :search OR p.spu LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " GROUP BY p.id ORDER BY p.name";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("getProductsByCategory error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get single product detail
 * @param PDO $conn
 * @param int $product_id
 * @return array|null
 */
function getProductDetail($conn, $product_id) {
    try {
        $sql = "
            SELECT 
                p.id,
                p.spu,
                p.name,
                p.description,
                p.base_price,
                p.image_url,
                p.status,
                p.category_id,
                c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $product_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("getProductDetail error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get product variants with inventory
 * @param PDO $conn
 * @param int $product_id
 * @return array
 */
function getProductVariants($conn, $product_id) {
    try {
        $sql = "
            SELECT 
                pv.id,
                pv.sku_code,
                pv.price,
                pv.attributes,
                pv.image_url,
                COALESCE(i.quantity, 0) as quantity,
                COALESCE(i.reserved_quantity, 0) as reserved_quantity,
                (COALESCE(i.quantity, 0) - COALESCE(i.reserved_quantity, 0)) as available
            FROM product_variants pv
            LEFT JOIN inventory i ON pv.id = i.product_variant_id
            WHERE pv.product_id = :product_id
            ORDER BY pv.sku_code
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':product_id' => $product_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("getProductVariants error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total available quantity for a product (all variants)
 * @param PDO $conn
 * @param int $product_id
 * @return int
 */
function getProductAvailability($conn, $product_id) {
    try {
        $sql = "
            SELECT 
                COALESCE(SUM(i.quantity), 0) as total_quantity,
                COALESCE(SUM(i.reserved_quantity), 0) as total_reserved
            FROM product_variants pv
            LEFT JOIN inventory i ON pv.id = i.product_variant_id
            WHERE pv.product_id = :product_id
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':product_id' => $product_id]);
        $result = $stmt->fetch();
        return (int)($result['total_quantity'] - $result['total_reserved']);
    } catch (Exception $e) {
        error_log("getProductAvailability error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get product count by category
 * @param PDO $conn
 * @param int $category_id
 * @return int
 */
function getProductCountByCategory($conn, $category_id) {
    try {
        $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $category_id]);
        return (int)$stmt->fetch()['count'];
    } catch (Exception $e) {
        error_log("getProductCountByCategory error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Format status badge
 * @param string $status
 * @return string HTML
 */
function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'draft' => '<span class="badge bg-warning text-dark">Draft</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

/**
 * Render category tree as HTML
 * @param array $categories
 * @param int|null $selected_id
 * @param int $depth
 * @return string HTML
 */
function renderCategoryTree($categories, $selected_id = null, $depth = 0) {
    $html = '';
    foreach ($categories as $cat) {
        $active = ($cat['id'] == $selected_id) ? 'active' : '';
        $hasChildren = !empty($cat['children']);
        $icon = $hasChildren ? '📁' : '📄';
        
        $html .= '<div class="cat-item ' . $active . '" style="margin-left: ' . ($depth * 20) . 'px;" data-id="' . $cat['id'] . '">';
        $html .= '<span class="cat-toggle">' . ($hasChildren ? '▼' : '▶') . '</span>';
        $html .= '<span class="cat-icon">' . $icon . '</span>';
        $html .= '<span class="cat-name">' . htmlspecialchars($cat['name']) . '</span>';
        $html .= '<div class="cat-actions ms-auto">';
        $html .= '<button class="btn btn-xs btn-light" title="Add sub-category"><i class="bi bi-plus-circle"></i></button>';
        $html .= '<button class="btn btn-xs btn-light" title="Edit"><i class="bi bi-pencil"></i></button>';
        $html .= '<button class="btn btn-xs btn-danger" title="Delete"><i class="bi bi-trash"></i></button>';
        $html .= '</div>';
        $html .= '</div>';

        if ($hasChildren) {
            $html .= renderCategoryTree($cat['children'], $selected_id, $depth + 1);
        }
    }
    return $html;
}
?>
