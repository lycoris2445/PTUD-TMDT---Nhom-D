<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/function-order-management.php';

try {
    /** @var PDO $conn */
    $conn = require __DIR__ . '/../../config/db_connect.php';
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection error: " . htmlspecialchars($e->getMessage()));
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Filters
$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

// UI state (inline view/edit)
$viewId = (int)($_GET['view'] ?? 0);
$editId = (int)($_GET['edit'] ?? 0);

// Pagination
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));

$filters = ['q' => $q, 'status' => $status];

$total = count_orders($conn, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;
$orders = fetch_orders($conn, $filters, $perPage, $offset);

$statuses = order_statuses();

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((float)$n, 0, '.', ','); }
function build_query(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null || $v === 0) unset($params[$k]);
    }
    return http_build_query($params);
}

// Load detail once if needed
$detailId = $editId > 0 ? $editId : ($viewId > 0 ? $viewId : 0);
$detailOrder = null;
$detailItems = [];
$detailHistory = [];

if ($detailId > 0) {
    $detailOrder = get_order_by_id($conn, $detailId);
    if ($detailOrder) {
        $detailItems = get_order_items($conn, $detailId);
        $detailHistory = get_order_history($conn, $detailId);
    }
}

// return query for actions (keep current filter/page)
$returnQuery = build_query(['view' => $viewId, 'edit' => $editId, 'page' => $page]);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Order Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/admin-main.css">
  <link rel="stylesheet" href="../css/order-management.css">
</head>

<body>
<div class="admin-wrapper">
  <!-- Sidebar -->
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
  <!-- Main -->
  <main class="admin-main">
    <div class="container py-4">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Orders</h3>

        <a class="btn btn-primary" href="#" onclick="alert('Bạn chưa cung cấp flow tạo order (chọn account, thêm items, tính tiền...). Nếu bạn muốn mình sẽ code phần Create luôn.'); return false;">
          + Create Order
        </a>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
      <?php endif; ?>

      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
              <label class="form-label">Search (Tracking number)</label>
              <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="VD: TRK-2025-0001">
            </div>

            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="">-- All --</option>
                <?php foreach ($statuses as $key => $label): ?>
                  <option value="<?= h($key) ?>" <?= $status === $key ? 'selected' : '' ?>>
                    <?= h($label) ?> (<?= h($key) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
              <button class="btn btn-dark w-100" type="submit">Filter</button>
              <a class="btn btn-outline-secondary w-100" href="order-management.php">Reset</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <div class="text-muted">Total: <strong><?= (int)$total ?></strong> orders</div>
            <div class="text-muted">Page <?= (int)$page ?> / <?= (int)$totalPages ?></div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:80px;">ID</th>
                  <th>Tracking</th>
                  <th style="width:120px;">Account</th>
                  <th class="text-end">Total</th>
                  <th class="text-end">Ship Fee</th>
                  <th class="text-end">Final</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th style="width:240px;">Actions</th>
                </tr>
              </thead>

              <tbody>
              <?php if ($orders): ?>
                <?php foreach ($orders as $o): ?>
                  <?php
                    $oid = (int)$o['id'];
                    $isDetail = ($detailOrder && $detailId === $oid);
                  ?>
                  <tr>
                    <td>#<?= $oid ?></td>
                    <td><?= h($o['tracking_number'] ?: '-') ?></td>
                    <td><?= $o['account_id'] !== null ? ('#' . (int)$o['account_id']) : '-' ?></td>

                    <td class="text-end"><?= money($o['total_amount']) ?></td>
                    <td class="text-end"><?= money($o['shipping_fee']) ?></td>
                    <td class="text-end fw-semibold"><?= money($o['final_amount']) ?></td>

                    <td><span class="badge bg-secondary badge-status"><?= h((string)$o['status']) ?></span></td>
                    <td><?= h((string)$o['created_at']) ?></td>

                    <td>
                      <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary"
                          href="order-management.php?<?= h(build_query(['view' => $oid, 'edit' => 0, 'page' => $page])) ?>">
                          View
                        </a>

                        <a class="btn btn-sm btn-outline-warning"
                          href="order-management.php?<?= h(build_query(['edit' => $oid, 'view' => 0, 'page' => $page])) ?>">
                          Edit
                        </a>

                        <form method="post" action="order-actions.php"
                              onsubmit="return confirm('Delete order #<?= $oid ?>? This cannot be undone.');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= $oid ?>">
                          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                          <input type="hidden" name="return_query" value="<?= h(build_query(['page' => $page])) ?>">
                          <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>

                  <?php if ($isDetail): ?>
                    <tr class="details-row">
                      <td colspan="9">

                        <?php if ($editId === $oid): ?>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="p-3 border rounded bg-white">
                                <h5 class="mb-3">Edit Order #<?= $oid ?></h5>

                                <form method="post" action="order-actions.php" class="row g-2">
                                  <input type="hidden" name="action" value="update">
                                  <input type="hidden" name="id" value="<?= $oid ?>">
                                  <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                  <input type="hidden" name="return_query" value="<?= h(build_query(['edit' => $oid, 'page' => $page])) ?>">

                                  <div class="col-12">
                                    <label class="form-label">Tracking number</label>
                                    <input class="form-control" name="tracking_number" value="<?= h((string)$detailOrder['tracking_number']) ?>">
                                  </div>

                                  <div class="col-12">
                                    <label class="form-label">Shipping carrier</label>
                                    <input class="form-control" name="shipping_carrier" value="<?= h((string)$detailOrder['shipping_carrier']) ?>">
                                  </div>

                                  <div class="col-12">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                      <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?= h($key) ?>" <?= ((string)$detailOrder['status'] === $key) ? 'selected' : '' ?>>
                                          <?= h($label) ?> (<?= h($key) ?>)
                                        </option>
                                      <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Nếu đổi status sẽ tự ghi ORDER_HISTORY.</div>
                                  </div>

                                  <div class="col-12">
                                    <label class="form-label">Note (optional)</label>
                                    <input class="form-control" name="note" placeholder="VD: Admin chuyển trạng thái do đã đóng gói xong">
                                  </div>

                                  <div class="col-12 d-flex gap-2">
                                    <button class="btn btn-warning" type="submit">Save</button>
                                    <a class="btn btn-outline-secondary"
                                      href="order-management.php?<?= h(build_query(['edit' => 0, 'view' => 0, 'page' => $page])) ?>">
                                      Close
                                    </a>
                                  </div>
                                </form>
                              </div>
                            </div>

                            <div class="col-md-6">
                              <div class="p-3 border rounded bg-white">
                                <h6 class="mb-2">Quick Info</h6>
                                <div><strong>Account:</strong> <?= $detailOrder['account_id'] !== null ? ('#' . (int)$detailOrder['account_id']) : '-' ?></div>
                                <div><strong>Created:</strong> <?= h((string)$detailOrder['created_at']) ?></div>
                                <div><strong>Total:</strong> <?= money($detailOrder['total_amount']) ?></div>
                                <div><strong>Ship fee:</strong> <?= money($detailOrder['shipping_fee']) ?></div>
                                <div><strong>Final:</strong> <?= money($detailOrder['final_amount']) ?></div>
                                <div class="mt-2"><strong>Address snapshot:</strong></div>
                                <pre class="addr p-2 bg-light rounded"><?= h((string)$detailOrder['shipping_address_snapshot']) ?></pre>
                              </div>
                            </div>

                            <div class="col-12">
                              <div class="p-3 border rounded bg-white">
                                <h6 class="mb-2">Items</h6>
                                <div class="table-responsive">
                                  <table class="table table-sm mb-0">
                                    <thead>
                                      <tr>
                                        <th>ID</th>
                                        <th>Variant ID</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Line</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($detailItems): ?>
                                      <?php foreach ($detailItems as $it): ?>
                                        <?php $line = (int)$it['quantity'] * (float)$it['price_at_purchase']; ?>
                                        <tr>
                                          <td><?= (int)$it['id'] ?></td>
                                          <td><?= h((string)$it['product_variant_id']) ?></td>
                                          <td class="text-end"><?= (int)$it['quantity'] ?></td>
                                          <td class="text-end"><?= money($it['price_at_purchase']) ?></td>
                                          <td class="text-end"><?= money($line) ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    <?php else: ?>
                                      <tr><td colspan="5" class="text-muted text-center">No items.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>

                            <div class="col-12">
                              <div class="p-3 border rounded bg-white">
                                <h6 class="mb-2">History</h6>
                                <div class="table-responsive">
                                  <table class="table table-sm mb-0">
                                    <thead>
                                      <tr>
                                        <th>Time</th>
                                        <th>Previous</th>
                                        <th>New</th>
                                        <th>Note</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($detailHistory): ?>
                                      <?php foreach ($detailHistory as $hr): ?>
                                        <tr>
                                          <td><?= h((string)$hr['created_at']) ?></td>
                                          <td><?= h((string)$hr['previous_status']) ?></td>
                                          <td><?= h((string)$hr['new_status']) ?></td>
                                          <td><?= h((string)$hr['note']) ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    <?php else: ?>
                                      <tr><td colspan="4" class="text-muted text-center">No history.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>

                          </div>

                        <?php else: ?>
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Order #<?= $oid ?> details</h5>
                            <a class="btn btn-sm btn-outline-secondary"
                              href="order-management.php?<?= h(build_query(['view' => 0, 'edit' => 0, 'page' => $page])) ?>">
                              Close
                            </a>
                          </div>

                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="p-3 border rounded bg-white">
                                <div><strong>Tracking:</strong> <?= h($detailOrder['tracking_number'] ?: '-') ?></div>
                                <div><strong>Status:</strong> <?= h((string)$detailOrder['status']) ?></div>
                                <div><strong>Carrier:</strong> <?= h($detailOrder['shipping_carrier'] ?: '-') ?></div>
                                <div><strong>Account:</strong> <?= $detailOrder['account_id'] !== null ? ('#' . (int)$detailOrder['account_id']) : '-' ?></div>
                                <div><strong>Created:</strong> <?= h((string)$detailOrder['created_at']) ?></div>
                              </div>
                            </div>

                            <div class="col-md-6">
                              <div class="p-3 border rounded bg-white">
                                <div><strong>Total:</strong> <?= money($detailOrder['total_amount']) ?></div>
                                <div><strong>Ship fee:</strong> <?= money($detailOrder['shipping_fee']) ?></div>
                                <div><strong>Final:</strong> <?= money($detailOrder['final_amount']) ?></div>
                                <div class="mt-2"><strong>Address snapshot:</strong></div>
                                <pre class="addr p-2 bg-light rounded"><?= h((string)$detailOrder['shipping_address_snapshot']) ?></pre>
                              </div>
                            </div>

                            <div class="col-12">
                              <div class="p-3 border rounded bg-white">
                                <h6 class="mb-2">Items</h6>
                                <div class="table-responsive">
                                  <table class="table table-sm mb-0">
                                    <thead>
                                      <tr>
                                        <th>ID</th>
                                        <th>Variant ID</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Line</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($detailItems): ?>
                                      <?php foreach ($detailItems as $it): ?>
                                        <?php $line = (int)$it['quantity'] * (float)$it['price_at_purchase']; ?>
                                        <tr>
                                          <td><?= (int)$it['id'] ?></td>
                                          <td><?= h((string)$it['product_variant_id']) ?></td>
                                          <td class="text-end"><?= (int)$it['quantity'] ?></td>
                                          <td class="text-end"><?= money($it['price_at_purchase']) ?></td>
                                          <td class="text-end"><?= money($line) ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    <?php else: ?>
                                      <tr><td colspan="5" class="text-muted text-center">No items.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>

                            <div class="col-12">
                              <div class="p-3 border rounded bg-white">
                                <h6 class="mb-2">History</h6>
                                <div class="table-responsive">
                                  <table class="table table-sm mb-0">
                                    <thead>
                                      <tr>
                                        <th>Time</th>
                                        <th>Previous</th>
                                        <th>New</th>
                                        <th>Note</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($detailHistory): ?>
                                      <?php foreach ($detailHistory as $hr): ?>
                                        <tr>
                                          <td><?= h((string)$hr['created_at']) ?></td>
                                          <td><?= h((string)$hr['previous_status']) ?></td>
                                          <td><?= h((string)$hr['new_status']) ?></td>
                                          <td><?= h((string)$hr['note']) ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    <?php else: ?>
                                      <tr><td colspan="4" class="text-muted text-center">No history.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>

                          </div>
                        <?php endif; ?>

                      </td>
                    </tr>
                  <?php endif; ?>

                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No orders found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <nav class="d-flex justify-content-end">
            <ul class="pagination mb-0">
              <?php $prev = max(1, $page - 1); $next = min($totalPages, $page + 1); ?>
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="order-management.php?<?= h(build_query(['page' => $prev])) ?>">Prev</a>
              </li>
              <li class="page-item disabled"><span class="page-link">Page <?= (int)$page ?></span></li>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="order-management.php?<?= h(build_query(['page' => $next])) ?>">Next</a>
              </li>
            </ul>
          </nav>

        </div>
      </div>

    </div>
  </main>
</div>
</body>
</html>
