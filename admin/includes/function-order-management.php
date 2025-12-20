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

function build_orders_where(array $filters, array &$params): string
{
    $where = [];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = "o.tracking_number LIKE :q";
        $params[':q'] = '%' . (string)$filters['q'] . '%';
    }

    if (!empty($filters['status'])) {
        $where[] = "o.status = :status";
        $params[':status'] = (string)$filters['status'];
    }

    return $where ? ("WHERE " . implode(" AND ", $where)) : "";
}

function count_orders(PDO $conn, array $filters = []): int
{
    $params = [];
    $where = build_orders_where($filters, $params);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders o {$where}");
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
            a.full_name AS account_full_name,
            o.tracking_number,
            o.total_amount,
            o.shipping_fee,
            o.final_amount,
            o.status,
            o.shipping_carrier,
            o.created_at
        FROM orders o
        LEFT JOIN accounts a ON a.id = o.account_id
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
