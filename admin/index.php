<?php
require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/footer.php';

$db = db();

// Stats
$active   = (int) $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','in-progress','ready')")->fetchColumn();
$unpaid   = (int) $db->query("SELECT COUNT(*) FROM orders WHERE payment_status != 'paid' AND status != 'cancelled'")->fetchColumn();
$done_today = (int) $db->query("SELECT COUNT(*) FROM orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn();
$rev_today  = (float) $db->query("SELECT COALESCE(SUM(paid_amount),0) FROM orders WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()")->fetchColumn();

// Ready + In-progress
$ready   = $db->query("SELECT * FROM orders WHERE status = 'ready' ORDER BY created_at DESC")->fetchAll();
$inprog  = $db->query("SELECT * FROM orders WHERE status = 'in-progress' ORDER BY created_at DESC")->fetchAll();

render_header('Dashboard', 'dashboard');
?>

<div class="page-header">
  <div>
    <div class="page-title">Good morning, <em>Admin</em></div>
    <div class="page-subtitle"><?= date('l, F j, Y') ?></div>
  </div>
  <a href="/fleur-c-print/admin/new-order.php" class="btn btn-primary">+ New Order</a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Active Orders</div><div class="stat-value accent"><?= $active ?></div><div class="stat-sub">in queue</div></div>
  <div class="stat-card"><div class="stat-label">Unpaid</div><div class="stat-value red"><?= $unpaid ?></div><div class="stat-sub">outstanding</div></div>
  <div class="stat-card"><div class="stat-label">Completed Today</div><div class="stat-value green"><?= $done_today ?></div><div class="stat-sub">finished</div></div>
  <div class="stat-card"><div class="stat-label">Revenue Today</div><div class="stat-value blue">₱<?= number_format($rev_today) ?></div><div class="stat-sub">collected</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
  <div>
    <div class="section-head">Ready for Pickup</div>
    <?php if ($ready): foreach ($ready as $o): ?>
    <a href="/fleur-c-print/admin/order.php?id=<?= $o['id'] ?>" style="text-decoration:none">
      <div class="card" style="margin-bottom:8px;padding:14px 16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
          <span class="ref-code"><?= e($o['ref_code']) ?></span>
          <?= pay_badge($o['payment_status']) ?>
        </div>
        <div style="font-weight:500;font-size:13px"><?= e($o['customer_name']) ?></div>
        <div style="font-size:11px;color:var(--text-3)"><?= e($o['service_name']) ?> · <?= $o['quantity'] ?>× · <strong><?= money($o['total_amount']) ?></strong></div>
      </div>
    </a>
    <?php endforeach; else: ?>
    <div class="empty">No orders waiting for pickup</div>
    <?php endif; ?>
  </div>
  <div>
    <div class="section-head">In Progress</div>
    <?php if ($inprog): foreach ($inprog as $o): ?>
    <a href="/fleur-c-print/admin/order.php?id=<?= $o['id'] ?>" style="text-decoration:none">
      <div class="card" style="margin-bottom:8px;padding:14px 16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
          <span class="ref-code"><?= e($o['ref_code']) ?></span>
          <?= pay_badge($o['payment_status']) ?>
        </div>
        <div style="font-weight:500;font-size:13px"><?= e($o['customer_name']) ?></div>
        <div style="font-size:11px;color:var(--text-3)"><?= e($o['service_name']) ?> · <?= $o['quantity'] ?>× · <strong><?= money($o['total_amount']) ?></strong></div>
      </div>
    </a>
    <?php endforeach; else: ?>
    <div class="empty">No active jobs</div>
    <?php endif; ?>
  </div>
</div>

<?php render_footer(); ?>
