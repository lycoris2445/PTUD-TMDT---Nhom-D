<?php
declare(strict_types=1);

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function getAllCategories($conn): array {
    $stmt = $conn->query("SELECT id, parent_id, name FROM CATEGORIES ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryTree($conn, ?int $parentId = null): array {
    if ($parentId === null) {
        $stmt = $conn->prepare("SELECT id, parent_id, name FROM CATEGORIES WHERE parent_id IS NULL ORDER BY name");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT id, parent_id, name FROM CATEGORIES WHERE parent_id = :pid ORDER BY name");
        $stmt->execute([':pid' => $parentId]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['children'] = getCategoryTree($conn, (int)$r['id']);
    }
    return $rows;
}

function renderCategoryTree(array $tree, ?int $selectedId = null): string {
    $html = '<ul class="list-unstyled mb-0">';
    foreach ($tree as $node) {
        $id = (int)$node['id'];
        $name = (string)$node['name'];
        $hasChildren = !empty($node['children']);
        $active = ($selectedId === $id) ? ' active' : '';

        $html .= '<li class="mb-1">';
        $html .= '<div class="d-flex align-items-center gap-2 category-row' . $active . '">';

        if ($hasChildren) {
            $html .= '<button type="button" class="btn btn-sm btn-light toggle-children" data-target="cat-children-' . $id . '">
                        <i class="bi bi-caret-right-fill"></i>
                      </button>';
        } else {
            $html .= '<span style="width:32px; display:inline-block;"></span>';
        }

        $html .= '<a class="flex-grow-1 text-decoration-none category-link" href="?category=' . $id . '">' . h($name) . '</a>';
        $html .= '</div>';

        if ($hasChildren) {
            $html .= '<div id="cat-children-' . $id . '" class="ms-4 mt-1 d-none">';
            $html .= renderCategoryTree($node['children'], $selectedId);
            $html .= '</div>';
        }

        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

/**
 * Lấy tất cả category con của một category (đệ quy)
 */
function getAllChildCategoryIds($conn, int $categoryId): array {
    $ids = [$categoryId];
    
    $stmt = $conn->prepare("SELECT id FROM CATEGORIES WHERE parent_id = ?");
    $stmt->execute([$categoryId]);
    $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($children as $childId) {
        $ids = array_merge($ids, getAllChildCategoryIds($conn, (int)$childId));
    }
    
    return $ids;
}

function getProductsByCategory($conn, ?int $categoryId, string $search = '', string $status = ''): array {
    if (!$categoryId) return [];

    $search = trim($search);
    $status = trim($status);

    // Lấy tất cả category con (bao gồm cả chính nó)
    $categoryIds = getAllChildCategoryIds($conn, $categoryId);
    
    // Tạo placeholders cho IN clause
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

    $sql = "
        SELECT
            p.id,
            p.spu,
            p.name,
            p.base_price,
            p.status,
            p.image_url,
            c.name AS category_name,
            COUNT(DISTINCT pv.id) AS variant_count,
            COALESCE(SUM(i.quantity), 0) AS total_quantity,
            COALESCE(SUM(i.reserved_quantity), 0) AS reserved_quantity,
            COALESCE(SUM(i.quantity - i.reserved_quantity), 0) AS available
        FROM PRODUCTS p
        LEFT JOIN CATEGORIES c ON c.id = p.category_id
        LEFT JOIN PRODUCT_VARIANTS pv ON pv.product_id = p.id
        LEFT JOIN INVENTORY i ON i.product_variant_id = pv.id
        WHERE p.category_id IN ($placeholders)
          AND (? = '' OR p.name LIKE ? OR p.spu LIKE ? OR pv.sku_code LIKE ?)
          AND (? = '' OR p.status = ?)
        GROUP BY p.id
        ORDER BY p.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $like = '%' . $search . '%';

    // Bind parameters: category IDs + search params + status params
    $params = array_merge(
        $categoryIds,
        [$search, $like, $like, $like, $status, $status]
    );

    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStatusBadge(string $status): string {
    $status = strtolower(trim($status));
    return match ($status) {
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'draft' => '<span class="badge bg-dark">Draft</span>',
        default => '<span class="badge bg-light text-dark">Unknown</span>',
    };
}
