<?php
// ── admin/daily-summary.php ──────────────────────────────────
require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/footer.php';

$db   = db();
$date = $_GET['date'] ?? date('Y-m-d');

// Validate date format to prevent SQL injection via date parameter
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
    $date = date('Y-m-d');
}

// ── Revenue collected today ───────────────────────────────────
$s = $db->prepare("SELECT COALESCE(SUM(paid_amount),0) FROM orders WHERE payment_status='paid' AND DATE(created_at)=?");
$s->execute([$date]);
$rev = (float) $s->fetchColumn();

// ── Completed orders today ────────────────────────────────────
$s = $db->prepare("SELECT COUNT(*) FROM orders WHERE status='completed' AND DATE(created_at)=?");
$s->execute([$date]);
$completed = (int) $s->fetchColumn();

// ── Outstanding balance today ─────────────────────────────────
$s = $db->prepare("SELECT COALESCE(SUM(total_amount - paid_amount),0) FROM orders WHERE payment_status != 'paid' AND status != 'cancelled' AND DATE(created_at)=?");
$s->execute([$date]);
$outstanding = (float) $s->fetchColumn();

// ── All orders for the selected date ─────────────────────────
$s = $db->prepare("SELECT * FROM orders WHERE DATE(created_at)=? ORDER BY id DESC");
$s->execute([$date]);
$orders = $s->fetchAll();

render_header('Daily Summary', 'summary');
?>

<div class="page-header">
  <div>
    <div class="page-title">Daily <em>Summary</em></div>
    <div class="page-subtitle"><?= date('l, F j, Y', strtotime($date)) ?></div>
  </div>
  <div style="display:flex;gap:10px;align-items:center">
    <form method="GET" style="display:flex;align-items:center;gap:8px">
      <label class="form-label" style="white-space:nowrap">Date:</label>
      <input class="form-control" type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()"
        style="width:160px;border:1px solid var(--border2);border-radius:var(--radius);padding:6px 10px">
    </form>
    <button class="btn btn-outline" onclick="window.print()">Print</button>
  </div>
</div>

<div class="sum-grid">
  <div class="sum-card"><div class="lbl">Total Revenue</div><div class="sum-hero">₱<?= number_format($rev) ?></div></div>
  <div class="sum-card"><div class="lbl">Completed</div><div class="sum-hero" style="color:var(--green)"><?= $completed ?></div></div>
  <div class="sum-card"><div class="lbl">Outstanding</div><div class="sum-hero" style="color:var(--red)">₱<?= number_format($outstanding) ?></div></div>
</div>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr>
      <th>Ref #</th><th>Customer</th><th>Service</th>
      <th>Amount</th><th>Payment</th><th>Status</th>
    </tr></thead>
    <tbody>
    <?php if ($orders): foreach ($orders as $o): ?>
    <tr class="tbl-link" onclick="location.href='/fleur-c-print/admin/order.php?id=<?= $o['id'] ?>'">
      <td><span class="ref-code"><?= e($o['ref_code']) ?></span></td>
      <td><?= e($o['customer_name']) ?></td>
      <td><?= e($o['service_name']) ?></td>
      <td style="font-weight:600"><?= money($o['total_amount']) ?></td>
      <td><?= pay_badge($o['payment_status']) ?></td>
      <td><?= status_badge($o['status']) ?></td>
    </tr>
    <?php endforeach; else: ?>
    <tr><td colspan="6"><div class="empty">No orders for this date.</div></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php render_footer(); ?>
