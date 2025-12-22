<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/function-order-management.php';

try {
    /** @var PDO $conn */
    $conn = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection error: " . htmlspecialchars($e->getMessage()));
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect_back(): void
{
    // quay về trang management (giữ filter nếu có)
    $qs = $_POST['return_query'] ?? '';
    header('Location: order-management.php' . ($qs ? ('?' . $qs) : ''));
    exit;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
    flash('danger', 'CSRF token invalid.');
    redirect_back();
}

$action = (string)($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('danger', 'Invalid order id.');
    redirect_back();
}

if ($action === 'update') {
    $data = [
        'tracking_number'  => trim((string)($_POST['tracking_number'] ?? '')),
        'shipping_carrier' => trim((string)($_POST['shipping_carrier'] ?? '')),
        'status'           => trim((string)($_POST['status'] ?? '')),
    ];
    $note = trim((string)($_POST['note'] ?? ''));
    if ($note === '') $note = null;

    $current = get_order_by_id($conn, $id);
    if (!$current) {
        flash('danger', "Order #{$id} not found.");
        redirect_back();
    }

    $prev = (string)$current['status'];
    $to   = (string)$data['status'];

    if ($to !== '' && $to !== $prev && !can_transition_status($prev, $to)) {
        flash('danger', "Cannot change status from '{$prev}' to '{$to}'. Valid flow: new → processing → awaiting_pickup → shipping → shipped → completed.");
        redirect_back();
    }

    $ok = update_order($conn, $id, $data, $note);
    flash($ok ? 'success' : 'danger', $ok ? "Updated order #{$id}." : "Failed to update order #{$id}.");

    redirect_back();
}

if ($action === 'return_create') {
    $order = get_order_by_id($conn, $id);
    if (!$order) {
        flash('danger', "Order #{$id} not found.");
        redirect_back();
    }

    $existing = get_return_by_order_id($conn, $id);
    if ($existing) {
        flash('danger', "This order already has a return request (Return #{$existing['id']}).");
        redirect_back();
    }

    $reason = trim((string)($_POST['reason'] ?? ''));
    $adminNote = trim((string)($_POST['admin_note'] ?? ''));
    $refundAmountRaw = trim((string)($_POST['refund_amount'] ?? ''));
    $refundAmount = ($refundAmountRaw === '') ? null : (float)$refundAmountRaw;

    $ok = create_return(
        $conn,
        $id,
        (int)$order['account_id'],
        $reason,
        $adminNote === '' ? null : $adminNote,
        $refundAmount
    );

    flash($ok ? 'success' : 'danger', $ok ? "Created return request for order #{$id}." : "Failed to create return request for order #{$id}.");
    redirect_back();
}

if ($action === 'return_update') {
    $returnId = (int)($_POST['return_id'] ?? 0);
    if ($returnId <= 0) {
        flash('danger', 'Invalid return id.');
        redirect_back();
    }

    $status = trim((string)($_POST['return_status'] ?? ''));
    $adminNote = trim((string)($_POST['admin_note'] ?? ''));
    $refundAmountRaw = trim((string)($_POST['refund_amount'] ?? ''));
    $refundAmount = ($refundAmountRaw === '') ? null : (float)$refundAmountRaw;

    $ok = update_return($conn, $returnId, [
        'status' => $status,
        'admin_note' => $adminNote,
        'refund_amount' => $refundAmount,
    ]);

    flash($ok ? 'success' : 'danger', $ok ? "Updated return #{$returnId}." : "Failed to update return #{$returnId} (invalid transition or data).");
    redirect_back();
}

flash('danger', 'Unknown action.');
redirect_back();
