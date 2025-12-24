<?php
session_start();

// 1. Kiểm tra đăng nhập - cho phép cả super_admin và operation_staff
if (!isset($_SESSION['is_admin']) || 
    $_SESSION['is_admin'] !== true || 
    !isset($_SESSION['admin_role'])) {
    
    header("Location: admin-login.php");
    exit;
}

$allowed_roles = ['super_admin'];
if (!in_array($_SESSION['admin_role'], $allowed_roles)) {
    header("Location: admin-login.php");
    exit;
}

// 2. Kết nối Database
try {
    $pdo = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection error: " . htmlspecialchars($e->getMessage()));
}

// 3. Xử lý Phân trang & Tìm kiếm
$perPage = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';

// 4. Thiết lập tham số và SQL - fix để dùng được cùng parameter nhiều lần
$searchPattern = "%$search%";
$whereSql = " WHERE (a.full_name LIKE ? OR a.email LIKE ?)";
$params = [$searchPattern, $searchPattern];

if ($role_filter !== '') {
    $whereSql .= " AND r.name = ?";
    $params[] = $role_filter;
}

// 5. Truy vấn lấy danh sách nhân viên
$sql = "SELECT a.id, a.email, a.full_name, a.status, a.created_at, a.last_login_at, r.name as role_name
        FROM ACCOUNTS a
        JOIN ACCOUNT_ROLES ar ON a.id = ar.account_id
        JOIN ROLES r ON ar.role_id = r.id" 
        . $whereSql . 
        " ORDER BY a.id ASC LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accounts = $stmt->fetchAll();

// 6. Tính tổng số bản ghi
$countSql = "SELECT COUNT(*) FROM ACCOUNTS a 
             JOIN ACCOUNT_ROLES ar ON a.id = ar.account_id
             JOIN ROLES r ON ar.role_id = r.id" . $whereSql;

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalItems / $perPage);

// 7. Hàm hiển thị
function getStatusBadge($status) {
    if ($status === 'active') return '<span class="badge bg-success">Active</span>';
    return '<span class="badge bg-danger">Suspended</span>';
}

function getRoleBadge($role) {
    if ($role === 'super_admin') return '<span class="badge bg-primary">Super Admin</span>';
    return '<span class="badge bg-info text-dark">Staff</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Management - Darling Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/admin-main.css">
    <style>
        .btn-action { margin-right: 5px; }
        .table td { vertical-align: middle; }
        tr.row-locked { background-color: #fff5f5; color: #999; }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php include '../includes/admin-sidebar.php'; ?>

    <main class="admin-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title">Staff Management</h2>
            <button class="btn btn-primary">
                <i class="bi bi-person-plus-fill"></i> Add New Account
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select form-select-sm">
                            <option value="">All Roles</option>
                            <option value="super_admin" <?= $role_filter === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                            <option value="operation_staff" <?= $role_filter === 'operation_staff' ? 'selected' : '' ?>>Operation Staff</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-dark w-100">Filter</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No staff found.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($accounts as $acc): ?>
                        <tr class="<?= $acc['status'] === 'suspended' ? 'row-locked' : '' ?>">
                            <td>#<?= $acc['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($acc['full_name']) ?></td>
                            <td><?= htmlspecialchars($acc['email']) ?></td>
                            <td><?= getRoleBadge($acc['role_name']) ?></td>
                            <td><?= getStatusBadge($acc['status']) ?></td>
                            <td class="text-muted small">
                                <i class="bi bi-clock"></i> <?= $acc['last_login_at'] ? date('d/m/Y H:i', strtotime($acc['last_login_at'])) : 'Never' ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary btn-action" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?> of <?= $totalItems ?>
                </span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= $role_filter ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>