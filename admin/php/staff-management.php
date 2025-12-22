<?php
// --------------------
// DỮ LIỆU GIẢ LẬP (Mô phỏng Database)
// --------------------
$accounts = [
    ["id"=>1,  "email"=>"superadmin01@darling.com", "full_name"=>"Sophia Bennett",  "role"=>"super_admin", "status"=>"active", "created_at"=>"2023-11-20", "last_login"=>"2025-12-15 08:30:00"],
    ["id"=>2,  "email"=>"superadmin02@darling.com", "full_name"=>"Ethan Clarke",    "role"=>"super_admin", "status"=>"active", "created_at"=>"2023-11-21", "last_login"=>"2025-12-14 17:20:00"],
    ["id"=>3,  "email"=>"superadmin03@darling.com", "full_name"=>"Olivia Hayes",    "role"=>"super_admin", "status"=>"active", "created_at"=>"2024-11-22", "last_login"=>"2025-12-13 14:05:00"],
    ["id"=>4,  "email"=>"superadmin04@darling.com", "full_name"=>"Liam Turner",     "role"=>"super_admin", "status"=>"active", "created_at"=>"2024-11-23", "last_login"=>"2025-12-10 09:40:00"],
    ["id"=>5,  "email"=>"superadmin05@darling.com", "full_name"=>"Ava Mitchell",    "role"=>"super_admin", "status"=>"locked", "created_at"=>"2025-11-24", "last_login"=>"2025-12-01 10:00:00"],
    ["id"=>6,  "email"=>"ops01@darling.com",        "full_name"=>"Mason Reed",      "role"=>"staff",       "status"=>"active", "created_at"=>"2025-11-25", "last_login"=>"2025-12-15 11:10:00"],
    ["id"=>7,  "email"=>"ops02@darling.com",        "full_name"=>"Amelia Brooks",   "role"=>"staff",       "status"=>"active", "created_at"=>"2025-11-26", "last_login"=>"2025-12-14 16:45:00"],
    ["id"=>8,  "email"=>"ops03@darling.com",        "full_name"=>"Noah Foster",     "role"=>"staff",       "status"=>"active", "created_at"=>"2025-11-27", "last_login"=>"2025-12-12 13:30:00"],
    ["id"=>9,  "email"=>"ops04@darling.com",        "full_name"=>"Charlotte Price", "role"=>"staff",       "status"=>"locked", "created_at"=>"2025-11-28", "last_login"=>"2025-11-30 09:00:00"],
    ["id"=>10, "email"=>"ops05@darling.com",        "full_name"=>"James Collins",   "role"=>"staff",       "status"=>"active", "created_at"=>"2025-11-29", "last_login"=>"2025-12-15 15:55:00"],
];

// --------------------
// Phân trang đơn giản
// --------------------
$perPage = 6; // Hiển thị 6 người mỗi trang cho thoáng
$total = count($accounts);
$page = max(1, (int)($_GET['page'] ?? 1));
$start = ($page - 1) * $perPage;
$pagedAccounts = array_slice($accounts, $start, $perPage);
$totalPages = ceil($total / $perPage);

// Hàm hiển thị badge trạng thái
function getStatusBadge($status) {
    if ($status === 'active') return '<span class="badge bg-success">Active</span>';
    return '<span class="badge bg-danger">Locked</span>';
}

// Hàm hiển thị badge vai trò
function getRoleBadge($role) {
    if ($role === 'super_admin') return '<span class="badge bg-primary">Super Admin</span>';
    return '<span class="badge bg-info text-dark">Operation Staff</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Accounts - Darling Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/admin-main.css">
    <link rel="stylesheet" href="../css/khach_hang.css">
    
    <style>
        /* CSS riêng cho trang này một chút */
        .btn-action { margin-right: 5px; }
        .table td { vertical-align: middle; }
        .role-col { width: 150px; }
        .status-col { width: 100px; }
        .action-col { width: 180px; text-align: right; }
        
        /* Highlight dòng bị khóa để dễ nhìn */
        tr.row-locked { background-color: #fff5f5; }
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

        <div class="card customers-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Staff List</h5>
                <div class="d-flex gap-2">
                     <select class="form-select form-select-sm" style="width: 150px;">
                        <option value="">All Roles</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                    <input type="text" class="form-control form-control-sm" placeholder="Search name or email..." style="width: 200px;">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover customers-table align-middle">
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
                    <?php foreach ($pagedAccounts as $acc): ?>
                        <tr class="<?= $acc['status'] === 'locked' ? 'row-locked' : '' ?>">
                            <td>#<?= $acc['id'] ?></td>
                            <td class="fw-bold"><?= $acc['full_name'] ?></td>
                            <td><?= $acc['email'] ?></td>
                            <td><?= getRoleBadge($acc['role']) ?></td>
                            <td><?= getStatusBadge($acc['status']) ?></td>
                            <td class="text-muted small">
                                <i class="bi bi-clock"></i> <?= $acc['last_login'] ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary btn-action" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                
                                <?php if($acc['status'] === 'active'): ?>
                                    <button class="btn btn-sm btn-outline-warning btn-action" title="Lock Account">
                                        <i class="bi bi-lock-fill"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-success btn-action" title="Unlock Account">
                                        <i class="bi bi-unlock-fill"></i>
                                    </button>
                                <?php endif; ?>

                                <button class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="text-muted small">Showing <?= $start + 1 ?>–<?= min($start + $perPage, $total) ?> of <?= $total ?> accounts</span>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
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