<?php
$orders = [
  ["code" => "DH10245", "customer" => "Nguyễn An", "date" => "2025-12-12", "total" => "1.250.000₫", "status" => "Paid", "method" => "COD"],
  ["code" => "DH10244", "customer" => "Trần Bình", "date" => "2025-12-12", "total" => "820.000₫", "status" => "Pending", "method" => "Bank"],
  ["code" => "DH10243", "customer" => "Lê Hoa", "date" => "2025-12-11", "total" => "2.150.000₫", "status" => "Cancelled", "method" => "COD"],
  ["code" => "DH10242", "customer" => "Phạm Khang", "date" => "2025-12-11", "total" => "640.000₫", "status" => "Paid", "method" => "COD"],
  ["code" => "DH10241", "customer" => "Vũ Mai", "date" => "2025-12-10", "total" => "1.820.000₫", "status" => "Pending", "method" => "Bank"],
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
  <title>Quản lý đơn hàng</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="admin-wrapper">
  <aside class="sidebar">
    <div class="sidebar-logo">Darling Admin</div>
    <ul class="sidebar-menu">
      <li onclick="location.href='dashboard.php'">Dashboard</li>
      <li class="active">Orders</li>
      <li onclick="location.href='customer.php'">Customers</li>
      <li>Products</li>
      <li>Reports</li>
      <li>Settings</li>
    </ul>
  </aside>

  <main class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="page-title mb-0">Đơn hàng</h1>
      <div class="d-flex gap-2">
        <select class="form-select form-select-sm" style="width:150px;">
          <option>Lọc trạng thái</option>
          <option>Paid</option>
          <option>Pending</option>
          <option>Cancelled</option>
        </select>
        <input class="form-control form-control-sm" style="width:200px;" placeholder="Tìm mã hoặc khách">
        <button class="btn btn-darling btn-sm">Tạo đơn</button>
      </div>
    </div>

    <div class="card card-darling">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Mã</th>
              <th>Khách hàng</th>
              <th>Ngày</th>
              <th>Phương thức</th>
              <th class="text-end">Tổng</th>
              <th class="text-center">Trạng thái</th>
              <th class="text-end">Thao tác</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><strong><?php echo $o['code']; ?></strong></td>
              <td><?php echo $o['customer']; ?></td>
              <td><?php echo $o['date']; ?></td>
              <td><?php echo $o['method']; ?></td>
              <td class="text-end"><?php echo $o['total']; ?></td>
              <td class="text-center"><?php echo statusBadge($o['status']); ?></td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <button class="btn btn-outline-secondary">Xem</button>
                  <button class="btn btn-outline-secondary">Sửa</button>
                  <button class="btn btn-outline-danger">Huỷ</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>