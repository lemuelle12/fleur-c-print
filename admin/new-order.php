<?php
// ── admin/new-order.php ──────────────────────────────────────
require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/footer.php';

$db        = db();
$services  = $db->query("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$customers = $db->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $service_id     = (int)($_POST['service_id'] ?? 0);
    $quantity       = max(1, (int)($_POST['quantity'] ?? 1));
    $paper_size     = trim($_POST['paper_size'] ?? '');
    $total_amount   = (float)($_POST['total_amount'] ?? 0);
    $notes          = trim($_POST['notes'] ?? '');

    $errors = [];
    if (!$customer_name) $errors[] = 'Customer name is required.';
    if (!$service_id)    $errors[] = 'Please select a service.';

    if (empty($errors)) {
        $svc = $db->prepare('SELECT name FROM services WHERE id = ?');
        $svc->execute([$service_id]);
        $svc_row = $svc->fetch();
        if (!$svc_row) { $errors[] = 'Invalid service.'; goto show_form; }

        // Upsert customer
        $cust = $db->prepare('SELECT id FROM customers WHERE name = ? LIMIT 1');
        $cust->execute([$customer_name]);
        $cust_row = $cust->fetch();
        if ($cust_row) {
            $customer_id = $cust_row['id'];
            if ($customer_phone) {
                $db->prepare('UPDATE customers SET phone=? WHERE id=?')->execute([$customer_phone, $customer_id]);
            }
            $db->prepare('UPDATE customers SET order_count = order_count + 1 WHERE id=?')->execute([$customer_id]);
        } else {
            $db->prepare('INSERT INTO customers (name,phone,order_count) VALUES (?,?,1)')->execute([$customer_name, $customer_phone]);
            $customer_id = (int)$db->lastInsertId();
        }

        $ref  = next_ref_code();
        $stmt = $db->prepare('INSERT INTO orders
            (ref_code, customer_id, customer_name, customer_phone, service_id, service_name,
             quantity, paper_size, total_amount, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $ref, $customer_id, $customer_name, $customer_phone,
            $service_id, $svc_row['name'], $quantity, $paper_size, $total_amount, $notes
        ]);
        $order_id = (int)$db->lastInsertId();

        // ── Handle file uploads with proper error reporting ──────
        $uploadErrors  = [];
        $uploadSuccess = 0;

        if (!empty($_FILES['files']['name'][0])) {
            foreach ($_FILES['files']['tmp_name'] as $k => $tmp) {
                $file = [
                    'tmp_name' => $tmp,
                    'name'     => $_FILES['files']['name'][$k],
                    'size'     => $_FILES['files']['size'][$k],
                    'error'    => $_FILES['files']['error'][$k],
                ];

                // Skip empty slots (browser sends them when fewer files chosen)
                if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;

                $result = handle_upload($file, $order_id);

                if (is_array($result)) {
                    $db->prepare(
                        'INSERT INTO order_files (order_id,file_name,file_path,file_size,mime_type) VALUES (?,?,?,?,?)'
                    )->execute([
                        $order_id,
                        $result['file_name'],
                        $result['file_path'],
                        $result['file_size'],
                        $result['mime_type'],
                    ]);
                    $uploadSuccess++;
                } else {
                    // $result is a string error message
                    $uploadErrors[] = basename($_FILES['files']['name'][$k]) . ': ' . $result;
                }
            }
        }

        // Flash the appropriate message
        if (empty($uploadErrors)) {
            flash("Order {$ref} created successfully.", 'success');
        } else {
            $errList = implode(' | ', $uploadErrors);
            flash(
                "Order {$ref} created" .
                ($uploadSuccess > 0 ? " ({$uploadSuccess} file(s) attached)" : '') .
                ", but some files were rejected: {$errList}",
                'warning'
            );
        }

        header("Location: /fleur-c-print/admin/order.php?id={$order_id}");
        exit;
    }
}

show_form:
render_header('New Order', 'new-order');
?>

<div class="page-header">
  <div>
    <div class="page-title">New <em>Order</em></div>
    <div class="page-subtitle">Create a new print job</div>
  </div>
  <a href="/fleur-c-print/admin/queue.php" class="btn btn-outline">← Back to Queue</a>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<div class="card" style="max-width:760px">
<form method="POST" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">

    <div class="form-group">
      <label class="form-label">Customer Name *</label>
      <input class="form-control" name="customer_name" list="cust-list"
        value="<?= e($_POST['customer_name'] ?? '') ?>" placeholder="Name or select existing" required>
      <datalist id="cust-list">
        <?php foreach ($customers as $c): ?>
        <option value="<?= e($c['name']) ?>">
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="form-group">
      <label class="form-label">Phone</label>
      <input class="form-control" name="customer_phone"
        value="<?= e($_POST['customer_phone'] ?? '') ?>" placeholder="09XX-XXX-XXXX">
    </div>

    <div class="form-group">
      <label class="form-label">Service *</label>
      <select class="form-control" name="service_id" id="svc-select" onchange="updatePrice()" required>
        <option value="">— Select —</option>
        <?php foreach ($services as $s): ?>
        <option value="<?= $s['id'] ?>"
          data-price="<?= $s['base_price'] ?>"
          <?= ($_POST['service_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
          <?= e($s['name']) ?> (₱<?= $s['base_price'] ?> <?= e($s['unit_label']) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Quantity</label>
      <input class="form-control" type="number" name="quantity" id="qty-input" min="1"
        value="<?= (int)($_POST['quantity'] ?? 1) ?>" oninput="updatePrice()">
    </div>

    <div class="form-group">
      <label class="form-label">Paper Size</label>
      <select class="form-control" name="paper_size">
        <?php foreach (['A4','Short (Legal)','Long (Legal)','4R (4×6)','5R (5×7)','Custom'] as $p): ?>
        <option <?= ($_POST['paper_size'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Estimated Total (₱)</label>
      <input class="form-control" type="number" name="total_amount" id="total-input"
        step="0.01" placeholder="0.00" value="<?= $_POST['total_amount'] ?? '' ?>">
    </div>

    <div class="form-group full">
      <label class="form-label">Special Instructions</label>
      <textarea class="form-control" name="notes"
        placeholder="Matte finish, landscape, colored…"><?= e($_POST['notes'] ?? '') ?></textarea>
    </div>

    <div class="form-group full">
      <label class="form-label">Attach Files</label>
      <div class="file-drop" onclick="document.getElementById('file-input').click()">
        Click to upload or drag &amp; drop<br>
        <span style="font-size:10px;color:var(--text-3)">PDF · JPG · PNG · DOCX · XLSX — max 50 MB each</span>
      </div>
      <input type="file" id="file-input" name="files[]" multiple
        accept=".pdf,.jpg,.jpeg,.png,.docx,.doc,.xlsx"
        style="display:none" onchange="showFiles(this)">
      <div class="file-list" id="file-preview"></div>
    </div>

  </div>
  <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)">
    <a href="/fleur-c-print/admin/queue.php" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-primary">Create Order</button>
  </div>
</form>
</div>

<script>
function updatePrice() {
  const sel   = document.getElementById('svc-select');
  const price = parseFloat(sel.options[sel.selectedIndex]?.dataset.price) || 0;
  const qty   = parseInt(document.getElementById('qty-input').value) || 1;
  document.getElementById('total-input').value = (price * qty).toFixed(2);
}
function showFiles(input) {
  const list = document.getElementById('file-preview');
  list.innerHTML = [...input.files].map(f => `
    <div class="file-item">
      <span class="file-nm">${f.name}</span>
      <span class="file-sz">${(f.size/1024/1024).toFixed(1)} MB</span>
    </div>`).join('');
}
const zone = document.querySelector('.file-drop');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor='var(--accent)'; });
zone.addEventListener('dragleave', () => zone.style.borderColor='');
zone.addEventListener('drop', e => {
  e.preventDefault(); zone.style.borderColor='';
  const dt = new DataTransfer();
  [...e.dataTransfer.files].forEach(f => dt.items.add(f));
  document.getElementById('file-input').files = dt.files;
  showFiles(document.getElementById('file-input'));
});
</script>

<?php render_footer(); ?>
