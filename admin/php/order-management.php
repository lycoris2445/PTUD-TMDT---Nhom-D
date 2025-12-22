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
$detailId = ($editId > 0 ? $editId : ($viewId > 0 ? $viewId : 0));

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$filters = ['q' => $q, 'status' => $status];
$total = count_orders($conn, $filters);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$orders = fetch_orders($conn, $filters, $perPage, $offset);

$statuses = order_statuses();
$filterStatusGroups = status_filter_options();
$returnStatuses = return_statuses();

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function build_query(array $override = []): string
{
    $params = array_merge($_GET, $override);
    // remove empty
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) unset($params[$k]);
    }
    return http_build_query($params);
}

// Details (view/edit)
$detailOrder = null;
$detailItems = [];
$detailHistory = [];
$detailReturn = null;

if ($detailId > 0) {
    $detailOrder = get_order_by_id($conn, $detailId);
    if ($detailOrder) {
        $detailItems = get_order_items($conn, $detailId);
        $detailHistory = get_order_history($conn, $detailId);
        $detailReturn = get_return_by_order_id($conn, $detailId);
    }
}

// return query for actions (keep current filter/page)
$returnQuery = build_query(['page' => $page, 'q' => $q, 'status' => $status, 'edit' => $editId ?: null, 'view' => $viewId ?: null]);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin-main.css">
  <link rel="stylesheet" href="../css/order-management.css">

  <style>
    .badge-status { font-size: 0.85rem; }
    pre.addr { white-space: pre-wrap; margin: 0; }
  </style>
</head>
<body>
  
<div class="admin-wrapper">

  <!-- Sidebar -->
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <!-- Main -->
  <main class="admin-main">
      <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="page-title">Order Management</h2>
      </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h($flash['type'] ?? 'info') ?>"><?= h($flash['message'] ?? '') ?></div>
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
              <?php foreach ($filterStatusGroups as $groupLabel => $options): ?>
                <optgroup label="<?= h($groupLabel) ?>">
                  <?php foreach ($options as $value => $text): ?>
                    <option value="<?= h($value) ?>" <?= ($status === $value) ? 'selected' : '' ?>>
                      <?= h($text) ?>
                    </option>
                  <?php endforeach; ?>
                </optgroup>
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
              <th>ID</th>
              <th>Tracking</th>
              <th>Account</th>
              <th>Total</th>
              <th>Ship Fee</th>
              <th>Final</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $row): ?>
              <tr>
                <td>#<?= (int)$row['id'] ?></td>
                <td><?= h((string)$row['tracking_number']) ?></td>
                <td><?= h((string)($row['account_email'] ?? ('#' . (int)$row['account_id']))) ?></td>
                <td><?= h((string)$row['total_amount']) ?></td>
                <td><?= h((string)$row['shipping_fee']) ?></td>
                <td><?= h((string)$row['final_amount']) ?></td>
                <td>
                  <?php
                    $orderStatus = (string)($row['order_status'] ?? $row['status'] ?? '');
                    $label = $statuses[$orderStatus] ?? $orderStatus;
                  ?>
                  <span class="badge bg-secondary badge-status">
                    <?= h($label) ?>
                  </span>

                  <?php if (!empty($row['return_status'])): ?>
                    <span class="badge bg-warning text-dark badge-status ms-1">
                      <?= h((string)$row['return_status']) ?>
                    </span>
                  <?php endif; ?>
                </td>

                <td><?= h((string)$row['created_at']) ?></td>
                <td class="d-flex gap-2">
                  <a class="btn btn-sm btn-outline-primary" href="order-management.php?<?= h(build_query(['view' => (int)$row['id'], 'edit' => null, 'page' => $page])) ?>">View</a>
                  <a class="btn btn-sm btn-outline-warning" href="order-management.php?<?= h(build_query(['edit' => (int)$row['id'], 'view' => null, 'page' => $page])) ?>">Edit</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
              <tr><td colspan="9" class="text-center text-muted py-4">No orders found.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav>
          <ul class="pagination justify-content-end">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="order-management.php?<?= h(build_query(['page' => $p])) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>

        <?php if ($detailOrder): ?>
          <?php $oid = (int)$detailOrder['id']; ?>
          <hr class="my-4">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="p-3 border rounded bg-white">
                <h5 class="mb-3"><?= $editId > 0 ? "Edit Order #{$oid}" : "View Order #{$oid}" ?></h5>

                <form method="post" action="order-actions.php" class="row g-2">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= $oid ?>">
                  <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                  <input type="hidden" name="return_query" value="<?= h(build_query(['edit' => $oid, 'page' => $page])) ?>">

                  <div class="col-12">
                    <label class="form-label">Tracking number</label>
                    <input class="form-control" name="tracking_number" value="<?= h((string)$detailOrder['tracking_number']) ?>" <?= $editId > 0 ? '' : 'readonly' ?>>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Shipping carrier</label>
                    <input class="form-control" name="shipping_carrier" value="<?= h((string)$detailOrder['shipping_carrier']) ?>" <?= $editId > 0 ? '' : 'readonly' ?>>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Status</label>
                    <?php
                      $currentStatus = (string)$detailOrder['status'];
                      $nextStatuses = allowed_next_statuses($currentStatus);
                      $selectKeys = array_values(array_unique(array_merge([$currentStatus], $nextStatuses)));
                      $disableStatusSelect = empty($nextStatuses); // không có bước tiếp theo
                    ?>
                    <select class="form-select" name="status" <?= ($disableStatusSelect || $editId <= 0) ? 'disabled' : '' ?>>
                      <?php foreach ($selectKeys as $key): ?>
                        <?php $label = $statuses[$key] ?? $key; ?>
                        <option value="<?= h($key) ?>" <?= ($currentStatus === $key) ? 'selected' : '' ?>>
                          <?= h($label) ?> (<?= h($key) ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>

                    <?php if ($disableStatusSelect): ?>
                      <div class="form-text text-muted">
                        Trạng thái hiện tại không có bước chuyển tiếp hợp lệ theo flow fulfillment (hoặc đã completed).
                        Bạn vẫn có thể sửa tracking/carrier.
                      </div>
                      <input type="hidden" name="status" value="<?= h($currentStatus) ?>">
                    <?php else: ?>
                      <div class="form-text">
                        Chỉ cho phép đổi theo flow: new → processing → awaiting_pickup → shipping → shipped → completed.
                      </div>
                    <?php endif; ?>

                    <div class="form-text">Nếu đổi status sẽ tự ghi ORDER_HISTORY.</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Note (optional)</label>
                    <textarea class="form-control" name="note" rows="2" <?= $editId > 0 ? '' : 'readonly' ?>></textarea>
                  </div>

                  <?php if ($editId > 0): ?>
                    <div class="col-12 d-flex gap-2 mt-2">
                      <button class="btn btn-dark" type="submit">Save changes</button>
                      <a class="btn btn-outline-secondary" href="order-management.php?<?= h(build_query(['edit' => null, 'view' => null, 'page' => $page])) ?>">Close</a>
                    </div>
                  <?php else: ?>
                    <div class="col-12 mt-2">
                      <a class="btn btn-outline-secondary" href="order-management.php?<?= h(build_query(['view' => null, 'edit' => null, 'page' => $page])) ?>">Close</a>
                    </div>
                  <?php endif; ?>
                </form>
              </div>
            </div>

            <div class="col-md-6">
              <div class="p-3 border rounded bg-white">
                <h6 class="mb-2">Quick Info</h6>
                <div><strong>Account:</strong> <?= h((string)($detailOrder['account_id'] ?? '-')) ?></div>
                <div><strong>Created:</strong> <?= h((string)$detailOrder['created_at']) ?></div>
                <div><strong>Total:</strong> <?= h((string)$detailOrder['total_amount']) ?></div>
                <div><strong>Ship fee:</strong> <?= h((string)$detailOrder['shipping_fee']) ?></div>
                <div><strong>Final:</strong> <?= h((string)$detailOrder['final_amount']) ?></div>
                <div class="mt-2"><strong>Address snapshot:</strong></div>
                <pre class="addr p-2 bg-light rounded border"><?= h((string)$detailOrder['shipping_address_snapshot']) ?></pre>
              </div>

              <div class="p-3 border rounded bg-white mt-3">
                <h6 class="mb-2">Return / Refund</h6>

                <?php if (!$detailReturn): ?>
                  <div class="alert alert-light border text-muted small mb-0">
                    <i class="bi bi-info-circle"></i> No return request has been submitted by the customer.
                  </div>
                <?php else: ?>
                  <?php
                    $rCurrent = (string)$detailReturn['status'];
                    $rNext = allowed_next_return_statuses($rCurrent);
                    $rSelectKeys = array_values(array_unique(array_merge([$rCurrent], $rNext)));
                    $rDisable = empty($rNext);
                  ?>
                  <div class="mb-2">
                    <div><strong>Return ID:</strong> #<?= (int)$detailReturn['id'] ?></div>
                    <div><strong>Current status:</strong> <span class="badge bg-warning text-dark"><?= h($rCurrent) ?></span></div>
                    <div class="text-muted small"><strong>Reason:</strong> <?= h((string)$detailReturn['reason']) ?></div>
                  </div>

                  <form method="post" action="order-actions.php" class="row g-2">
                    <input type="hidden" name="action" value="return_update">
                    <input type="hidden" name="id" value="<?= $oid ?>">
                    <input type="hidden" name="return_id" value="<?= (int)$detailReturn['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <input type="hidden" name="return_query" value="<?= h(build_query(['edit' => $oid, 'page' => $page])) ?>">

                    <div class="col-12">
                      <label class="form-label">Return status</label>
                      <select class="form-select" name="return_status" <?= ($rDisable || $editId <= 0) ? 'disabled' : '' ?>>
                        <?php foreach ($rSelectKeys as $k): ?>
                          <?php $lbl = $returnStatuses[$k] ?? $k; ?>
                          <option value="<?= h($k) ?>" <?= ($rCurrent === $k) ? 'selected' : '' ?>>
                            <?= h($lbl) ?> (<?= h($k) ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Refund amount</label>
                      <input class="form-control" name="refund_amount" inputmode="decimal" value="<?= h((string)($detailReturn['refund_amount'] ?? '')) ?>" <?= $editId > 0 ? '' : 'readonly' ?>>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Admin note</label>
                      <input class="form-control" name="admin_note" value="<?= h((string)($detailReturn['admin_note'] ?? '')) ?>" <?= $editId > 0 ? '' : 'readonly' ?>>
                    </div>

                    <?php if ($editId > 0 && !$rDisable): ?>
                      <div class="col-12 mt-2">
                        <button class="btn btn-primary btn-sm" type="submit">Update return status</button>
                      </div>
                    <?php endif; ?>
                  </form>
                <?php endif; ?> </div>
            </div>

            <div class="col-12">
              <div class="p-3 border rounded bg-white">
                <h6 class="mb-2">Items</h6>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead class="table-light">
                    <tr>
                      <th>ID</th>
                      <th>Variant</th>
                      <th>Qty</th>
                      <th>Price</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detailItems as $it): ?>
                      <tr>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= h((string)$it['product_variant_id']) ?></td>
                        <td><?= h((string)$it['quantity']) ?></td>
                        <td><?= h((string)$it['price_at_purchase']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (!$detailItems): ?>
                      <tr><td colspan="4" class="text-muted text-center py-3">No items.</td></tr>
                    <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="p-3 border rounded bg-white">
                <h6 class="mb-2">ORDER_HISTORY</h6>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead class="table-light">
                    <tr>
                      <th>When</th>
                      <th>Previous</th>
                      <th>New</th>
                      <th>Note</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detailHistory as $hrow): ?>
                      <tr>
                        <td><?= h((string)$hrow['created_at']) ?></td>
                        <td><?= h((string)$hrow['previous_status']) ?></td>
                        <td><?= h((string)$hrow['new_status']) ?></td>
                        <td><?= h((string)$hrow['note']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (!$detailHistory): ?>
                      <tr><td colspan="4" class="text-muted text-center py-3">No history.</td></tr>
                    <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>
        <?php endif; ?>

      </div>
    </div>
  </main>
</div>
</body>
</html>
