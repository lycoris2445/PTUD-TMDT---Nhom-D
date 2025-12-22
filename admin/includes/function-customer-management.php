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
        $where[] = "(a.full_name LIKE :q_name OR a.email LIKE :q_email)";
        $like = '%' . (string)$filters['q'] . '%';
        $params[':q_name']  = $like;
        $params[':q_email'] = $like;
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

function log_account_status(PDO $conn, int $accountId, string $action, ?string $reason, ?int $changedBy): bool
{
    if (!in_array($action, ['suspend', 'activate'], true)) return false;

    $stmt = $conn->prepare("
        INSERT INTO account_status_logs (account_id, action, reason, changed_by)
        VALUES (:account_id, :action, :reason, :changed_by)
    ");
    return $stmt->execute([
        ':account_id' => $accountId,
        ':action' => $action,
        ':reason' => $reason,
        ':changed_by' => $changedBy,
    ]);
}

function suspend_customer_with_reason(PDO $conn, int $id, string $reason, ?int $changedBy = null): bool
{
    $reason = trim($reason);
    if ($reason === '' || mb_strlen($reason) > 1000) return false;

    try {
        $conn->beginTransaction();

        // Update status
        $stmt = $conn->prepare("UPDATE accounts SET status = 'suspended' WHERE id = :id");
        $ok1 = $stmt->execute([':id' => $id]);

        // Log reason
        $ok2 = log_account_status($conn, $id, 'suspend', $reason, $changedBy);

        if ($ok1 && $ok2) {
            $conn->commit();
            return true;
        }
        $conn->rollBack();
        return false;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        return false;
    }
}

function activate_customer(PDO $conn, int $id, ?int $changedBy = null): bool
{
    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE accounts SET status = 'active' WHERE id = :id");
        $ok1 = $stmt->execute([':id' => $id]);

        $ok2 = log_account_status($conn, $id, 'activate', null, $changedBy);

        if ($ok1 && $ok2) {
            $conn->commit();
            return true;
        }
        $conn->rollBack();
        return false;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        return false;
    }
}

