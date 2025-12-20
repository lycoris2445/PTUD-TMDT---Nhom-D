<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/function_customer_management.php';

try {
    /** @var PDO $conn */
    $conn = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection error: " . htmlspecialchars($e->getMessage()));
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((float)$n, 0, '.', ','); }

function build_query(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null || $v === 0) unset($params[$k]);
    }
    return http_build_query($params);
}

function sort_link(string $column, string $label, string $currentSort, string $currentOrder): string {
    $nextOrder = ($currentSort === $column && strtolower($currentOrder) === 'asc') ? 'desc' : 'asc';
    $arrow = ($currentSort === $column) ? (strtolower($currentOrder) === 'asc' ? ' ↑' : ' ↓') : '';
    $qs = build_query(['sort' => $column, 'order' => $nextOrder, 'page' => 1]);
    return "<a href='?{$qs}' style='text-decoration:none'>{$label}{$arrow}</a>";
}

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? '')); // active|suspended|''

$sort = (string)($_GET['sort'] ?? 'full_name');
$order = (string)($_GET['order'] ?? 'asc');

$viewId = (int)($_GET['view'] ?? 0);

$filters = ['q' => $q, 'status' => $status];

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = count_customers($conn, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$customers = fetch_customers($conn, $filters, $sort, $order, $perPage, $offset);

$detail = null;
if ($viewId > 0) {
    $detail = get_customer_by_id($conn, $viewId);
}

$returnQuery = build_query(['page' => $page]);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Customer Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin-main.css">
  <link rel="stylesheet" href="../css/customer-management.css">

</head>
<body>
<div class="admin-wrapper">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="admin-main">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="page-title mb-0">Customer Management</h2>
      <button class="btn btn-primary" type="button" onclick="alert('Admin không tạo customer trực tiếp.');">
        + Add New Account
      </button>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Search (name/email)</label>
            <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Search customers...">
          </div>

          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="">-- All --</option>
              <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>active</option>
              <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>suspended</option>
            </select>
          </div>

          <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-dark w-100" type="submit">Filter</button>
            <a class="btn btn-outline-secondary w-100" href="customer-management.php">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">

        <div class="d-flex justify-content-between mb-2">
          <div class="text-muted">Total: <strong><?= (int)$total ?></strong> customers</div>
          <div class="text-muted">Page <?= (int)$page ?> / <?= (int)$totalPages ?></div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th><?= sort_link('full_name', 'Full Name', $sort, $order) ?></th>
                <th><?= sort_link('email', 'Email', $sort, $order) ?></th>
                <th><?= sort_link('created_at', 'Registered', $sort, $order) ?></th>
                <th><?= sort_link('last_login_at', 'Last Login', $sort, $order) ?></th>
                <th class="text-center"><?= sort_link('orders', 'Orders', $sort, $order) ?></th>
                <th class="text-end"><?= sort_link('total_spent', 'Total Spent', $sort, $order) ?></th>
                <th>Address</th>
                <th><?= sort_link('status', 'Status', $sort, $order) ?></th>
                <th style="width:260px;">Actions</th>
              </tr>
            </thead>

            <tbody>
            <?php if ($customers): ?>
              <?php foreach ($customers as $c): ?>
                <?php $cid = (int)$c['id']; ?>
                <tr>
                  <td class="fw-bold"><?= h($c['full_name']) ?></td>
                  <td><a href="mailto:<?= h($c['email']) ?>"><?= h($c['email']) ?></a></td>
                  <td><?= h(date('d/m/Y', strtotime((string)$c['created_at']))) ?></td>
                  <td>
                    <?= $c['last_login_at']
                      ? h(date('d/m/Y H:i', strtotime((string)$c['last_login_at'])))
                      : '<span class="text-muted">Never</span>' ?>
                  </td>
                  <td class="text-center"><?= (int)$c['orders'] ?></td>
                  <td class="text-end text-success fw-bold"><?= money($c['total_spent']) ?></td>
                  <td class="small text-muted"><?= h($c['detail_address'] ?? 'No address') ?></td>
                  <td><span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h($c['status']) ?></span></td>

                  <td>
                    <div class="d-flex gap-2">
                      <a class="btn btn-sm btn-outline-primary"
                         href="customer-management.php?<?= h(build_query(['view' => $cid, 'page' => $page])) ?>">
                        View
                      </a>

                      <form method="post" action="customer-actions.php">
                        <input type="hidden" name="action" value="set_status">
                        <input type="hidden" name="id" value="<?= $cid ?>">
                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                        <input type="hidden" name="return_query" value="<?= h($returnQuery) ?>">
                        <input type="hidden" name="status" value="<?= $c['status'] === 'active' ? 'suspended' : 'active' ?>">
                        <button class="btn btn-sm btn-outline-warning" type="submit">
                          <?= $c['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                        </button>
                      </form>

                      <form method="post" action="customer-actions.php"
                            onsubmit="return confirm('Delete customer #<?= $cid ?>? Có thể lỗi nếu còn ràng buộc FK.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $cid ?>">
                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                        <input type="hidden" name="return_query" value="<?= h($returnQuery) ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>

                <?php if ($detail && (int)$detail['id'] === $cid): ?>
                  <tr class="details-row">
                    <td colspan="9">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Customer #<?= (int)$detail['id'] ?> details</h6>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="customer-management.php?<?= h(build_query(['view' => 0, 'page' => $page])) ?>">
                          Close
                        </a>
                      </div>

                      <div class="row g-3">
                        <div class="col-md-6">
                          <div class="p-3 border rounded bg-white">
                            <div><strong>Full name:</strong> <?= h($detail['full_name']) ?></div>
                            <div><strong>Email:</strong> <?= h($detail['email']) ?></div>
                            <div><strong>Phone:</strong> <?= h($detail['phone_number'] ?? '-') ?></div>
                            <div><strong>Status:</strong> <?= h($detail['status']) ?></div>
                            <div><strong>Registered:</strong> <?= h((string)$detail['created_at']) ?></div>
                            <div><strong>Last login:</strong> <?= h((string)($detail['last_login_at'] ?? 'Never')) ?></div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="p-3 border rounded bg-white">
                            <div><strong>Orders:</strong> <?= (int)$detail['orders'] ?></div>
                            <div><strong>Total spent:</strong> <?= money($detail['total_spent']) ?></div>
                            <div class="mt-2"><strong>Default address:</strong></div>
                            <pre class="addr p-2 bg-light rounded"><?= h($detail['detail_address'] ?? 'No address') ?></pre>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>

              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="9" class="text-center text-muted py-4">No customers found.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <nav class="d-flex justify-content-end">
          <ul class="pagination mb-0">
            <?php $prev = max(1, $page - 1); $next = min($totalPages, $page + 1); ?>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="customer-management.php?<?= h(build_query(['page' => $prev])) ?>">Prev</a>
            </li>
            <li class="page-item disabled"><span class="page-link">Page <?= (int)$page ?></span></li>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="customer-management.php?<?= h(build_query(['page' => $next])) ?>">Next</a>
            </li>
          </ul>
        </nav>

      </div>
    </div>

  </main>
</div>
</body>
</html>
