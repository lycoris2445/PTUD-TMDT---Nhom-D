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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/customer-management.css">
  <link rel="stylesheet" href="../css/admin-main.css">
</head>
<body>

<div class="admin-wrapper">
  <?php include '../includes/admin-sidebar.php'; ?>

  <main class="content">
    <h2 class="page-title">Orders</h2>

    <div class="card customers-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Orders</h5>
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

      <div class="table-responsive">
        <table class="table customers-table">
          <thead>
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

      <div class="card-footer d-flex justify-content-between align-items-center">
        <span>Showing 1–5 of 5</span>
        <nav>
          <ul class="pagination mb-0">
            <li class="page-item active">
              <a class="page-link" href="?page=1">1</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>