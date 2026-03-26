<?php
require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/footer.php';

$status_filter = $_GET['status'] ?? 'all';
$search        = trim($_GET['q'] ?? '');

$allowed = ['all','pending','in-progress','ready','completed','cancelled'];
if (!in_array($status_filter, $allowed)) $status_filter = 'all';

$sql    = 'SELECT * FROM orders WHERE 1=1';
$params = [];

if ($status_filter !== 'all') {
    $sql    .= ' AND status = ?';
    $params[] = $status_filter;
}
if ($search !== '') {
    $sql    .= ' AND (ref_code LIKE ? OR customer_name LIKE ? OR service_name LIKE ?)';
    $like    = '%' . $search . '%';
    $params  = array_merge($params, [$like, $like, $like]);
}
$sql .= ' ORDER BY id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

render_header('Order Queue', 'queue');
?>

<div class="page-header">
  <div>
    <div class="page-title">Order <em>Queue</em></div>
    <div class="page-subtitle">Click any row to view full details</div>
  </div>
  <a href="/fleur-c-print/admin/new-order.php" class="btn btn-primary">+ New Order</a>
</div>

<form method="GET" class="filter-bar">
  <?php foreach (['all','pending','in-progress','ready','completed','cancelled'] as $s): ?>
  <button type="submit" name="status" value="<?= $s ?>"
    class="ftab <?= $status_filter===$s?'active':'' ?>">
    <?= ucfirst($s) ?>
  </button>
  <?php endforeach; ?>
  <input class="search-box" type="text" name="q" placeholder="Search orders…"
    value="<?= e($search) ?>" oninput="this.form.submit()">
</form>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr>
      <th>Ref #</th><th>Customer</th><th>Service</th>
      <th>Status</th><th>Payment</th><th>Total</th><th>Date</th>
    </tr></thead>
    <tbody>
    <?php if ($orders): foreach ($orders as $o): ?>
    <tr class="tbl-link" onclick="location.href='/fleur-c-print/admin/order.php?id=<?= $o['id'] ?>'">
      <td><span class="ref-code"><?= e($o['ref_code']) ?></span></td>
      <td>
        <div style="font-weight:500"><?= e($o['customer_name']) ?></div>
        <div style="font-size:11px;color:var(--text-3)"><?= e($o['customer_phone']) ?></div>
      </td>
      <td>
        <?= e($o['service_name']) ?>
        <div style="font-size:11px;color:var(--text-3)"><?= $o['quantity'] ?>× · <?= e($o['paper_size']) ?></div>
      </td>
      <td><?= status_badge($o['status']) ?></td>
      <td><?= pay_badge($o['payment_status']) ?></td>
      <td style="font-weight:600"><?= money($o['total_amount']) ?></td>
      <td style="font-size:11px;color:var(--text-3)"><?= e(substr($o['created_at'],0,16)) ?></td>
    </tr>
    <?php endforeach; else: ?>
    <tr><td colspan="7"><div class="empty">No orders found</div></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php render_footer(); ?>
