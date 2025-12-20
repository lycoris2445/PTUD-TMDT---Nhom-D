<?php
declare(strict_types=1);

function customer_sort_whitelist(): array
{
    // key => SQL expression
    return [
        'full_name'     => 'a.full_name',
        'email'         => 'a.email',
        'created_at'    => 'a.created_at',
        'last_login_at' => 'a.last_login_at',
        'orders'        => 'orders',
        'total_spent'   => 'total_spent',
        'status'        => 'a.status',
    ];
}

function build_customers_where(array $filters, array &$params): string
{
    $where = [];
    $params = [];

    // Chỉ lấy role user
    $where[] = "r.name = 'user'";

    if (!empty($filters['q'])) {
        $where[] = "(a.full_name LIKE :q OR a.email LIKE :q)";
        $params[':q'] = '%' . (string)$filters['q'] . '%';
    }

    if (!empty($filters['status'])) {
        $where[] = "a.status = :status";
        $params[':status'] = (string)$filters['status']; // 'active' | 'suspended'
    }

    return "WHERE " . implode(" AND ", $where);
}

function count_customers(PDO $conn, array $filters = []): int
{
    $params = [];
    $where = build_customers_where($filters, $params);

    // COUNT DISTINCT account id vì join orders sẽ nhân bản dòng
    $sql = "
        SELECT COUNT(DISTINCT a.id)
        FROM accounts a
        INNER JOIN account_roles ar ON ar.account_id = a.id
        INNER JOIN roles r ON r.id = ar.role_id
        LEFT JOIN orders o ON o.account_id = a.id
        {$where}
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function fetch_customers(
    PDO $conn,
    array $filters = [],
    string $sort = 'full_name',
    string $order = 'asc',
    int $limit = 10,
    int $offset = 0
): array {
    $params = [];
    $where = build_customers_where($filters, $params);

    $whitelist = customer_sort_whitelist();
    $sortExpr = $whitelist[$sort] ?? $whitelist['full_name'];
    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

    $sql = "
        SELECT
            a.id,
            a.full_name,
            a.email,
            a.phone_number,
            a.status,
            a.created_at,
            a.last_login_at,
            (
              SELECT ad.detail_address
              FROM addresses ad
              WHERE ad.account_id = a.id AND ad.is_default = 1
              LIMIT 1
            ) AS detail_address,
            COUNT(o.id) AS orders,
            IFNULL(SUM(o.final_amount), 0) AS total_spent
        FROM accounts a
        INNER JOIN account_roles ar ON ar.account_id = a.id
        INNER JOIN roles r ON r.id = ar.role_id
        LEFT JOIN orders o ON o.account_id = a.id
        {$where}
        GROUP BY a.id
        ORDER BY {$sortExpr} {$order}
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function get_customer_by_id(PDO $conn, int $id): ?array
{
    $sql = "
        SELECT
            a.*,
            (
              SELECT ad.detail_address
              FROM addresses ad
              WHERE ad.account_id = a.id AND ad.is_default = 1
              LIMIT 1
            ) AS detail_address,
            (
              SELECT COUNT(*)
              FROM orders o
              WHERE o.account_id = a.id
            ) AS orders,
            (
              SELECT IFNULL(SUM(o.final_amount), 0)
              FROM orders o
              WHERE o.account_id = a.id
            ) AS total_spent
        FROM accounts a
        WHERE a.id = :id
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function set_customer_status(PDO $conn, int $id, string $status): bool
{
    if (!in_array($status, ['active', 'suspended'], true)) return false;
    $stmt = $conn->prepare("UPDATE accounts SET status = :status WHERE id = :id");
    return $stmt->execute([':status' => $status, ':id' => $id]);
}

function delete_customer(PDO $conn, int $id): bool
{
    // NOTE: xóa cứng; nếu hệ thống có nhiều FK khác thì có thể fail.
    // Bạn có thể đổi thành "suspended" thay vì delete nếu muốn an toàn.
    $stmt = $conn->prepare("DELETE FROM accounts WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}
