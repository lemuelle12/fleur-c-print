<?php
require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/footer.php';

$db = db();
$filter = $_GET['filter'] ?? 'all';

if ($filter === 'unpaid') {
    $customers = $db->query("
        SELECT DISTINCT c.* FROM customers c
        JOIN orders o ON o.customer_id = c.id
        WHERE o.payment_status != 'paid' AND o.status != 'cancelled'
        ORDER BY c.name
    ")->fetchAll();
} else {
    $customers = $db->query("SELECT * FROM customers ORDER BY name")->fetchAll();
}

// Handle new customer POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_customer') {
    csrf_verify();
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if ($name) {
        $db->prepare('INSERT INTO customers (name,phone,email,notes) VALUES (?,?,?,?)')
           ->execute([$name,$phone,$email,$notes]);
        flash('Customer added.', 'success');
        header('Location: /fleur-c-print/admin/customers.php'); exit;
    }
}

render_header('Customers', 'customers');
?>

<div class="page-header">
  <div>
    <div class="page-title">Cus<em>tomers</em></div>
    <div class="page-subtitle">Manage customer records</div>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <a href="?filter=all"    class="ftab <?= $filter==='all'?'active':'' ?>">All</a>
    <a href="?filter=unpaid" class="ftab <?= $filter==='unpaid'?'active':'' ?>">Unpaid Orders</a>
    <button class="btn btn-primary" onclick="document.getElementById('add-cust-form').style.display='block';this.style.display='none'">+ Add Customer</button>
  </div>
</div>

<!-- Inline add form -->
<div id="add-cust-form" style="display:none" class="card" style="max-width:640px;margin-bottom:24px">
  <div class="card-title">New Customer</div>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_customer">
    <div class="form-grid">
      <div class="form-group full"><label class="form-label">Full Name *</label><input class="form-control" name="name" required placeholder="Juan dela Cruz"></div>
      <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone" placeholder="09XX-XXX-XXXX"></div>
      <div class="form-group"><label class="form-label">Email</label><input class="form-control" name="email" type="email"></div>
      <div class="form-group full"><label class="form-label">Notes</label><textarea class="form-control" name="notes" placeholder="Prefers matte, always pays cash…"></textarea></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button type="submit" class="btn btn-primary btn-sm">Save</button>
      <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('add-cust-form').style.display='none'">Cancel</button>
    </div>
  </form>
</div>

<?php if ($customers): ?>
<div class="cust-grid">
  <?php foreach ($customers as $c): ?>
  <div class="cust-card">
    <div class="cust-initials"><?= mb_substr($c['name'],0,1) ?></div>
    <div class="cust-name"><?= e($c['name']) ?></div>
    <div class="cust-phone"><?= e($c['phone'] ?: '—') ?></div>
    <?php if ($c['notes']): ?><div style="font-size:11px;color:var(--text-3);margin-top:6px;font-style:italic">"<?= e($c['notes']) ?>"</div><?php endif; ?>
    <div class="cust-stats">
      <div class="cust-stat"><span class="val"><?= $c['order_count'] ?></span><span class="lbl">Orders</span></div>
      <div class="cust-stat"><span class="val">₱<?= number_format($c['total_spent']) ?></span><span class="lbl">Spent</span></div>
    </div>
    <div style="margin-top:14px">
      <a href="/fleur-c-print/admin/queue.php?q=<?= urlencode($c['name']) ?>" class="btn btn-outline btn-xs">View Orders</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty">No customers found.</div>
<?php endif; ?>

<?php render_footer(); ?>
