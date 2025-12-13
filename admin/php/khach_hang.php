<?php
// --------------------
// Fake data (replace with DB)
// --------------------
$customers = [
    ["name"=>"testsite","username"=>"testsite","last_active"=>"2025-11-15","registered"=>"2025-08-18","email"=>"dev-email@wpengine.local","orders"=>3,"total_spent"=>38552400,"aov"=>12850800,"country"=>"VN","city"=>"Ho Chi Minh City","region"=>"HCM"],
    ["name"=>"Alice","username"=>"alice01","last_active"=>"2025-11-10","registered"=>"2025-06-12","email"=>"alice@gmail.com","orders"=>1,"total_spent"=>5200000,"aov"=>5200000,"country"=>"VN","city"=>"Da Nang","region"=>"DN"],
    ["name"=>"Bob","username"=>"bob88","last_active"=>"2025-10-22","registered"=>"2025-05-01","email"=>"bob@yahoo.com","orders"=>5,"total_spent"=>21000000,"aov"=>4200000,"country"=>"US","city"=>"New York","region"=>"NY"],
];

// --------------------
// Sorting
// --------------------
$sort = $_GET['sort'] ?? 'name';
$order = $_GET['order'] ?? 'asc';

usort($customers, function ($a, $b) use ($sort, $order) {
    if ($order === 'asc') {
        return $a[$sort] <=> $b[$sort];
    }
    return $b[$sort] <=> $a[$sort];
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

function vnd($n) {
    return number_format($n, 0, ',', '.') . " ₫";
}

function sortLink($column, $label, $currentSort, $currentOrder) {
    $order = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    return "<a href='?sort=$column&order=$order'>$label</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/khach_hang.css">
</head>
<body>

<div class="admin-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">Darling</div>
        <ul class="sidebar-menu">
            <li>Dashboard</li>
            <li class="active">Customers</li>
            <li>Orders</li>
            <li>Products</li>
            <li>Reports</li>
            <li>Settings</li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">
        <h2 class="page-title">Customers</h2>

        <div class="card customers-card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">Customers</h5>
                <input type="text" 
                       class="form-control search-input" 
                       placeholder="Search customers..." 
                       id="search-input"
                       autocomplete="off">
            </div>

            <div class="table-responsive">
                <table class="table customers-table">
                    <thead>
                    <tr>
                        <th><?= sortLink('name','Name',$sort,$order) ?></th>
                        <th><?= sortLink('username','Username',$sort,$order) ?></th>
                        <th><?= sortLink('last_active','Last active',$sort,$order) ?></th>
                        <th><?= sortLink('registered','Date registered',$sort,$order) ?></th>
                        <th>Email</th>
                        <th><?= sortLink('orders','Orders',$sort,$order) ?></th>
                        <th><?= sortLink('total_spent','Total spend',$sort,$order) ?></th>
                        <th><?= sortLink('aov','AOV',$sort,$order) ?></th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Region</th>
                    </tr>
                    </thead>
                    <tbody id="customer-table-body">
                    <?php foreach ($pagedCustomers as $c): ?>
                        <tr>
                            <td><?= $c['name'] ?></td>
                            <td><?= $c['username'] ?></td>
                            <td><?= $c['last_active'] ?></td>
                            <td><?= $c['registered'] ?></td>
                            <td><a href="mailto:<?= $c['email'] ?>"><?= $c['email'] ?></a></td>
                            <td><?= $c['orders'] ?></td>
                            <td><?= vnd($c['total_spent']) ?></td>
                            <td><?= vnd($c['aov']) ?></td>
                            <td><?= $c['country'] ?></td>
                            <td><?= $c['city'] ?></td>
                            <td><?= $c['region'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="card-footer d-flex justify-content-between align-items-center">
                <span>Showing <?= $start + 1 ?>–<?= min($start + $perPage, $total) ?> of <?= $total ?></span>

                <nav>
                    <ul class="pagination mb-0">
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