<?php
require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/footer.php';

$db = db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /fleur-c-print/admin/queue.php'); exit; }

$order = $db->prepare('SELECT * FROM orders WHERE id = ?');
$order->execute([$id]);
$o = $order->fetch();
if (!$o) { flash('Order not found.','error'); header('Location: /fleur-c-print/admin/queue.php'); exit; }

$files = $db->prepare('SELECT * FROM order_files WHERE order_id = ?');
$files->execute([$id]);
$file_rows = $files->fetchAll();

// ── HANDLE POST ACTIONS ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    // Change status
    if ($action === 'status') {
        $new_status = $_POST['new_status'] ?? '';
        $allowed    = ['pending','in-progress','ready','completed','cancelled'];
        if (in_array($new_status, $allowed) && $o['status'] !== 'cancelled') {
            $extra = '';
            $params = [$new_status];
            if ($new_status === 'in-progress' && !$o['started_at']) {
                $extra   = ', started_at = NOW()';
            }
            if ($new_status === 'ready' || $new_status === 'completed') {
                $extra   = ', completed_at = NOW(), actual_minutes = TIMESTAMPDIFF(MINUTE, IFNULL(started_at, created_at), NOW())';
            }
            $params[] = $id;
            $db->prepare("UPDATE orders SET status = ? {$extra} WHERE id = ?")->execute($params);
            flash("Status updated to: {$new_status}", 'success');
        }
    }

    // Record payment
    if ($action === 'payment') {
        $method = in_array($_POST['pay_method'], ['Cash','GCash']) ? $_POST['pay_method'] : 'Cash';
        $amount = round((float)($_POST['pay_amount'] ?? 0), 2);
        $ref    = trim($_POST['gcash_ref'] ?? '');

        if ($amount > 0) {
            $new_paid = min($o['paid_amount'] + $amount, $o['total_amount']);
            $pay_status = $new_paid >= $o['total_amount'] ? 'paid' : 'partial';

            $db->prepare('UPDATE orders SET paid_amount=?, payment_status=?, payment_method=?, gcash_ref=? WHERE id=?')
               ->execute([$new_paid, $pay_status, $method, $ref, $id]);

            $db->prepare('INSERT INTO payment_log (order_id, amount, method, gcash_ref) VALUES (?,?,?,?)')
               ->execute([$id, $amount, $method, $ref]);

            if ($pay_status === 'paid') {
                $db->prepare('UPDATE customers SET total_spent = total_spent + ? WHERE id = ?')
                   ->execute([$amount, $o['customer_id']]);
            }
            flash('Payment recorded.', 'success');
        }
    }

    // Cancel order
    if ($action === 'cancel') {
        $db->prepare("UPDATE orders SET status='cancelled' WHERE id=?")->execute([$id]);
        // Delete files from disk
        foreach ($file_rows as $f) {
            $path = UPLOAD_BASE . $f['file_path'];
            if (file_exists($path)) unlink($path);
        }
        $db->prepare('DELETE FROM order_files WHERE order_id=?')->execute([$id]);
        flash('Order cancelled and files deleted.', 'info');
    }

    // Mark as notified
    if ($action === 'notified') {
        $db->prepare('UPDATE orders SET notified=1 WHERE id=?')->execute([$id]);
        flash('Notification marked as sent.', 'info');
    }

    header("Location: /fleur-c-print/admin/order.php?id={$id}"); exit;
}

// Reload fresh data after any POST
$order->execute([$id]);
$o = $order->fetch();
$files->execute([$id]);
$file_rows = $files->fetchAll();
$pay_log = $db->prepare('SELECT * FROM payment_log WHERE order_id=? ORDER BY recorded_at DESC');
$pay_log->execute([$id]);
$payments = $pay_log->fetchAll();

render_header('Order ' . $o['ref_code'], 'queue');
?>

<div class="page-header">
  <div>
    <div class="page-title"><em><?= e($o['ref_code']) ?></em></div>
    <div class="page-subtitle">Created <?= e(substr($o['created_at'],0,16)) ?></div>
  </div>
  <a href="/fleur-c-print/admin/queue.php" class="btn btn-outline">← Back</a>
</div>

<div class="od-grid">
<!-- LEFT COLUMN -->
<div>
  <div class="card" style="margin-bottom:16px">
    <div class="card-title">Order Info</div>
    <div class="d-row"><span class="d-key">Customer</span><span class="d-val"><strong><?= e($o['customer_name']) ?></strong></span></div>
    <div class="d-row"><span class="d-key">Phone</span><span class="d-val"><?= e($o['customer_phone'] ?: '—') ?></span></div>
    <div class="d-row"><span class="d-key">Service</span><span class="d-val"><?= e($o['service_name']) ?></span></div>
    <div class="d-row"><span class="d-key">Quantity</span><span class="d-val"><?= $o['quantity'] ?>× · <?= e($o['paper_size']) ?></span></div>
    <div class="d-row"><span class="d-key">Total</span><span class="d-val" style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;color:var(--accent)"><?= money($o['total_amount']) ?></span></div>
    <?php if ($o['notes']): ?>
    <div class="d-row"><span class="d-key">Notes</span><span class="d-val" style="font-style:italic;color:var(--text-2)"><?= e($o['notes']) ?></span></div>
    <?php endif; ?>
    <?php if ($o['actual_minutes']): ?>
    <div class="d-row"><span class="d-key">Time Taken</span><span class="d-val"><?= $o['actual_minutes'] ?> min</span></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title">Payment</div>
    <div class="d-row"><span class="d-key">Status</span><span class="d-val"><?= pay_badge($o['payment_status']) ?></span></div>
    <div class="d-row"><span class="d-key">Method</span><span class="d-val"><?= e($o['payment_method'] ?: '—') ?></span></div>
    <div class="d-row"><span class="d-key">Paid</span><span class="d-val"><?= money($o['paid_amount']) ?> / <?= money($o['total_amount']) ?></span></div>
    <?php if ($o['gcash_ref']): ?>
    <div class="d-row"><span class="d-key">GCash Ref</span><span class="d-val mono" style="font-size:11px"><?= e($o['gcash_ref']) ?></span></div>
    <?php endif; ?>

    <?php if ($o['payment_status'] !== 'paid' && $o['status'] !== 'cancelled'): ?>
    <form method="POST" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="payment">
      <div style="font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-3);margin-bottom:12px">Record Payment</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end">
        <div class="form-group">
          <label class="form-label">Method</label>
          <select class="form-control" name="pay_method"><option>Cash</option><option>GCash</option></select>
        </div>
        <div class="form-group">
          <label class="form-label">Amount (₱)</label>
          <input class="form-control" type="number" name="pay_amount" step="0.01" placeholder="<?= $o['total_amount'] - $o['paid_amount'] ?>">
        </div>
        <div class="form-group">
          <label class="form-label">GCash Ref</label>
          <input class="form-control" name="gcash_ref" placeholder="optional">
        </div>
        <button type="submit" class="btn btn-success btn-sm" style="align-self:flex-end">Mark Paid</button>
      </div>
    </form>
    <?php endif; ?>

    <?php if ($payments): ?>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
      <div style="font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-3);margin-bottom:8px">Payment History</div>
      <?php foreach ($payments as $p): ?>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-2);padding:4px 0">
        <span><?= e($p['method']) ?><?= $p['gcash_ref'] ? ' · <span class="mono" style="font-size:10px">'.e($p['gcash_ref']).'</span>' : '' ?></span>
        <span><strong><?= money($p['amount']) ?></strong> · <?= e(substr($p['recorded_at'],0,16)) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- RIGHT COLUMN -->
<div>
  <div class="card" style="margin-bottom:16px">
    <div class="card-title">Status</div>
    <div class="d-row"><span class="d-key">Current</span><span class="d-val"><?= status_badge($o['status']) ?></span></div>

    <?php if ($o['status'] !== 'cancelled'): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">
      <?php foreach (['pending','in-progress','ready','completed'] as $s): ?>
        <?php if ($s !== $o['status']): ?>
        <form method="POST" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="status">
          <input type="hidden" name="new_status" value="<?= $s ?>">
          <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px"><?= ucfirst($s) ?></button>
        </form>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="font-size:12px;color:var(--red);margin-top:10px">This order has been cancelled.</div>
    <?php endif; ?>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="card-title">Files</div>
    <?php if ($file_rows): ?>
    <div class="file-list" style="margin-top:0">
      <?php foreach ($file_rows as $f): ?>
      <div class="file-item">
        <span class="file-nm"><a href="/fleur-c-print/admin/file.php?id=<?= $f['id'] ?>" style="color:var(--accent)"><?= e($f['file_name']) ?></a></span>
        <span class="file-sz"><?= round($f['file_size']/1024/1024, 1) ?> MB</span>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_file">
          <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
          <button type="submit" class="file-rm">✕</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="font-size:12px;color:var(--text-3);padding:4px 0">No files attached.</div>
    <?php endif; ?>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="card-title">Customer Notification</div>
    <?php
    $fname = explode(' ', $o['customer_name'])[0];
    $ref   = $o['ref_code'];
    $total = money($o['total_amount']);
    $msgs  = [
        'ready'     => "Hi {$fname}! Your order {$ref} is ready for pickup at " . SHOP_NAME . ". Total: {$total}. Open until " . SHOP_HOURS . ".",
        'completed' => "Hi {$fname}! Just a reminder that your order {$ref} is still waiting for pickup. Let us know if you need to reschedule.",
        'default'   => "Hi {$fname}! We received your order {$ref}. We will notify you when it is ready.",
    ];
    $msg    = $msgs[$o['status']] ?? $msgs['default'];
    $label  = $o['status'] === 'ready' ? 'Ready for Pickup' : ($o['status'] === 'completed' ? 'Pickup Reminder' : 'Order Received');
    ?>
    <div class="notify-card">
      <div class="notify-label"><?= e($label) ?></div>
      <div class="notify-msg" id="notify-text"><?= e($msg) ?></div>
      <button class="btn btn-xs btn-outline" style="margin-top:10px" onclick="copyNotify(this)">
        <?= $o['notified'] ? '✓ Copied' : 'Copy' ?>
      </button>
      <?php if (!$o['notified']): ?>
      <form method="POST" style="display:inline;margin-left:6px">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="notified">
        <button type="submit" class="btn btn-xs btn-outline">Mark Sent</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($o['status'] !== 'cancelled'): ?>
  <form method="POST" onsubmit="return confirm('Cancel this order? Files will be deleted.')">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="cancel">
    <button type="submit" class="btn btn-danger btn-sm" style="width:100%">Cancel Order</button>
  </form>
  <?php endif; ?>
</div>
</div>

<script>
function copyNotify(btn) {
  const text = document.getElementById('notify-text').textContent;
  navigator.clipboard.writeText(text).then(() => {
    btn.textContent = '✓ Copied!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline');
  });
}
</script>

<?php render_footer(); ?>
