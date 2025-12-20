<?php
// 1. Cấu hình kết nối Database
$host = 'localhost';
$db   = 'Darling_cosmetics';
$user = 'root'; // Thay bằng username của bạn
$pass = '';     // Thay bằng password của bạn
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // 2. Truy vấn dữ liệu theo yêu cầu
    // Lấy thông tin từ ACCOUNTS, tính tổng đơn và tổng chi từ ORDERS, lấy địa chỉ từ ADDRESSES
    $sql = "SELECT
                a.full_name,
                a.email,
                a.created_at,
                a.last_login_at,
                (SELECT ad.detail_address FROM ADDRESSES ad WHERE ad.account_id = a.id AND ad.is_default = 1 LIMIT 1) as detail_address,
                COUNT(o.id) as orders,
                IFNULL(SUM(o.final_amount), 0) as total_spent
            FROM ACCOUNTS a
            INNER JOIN ACCOUNT_ROLES ar ON a.id = ar.account_id
            INNER JOIN ROLES r ON ar.role_id = r.id
            LEFT JOIN ORDERS o ON a.id = o.account_id
            WHERE r.name = 'user'
            GROUP BY a.id, a.full_name, a.email, a.created_at, a.last_login_at";

    $stmt = $pdo->query($sql);
    $customers = $stmt->fetchAll();

} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// --------------------
// Sorting (Giữ nguyên logic sắp xếp từ file cũ của bạn)
// --------------------
$sort = $_GET['sort'] ?? 'full_name';
$order = $_GET['order'] ?? 'asc';

usort($customers, function ($a, $b) use ($sort, $order) {
    $valA = $a[$sort] ?? '';
    $valB = $b[$sort] ?? '';

    if ($order === 'asc') {
        return $valA <=> $valB;
    }
    return $valB <=> $valA;
});

// --------------------
// Pagination
// --------------------
$perPage = 5;
$total = count($customers);
$page = max(1, (int)($_GET['page'] ?? 1));
$start = ($page - 1) * $perPage;
$pagedCustomers = array_slice($customers, $start, $perPage);
$totalPages = ceil($total / $perPage);

function usd($n) {
    return "$" . number_format($n, 2, '.', ',');
}

function sortLink($column, $label, $currentSort, $currentOrder) {
    $order = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    return "<a href='?sort=$column&order=$order' style='text-decoration: none; color: inherit;'>$label " . ($currentSort === $column ? ($currentOrder === 'asc' ? '↑' : '↓') : '') . "</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/khach_hang.css">
</head>
<body>

<div class="admin-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">Darling</div>
        <ul class="sidebar-menu">
            <li onclick="location.href='dashboard.php'">Dashboard</li>
            <li onclick="location.href='orders.php'">Orders</li>
            <li class="active">Customers</li>
            <li onclick="location.href='products.php'">Products</li>
            <li onclick="location.href='admin_accounts.php'">Reports</li>
            <li>Settings</li>
        </ul>
    </aside>

    <main class="content">
        <h2 class="page-title">Customers</h2>

        <div class="card customers-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Customer List</h5>
                <form class="d-flex">
                <input type="text"
                       class="form-control search-input"
                       placeholder="Search customers..."
                       id="search-input"
                       style="width: 300px;"
                       autocomplete="off">
            </div>

            <div class="table-responsive">
                <table class="table table-hover customers-table">
                    <thead>
                    <tr>
                        <th><?= sortLink('full_name', 'Full Name', $sort, $order) ?></th>
                        <th>Email</th>
                        <th><?= sortLink('created_at', 'Registered', $sort, $order) ?></th>
                        <th><?= sortLink('last_login_at', 'Last Login', $sort, $order) ?></th>
                        <th><?= sortLink('orders', 'Orders', $sort, $order) ?></th>
                        <th><?= sortLink('total_spent', 'Total Spent', $sort, $order) ?></th>
                        <th>Address</th>
                    </tr>
                    </thead>
                    <tbody id="customer-table-body">
                    <?php if (!empty($pagedCustomers)): ?>
                        <?php foreach ($pagedCustomers as $c): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($c['full_name']) ?></td>
                                <td><a href="mailto:<?= $c['email'] ?>"><?= htmlspecialchars($c['email'] ?? '') ?></a></td>
                                <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                                <td><?= $c['last_login_at'] ? date('d/m/Y H:i', strtotime($c['last_login_at'])) : '<span class="text-muted">Never</span>' ?></td>
                                <td class="text-center"><?= $c['orders'] ?></td>
                                <td class="text-success fw-bold"><?= usd($c['total_spent']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($c['detail_address'] ?? 'No address') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No customers found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="text-muted small">Showing <?= $start + 1 ?>–<?= min($start + $perPage, $total) ?> of <?= $total ?> customers</span>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </main>
</div>

<script src="../js/khach_hang.js"></script>
</body>
</html>