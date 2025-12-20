<?php
$stats = [
  ["label" => "Doanh thu", "value" => "₫1.2B", "delta" => "+8.4% MoM"],
  ["label" => "Đơn hàng", "value" => "1,245", "delta" => "+3.1% MoM"],
  ["label" => "Khách hàng mới", "value" => "312", "delta" => "+5.6% MoM"],
  ["label" => "Tỉ lệ chuyển đổi", "value" => "3.4%", "delta" => "+0.3 điểm"],
];

$recentOrders = [
  ["code" => "DH10245", "customer" => "Nguyễn An", "total" => "1.250.000₫", "status" => "Paid", "date" => "2025-12-12"],
  ["code" => "DH10244", "customer" => "Trần Bình", "total" => "820.000₫", "status" => "Pending", "date" => "2025-12-12"],
  ["code" => "DH10243", "customer" => "Lê Hoa", "total" => "2.150.000₫", "status" => "Cancelled", "date" => "2025-12-11"],
  ["code" => "DH10242", "customer" => "Phạm Khang", "total" => "640.000₫", "status" => "Paid", "date" => "2025-12-11"],
];

function statusBadge($status) {
  return match($status) {
    'Paid' => '<span class="badge-status badge-paid">Paid</span>',
    'Pending' => '<span class="badge-status badge-pending">Pending</span>',
    default => '<span class="badge-status badge-cancel">Cancelled</span>'
  };
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="admin-wrapper">
  <aside class="sidebar">
    <div class="sidebar-logo">Darling Admin</div>
    <ul class="sidebar-menu">
      <li class="active">Dashboard</li>
      <li onclick="location.href='orders.php'">Orders</li>
      <li onclick="location.href='customer.php'">Customers</li>
      <li>Products</li>
      <li>Reports</li>
      <li>Settings</li>
    </ul>
  </aside>

  <main class="content">
    <h1 class="page-title">Dashboard</h1>

    <div class="row g-3 mb-4">
      <?php foreach ($stats as $stat): ?>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small"><?php echo $stat['label']; ?></span>
              <span class="stat-icon">
                <i class="bi bi-activity"></i>
              </span>
            </div>
            <div class="h5 mb-1"><?php echo $stat['value']; ?></div>
            <div class="text-success small"><?php echo $stat['delta']; ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card card-darling">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Đơn hàng gần đây</h5>
            <a href="orders.php" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Mã</th>
                    <th>Khách</th>
                    <th>Ngày</th>
                    <th class="text-end">Tổng</th>
                    <th class="text-center">Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                  <tr>
                    <td><strong><?php echo $o['code']; ?></strong></td>
                    <td><?php echo $o['customer']; ?></td>
                    <td><?php echo $o['date']; ?></td>
                    <td class="text-end"><?php echo $o['total']; ?></td>
                    <td class="text-center"><?php echo statusBadge($o['status']); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card card-darling h-100">
          <div class="card-header">
            <h5 class="mb-0">Tác vụ nhanh</h5>
          </div>
          <div class="card-body d-grid gap-2">
            <button class="btn btn-darling">Tạo đơn thủ công</button>
            <button class="btn btn-outline-secondary">Xuất báo cáo</button>
            <button class="btn btn-outline-secondary">Quản lý kho</button>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>