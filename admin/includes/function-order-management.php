<?php
declare(strict_types=1);

function order_statuses(): array
{
    return [
        'new'             => 'New',
        'pending'         => 'Pending Payment',
        'on_hold'         => 'On Hold',
        'processing'      => 'Processing',
        'awaiting_pickup' => 'Awaiting Pickup',
        'shipping'        => 'Shipping',
        'shipped'         => 'Shipped',
        'completed'       => 'Completed',
        'cancelled'       => 'Cancelled',
        'declined'        => 'Declined',
    ];
}

function return_statuses(): array
{
    return [
        'request_return'          => 'Return requested',
        'accept_return'           => 'Return accepted', // Bước đệm
        'decline_return'          => 'Return declined', // Kết thúc
        'receive_return_package'  => 'Return package received',
        'accept_refund'           => 'Refunded',        // Kết thúc
        'decline_refund'          => 'Refund declined', // Kết thúc
    ];
}

function refund_statuses(): array
{
    return [
        'pending'    => 'Pending Review',
        'approved'   => 'Approved',
        'rejected'   => 'Rejected',
        'processing' => 'Processing',
        'completed'  => 'Completed',
        'failed'     => 'Failed'
    ];
}

/**
 * Status options for the FILTER dropdown (search bar).
 * We prefix values to avoid mixing order.status vs return.status.
 * Example values:
 *  - order:new
 *  - return:request_return
 */
function status_filter_options(): array
{
    $out = [
        'Order Status' => [],
        'Return / Refund' => [],
    ];

    foreach (order_statuses() as $k => $label) {
        $out['Order Status']["order:{$k}"] = "{$label} ({$k})";
    }
    foreach (return_statuses() as $k => $label) {
        $out['Return / Refund']["return:{$k}"] = "{$label} ({$k})";
    }

    return $out;
}

function allowed_return_transitions(): array
{
    return [
        'request_return'         => ['accept_return', 'decline_return'],
        'accept_return'          => ['receive_return_package'],
        'receive_return_package' => ['accept_refund'],
        'decline_return'         => [],
        'accept_refund'          => [],
    ];
}

function allowed_next_return_statuses(string $current): array
{
    switch ($current) {
        case 'request_return':
            // Cho phép Chấp nhận hoặc Từ chối yêu cầu ban đầu
            return ['accept_return', 'decline_return'];

        case 'receive_return_package':
            // Sau khi đã nhận hàng, admin quyết định hoàn tiền hoặc không
            return ['accept_refund', 'decline_refund'];

        default:
            // Các trạng thái: decline_return, accept_refund, decline_refund là trạng thái cuối (Terminal)
            // Không cho phép chuyển đi đâu nữa.
            return [];
    }
}
function can_transition_return_status(string $from, string $to): bool
{
    $next = allowed_next_return_statuses($from);
    return in_array($to, $next, true);
}
function allowed_status_transitions(): array
{
    return [
        'new'             => ['processing'],
        'processing'      => ['awaiting_pickup'],
        'awaiting_pickup' => ['shipping'],
        'shipping'        => ['shipped'],
        'shipped'         => ['completed'],
        'completed'       => [],

        // Các trạng thái khác không nằm trong fulfillment flow => không cho chuyển trong UI edit.
        'pending'         => [],
        'on_hold'         => ['processing'], // Admin có thể chuyển từ on_hold sang processing
        'cancelled'       => [],
        'declined'        => [],
    ];
}

function allowed_next_statuses(string $currentStatus): array
{
    $map = allowed_status_transitions();
    return $map[$currentStatus] ?? [];
}

function can_transition_status(string $from, string $to): bool
{
    if ($from === $to) return true;
    $next = allowed_next_statuses($from);
    return in_array($to, $next, true);
}

function build_orders_where(array $filters, array &$params): string
{
    $where = [];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = "o.tracking_number LIKE :q";
        $params[':q'] = '%' . (string)$filters['q'] . '%';
    }

    // status filter supports:
    //  - "new" (backward compatible) => orders.status
    //  - "order:new"
    //  - "return:request_return"
    if (!empty($filters['status'])) {
        $raw = (string)$filters['status'];
        $scope = 'order';
        $val = $raw;

        if (str_contains($raw, ':')) {
            [$scope, $val] = explode(':', $raw, 2);
            $scope = strtolower(trim($scope));
            $val = trim($val);
        }

        if ($scope === 'return') {
            $where[] = "r.status = :r_status";
            $params[':r_status'] = $val;
        } else {
            $where[] = "o.status = :status";
            $params[':status'] = $val;
        }
    }

    return $where ? ("WHERE " . implode(" AND ", $where)) : "";
}

function count_orders(PDO $conn, array $filters = []): int
{
    $params = [];
    $where = build_orders_where($filters, $params);

    // LEFT JOIN returns to support filtering by return status.
    // COUNT(DISTINCT) prevents double counting if an order has multiple return rows.
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o LEFT JOIN returns r ON r.order_id = o.id {$where}");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function fetch_orders(PDO $conn, array $filters = [], int $limit = 15, int $offset = 0): array
{
    $params = [];
    $where = build_orders_where($filters, $params);

    $sql = "
    SELECT
        o.id,
        o.account_id,
        a.email AS account_email,
        o.tracking_number,
        o.total_amount,
        o.shipping_fee,
        o.final_amount,
        o.status AS order_status,
        r.status AS return_status,
        o.created_at
        FROM orders o
        LEFT JOIN accounts a ON a.id = o.account_id
        LEFT JOIN returns r ON r.order_id = o.id
        {$where}
        ORDER BY o.created_at DESC, o.id DESC
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

function get_order_by_id(PDO $conn, int $orderId): ?array
{
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function get_order_items(PDO $conn, int $orderId): array
{
    $sql = "
        SELECT id, product_variant_id, quantity, price_at_purchase
        FROM order_items
        WHERE order_id = :oid
        ORDER BY id ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':oid' => $orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function get_order_history(PDO $conn, int $orderId): array
{
    $sql = "
        SELECT previous_status, new_status, note, created_at
        FROM order_history
        WHERE order_id = :oid
        ORDER BY created_at DESC, id DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':oid' => $orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function delete_order(PDO $conn, int $orderId): bool
{
    // order_items & order_history will cascade by FK
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = :id");
    return $stmt->execute([':id' => $orderId]);
}

/**
 * Update tracking/carrier/status.
 * If status changes => insert order_history row
 */
function update_order(PDO $conn, int $orderId, array $data, ?string $note = null): bool
{
    $current = get_order_by_id($conn, $orderId);
    if (!$current) return false;

    $allowedStatuses = array_keys(order_statuses());

    $tracking = array_key_exists('tracking_number', $data) ? trim((string)$data['tracking_number']) : (string)($current['tracking_number'] ?? '');
    $carrier  = array_key_exists('shipping_carrier', $data) ? trim((string)$data['shipping_carrier']) : (string)($current['shipping_carrier'] ?? '');
    $status   = array_key_exists('status', $data) ? trim((string)$data['status']) : (string)$current['status'];

    if ($status === '' || !in_array($status, $allowedStatuses, true)) {
        // keep current if invalid/empty
        $status = (string)$current['status'];
    }

    $prevStatus = (string)$current['status'];
    $newStatus  = $status;
    // Enforce fulfillment transition rule
    if ($newStatus !== $prevStatus && !can_transition_status($prevStatus, $newStatus)) {
        // Không cho đổi status ngoài flow (nhưng vẫn có thể đổi tracking/carrier)
        return false;
    }

    $statusChanged = ($newStatus !== $prevStatus);

    try {
        $conn->beginTransaction();

        $sql = "
            UPDATE orders
            SET tracking_number = :tracking,
                shipping_carrier = :carrier,
                status = :status
            WHERE id = :id
        ";
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute([
            ':tracking' => ($tracking === '') ? null : $tracking,
            ':carrier'  => ($carrier === '') ? null : $carrier,
            ':status'   => $newStatus,
            ':id'       => $orderId,
        ]);

        if (!$ok) {
            $conn->rollBack();
            return false;
        }

        if ($statusChanged) {
            $stmtH = $conn->prepare("
                INSERT INTO order_history (order_id, previous_status, new_status, note)
                VALUES (:oid, :prev, :new, :note)
            ");
            $stmtH->execute([
                ':oid'  => $orderId,
                ':prev' => $prevStatus,
                ':new'  => $newStatus,
                ':note' => ($note === '' ? null : $note),
            ]);
        }

        $conn->commit();
        return true;

    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        return false;
    }
}

function get_return_by_order_id(PDO $conn, int $orderId): ?array
{
    $stmt = $conn->prepare("SELECT * FROM returns WHERE order_id = :oid ORDER BY id DESC LIMIT 1");
    $stmt->execute([':oid' => $orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function get_refund_requests_by_order_id(PDO $conn, int $orderId): array
{
    $stmt = $conn->prepare("SELECT * FROM REFUND WHERE order_id = :oid ORDER BY created_at DESC");
    $stmt->execute([':oid' => $orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function get_return_items(PDO $conn, int $returnId): array
{
    $sql = "
        SELECT 
            ri.*,
            oi.product_variant_id,
            oi.price_at_purchase,
            COALESCE(pv.sku_code, p.spu) as sku,
            p.name as product_name
        FROM RETURN_ITEMS ri
        JOIN ORDER_ITEMS oi ON ri.order_item_id = oi.id
        LEFT JOIN product_variants pv ON oi.product_variant_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        WHERE ri.return_id = :rid
        ORDER BY ri.id ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':rid' => $returnId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function update_return(PDO $conn, int $returnId, array $data): bool
{
    $allowed = array_keys(return_statuses());

    $status = isset($data['status']) ? trim((string)$data['status']) : '';
    $note   = array_key_exists('admin_note', $data) ? trim((string)$data['admin_note']) : null;
    $amount = array_key_exists('refund_amount', $data) ? $data['refund_amount'] : null;

    if ($status !== '' && !in_array($status, $allowed, true)) {
        return false;
    }

    $currentStmt = $conn->prepare("SELECT * FROM returns WHERE id = :id LIMIT 1");
    $currentStmt->execute([':id' => $returnId]);
    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$current) return false;

    $prev = (string)$current['status'];
    $to   = ($status === '' ? $prev : $status);

    if ($to !== $prev && !can_transition_return_status($prev, $to)) {
        return false;
    }

    $stmt = $conn->prepare("
        UPDATE returns
        SET status = :status,
            admin_note = :note,
            refund_amount = :amount
        WHERE id = :id
    ");
    return $stmt->execute([
        ':status' => $to,
        ':note'   => ($note === '' ? null : $note),
        ':amount' => ($amount === '' ? null : $amount),
        ':id'     => $returnId,
    ]);
}
