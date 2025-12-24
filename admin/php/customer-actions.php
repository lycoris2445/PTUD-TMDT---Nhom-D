<?php
declare(strict_types=1);


session_start();


// Kiểm tra quyền admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || !isset($_SESSION['admin_role'])) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Unauthorized']));
}


$allowed_roles = ['super_admin', 'operation_staff'];
if (!in_array($_SESSION['admin_role'], $allowed_roles)) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Access denied']));
}


require_once __DIR__ . '/../includes/function-customer-management.php';


try {
    /** @var PDO $conn */
    $conn = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection error: " . htmlspecialchars($e->getMessage()));
}


function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}


function redirect_back(): void {
    $qs = (string)($_POST['return_query'] ?? '');
    header('Location: customer-management.php' . ($qs ? ('?' . $qs) : ''));
    exit;
}


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
    flash('danger', 'Invalid customer id.');
    redirect_back();
}


// Lấy admin id nếu có
$changedBy = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;


// Suspend với lý do + confirm
if ($action === 'suspend_with_reason') {
    $reason = (string)($_POST['reason'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');


    if (trim($reason) === '') {
        flash('danger', 'Please enter a suspend reason.');
        redirect_back();
    }


    if ($confirm !== 'yes') {
        flash('danger', 'You have not confirmed (Yes) to suspend.');
        redirect_back();
    }


    $ok = suspend_customer_with_reason($conn, $id, $reason, $changedBy);
    flash($ok ? 'success' : 'danger', $ok ? "Suspended customer #{$id}." : "Failed to suspend.");
    redirect_back();
}


// Activate có log
if ($action === 'activate_with_log') {
    $ok = activate_customer($conn, $id, $changedBy);
    flash($ok ? 'success' : 'danger', $ok ? "Activated customer #{$id}." : "Failed to activate.");
    redirect_back();
}


// Set status thủ công (nếu cần)
if ($action === 'set_status') {
    $status = trim((string)($_POST['status'] ?? ''));
    $ok = set_customer_status($conn, $id, $status);
    flash($ok ? 'success' : 'danger', $ok ? "Updated customer #{$id} status." : "Failed to update status.");
    redirect_back();
}


flash('danger', 'Unknown action.');
redirect_back();



