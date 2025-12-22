<?php
session_start();
//chặn những người chưa đăng nhập hoặc không đúng quyền
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || !isset($_SESSION['admin_role'])) {
    header("Location: admin-login.php");
    exit;
}
$allowed_roles = ['super_admin', 'operation_staff'];

if (!in_array($_SESSION['admin_role'], $allowed_roles)) {
    header("Location: admin-login.php");
    exit;
}

// Connect to database
try {
    $conn = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection error: " . htmlspecialchars($e->getMessage()));
}

// ===== LOAD STATISTICS FROM DATABASE =====

// 1. Total Revenue (this month)
$stmt = $conn->query("
    SELECT COALESCE(SUM(final_amount), 0) as total_revenue,
           COUNT(*) as total_orders
    FROM ORDERS 
    WHERE status IN ('completed', 'shipping', 'shipped', 'on_hold', 'processing', 'awaiting_pickup')
      AND MONTH(created_at) = MONTH(CURRENT_DATE())
      AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$revenue_data = $stmt->fetch(PDO::FETCH_ASSOC);
$current_revenue = (float)$revenue_data['total_revenue'];
$current_orders = (int)$revenue_data['total_orders'];

// Revenue last month for comparison
$stmt = $conn->query("
    SELECT COALESCE(SUM(final_amount), 0) as total_revenue
    FROM ORDERS 
    WHERE status IN ('completed', 'shipping', 'shipped', 'on_hold', 'processing', 'awaiting_pickup')
      AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
      AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
");
$last_revenue = (float)$stmt->fetchColumn();
$revenue_delta = $last_revenue > 0 ? (($current_revenue - $last_revenue) / $last_revenue * 100) : 0;

// 2. Orders this month vs last month
$stmt = $conn->query("
    SELECT COUNT(*) FROM ORDERS 
    WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
      AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
");
$last_orders = (int)$stmt->fetchColumn();
$orders_delta = $last_orders > 0 ? (($current_orders - $last_orders) / $last_orders * 100) : 0;

// 3. New Customers this month
$stmt = $conn->query("
    SELECT COUNT(*) as new_customers FROM ACCOUNTS
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
      AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$current_customers = (int)$stmt->fetchColumn();

$stmt = $conn->query("
    SELECT COUNT(*) FROM ACCOUNTS
    WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
      AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
");
$last_customers = (int)$stmt->fetchColumn();
$customers_delta = $last_customers > 0 ? (($current_customers - $last_customers) / $last_customers * 100) : 0;

// 4. Conversion Rate (orders / visitors) - simplified as orders/customers ratio
$stmt = $conn->query("SELECT COUNT(*) FROM ACCOUNTS WHERE MONTH(created_at) = MONTH(CURRENT_DATE())");
$visitors = max(1, (int)$stmt->fetchColumn());
$conversion_rate = ($current_orders / $visitors) * 100;

$stats = [
  [
    "label" => "Doanh thu", 
    "value" => '$' . number_format($current_revenue, 2), 
    "delta" => ($revenue_delta >= 0 ? '+' : '') . number_format($revenue_delta, 1) . '% MoM'
  ],
  [
    "label" => "Đơn hàng", 
    "value" => number_format($current_orders), 
    "delta" => ($orders_delta >= 0 ? '+' : '') . number_format($orders_delta, 1) . '% MoM'
  ],
  [
    "label" => "Khách hàng mới", 
    "value" => number_format($current_customers), 
    "delta" => ($customers_delta >= 0 ? '+' : '') . number_format($customers_delta, 1) . '% MoM'
  ],
  [
    "label" => "Tỉ lệ chuyển đổi", 
    "value" => number_format($conversion_rate, 1) . '%', 
    "delta" => 'Tỷ lệ đơn hàng/khách'
  ],
];

// ===== LOAD RECENT ORDERS FROM DATABASE =====
$stmt = $conn->prepare("
    SELECT 
        o.id,
        o.tracking_number,
        o.final_amount,
        o.status,
        o.created_at,
        a.full_name as customer_name
    FROM ORDERS o
    LEFT JOIN ACCOUNTS a ON o.account_id = a.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentOrders = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recentOrders[] = [
        'id' => $row['id'],
        'code' => $row['tracking_number'] ?? 'N/A',
        'customer' => $row['customer_name'] ?? 'Guest',
        'total' => '$' . number_format((float)$row['final_amount'], 2),
        'status' => ucfirst($row['status']),
        'date' => date('Y-m-d', strtotime($row['created_at']))
    ];
}

function statusBadge($status) {
  $status_lower = strtolower($status);
  if (in_array($status_lower, ['completed', 'shipped'])) {
    return '<span class="badge-status badge-paid">' . htmlspecialchars($status) . '</span>';
  } elseif (in_array($status_lower, ['pending', 'new', 'on_hold', 'processing', 'awaiting_pickup', 'shipping'])) {
    return '<span class="badge-status badge-pending">' . htmlspecialchars($status) . '</span>';
  } else {
    return '<span class="badge-status badge-cancel">' . htmlspecialchars($status) . '</span>';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/customer-management.css">
  <link rel="stylesheet" href="../css/admin-main.css">
</head>
<body>

<div class="admin-wrapper">
  <?php include '../includes/admin-sidebar.php'; ?>

  <main class="content">
    <h2 class="page-title">Dashboard</h2>

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
            <a href="order-management.php" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
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
                  <tr style="cursor: pointer;" onclick="window.location.href='order-management.php?view=<?php echo $o['id']; ?>'">
                    <td><strong><?php echo htmlspecialchars($o['code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($o['customer']); ?></td>
                    <td><?php echo htmlspecialchars($o['date']); ?></td>
                    <td class="text-end"><?php echo htmlspecialchars($o['total']); ?></td>
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
            <a href="order-management.php" class="btn btn-darling">Quản lý đơn hàng</a>
            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'operation_staff'): ?>
            <a href="product-management.php" class="btn btn-outline-secondary">Quản lý sản phẩm</a>
            <?php endif; ?>
            <a href="customer-management.php" class="btn btn-outline-secondary">Quản lý khách hàng</a>
            <a href="staff-management.php" class="btn btn-outline-primary">Quản lý nhân viên</a>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>