<?php
// ── order.php ──────────────────────────────────────────────
// Public order form – customers can submit print requests.

// MUST BE FIRST - Start session before ANY output
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/public/header.php';
require_once __DIR__ . '/public/footer.php';

$db = db();
$services = $db->query("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$errors = [];
$success = false;

// Generate CSRF token BEFORE processing form
if (empty($_SESSION['order_csrf'])) {
    $_SESSION['order_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation with hash_equals (secure comparison)
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['order_csrf']) || 
        !hash_equals($_SESSION['order_csrf'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please refresh the page and try again.';
    } else {
        $customer_name  = trim($_POST['customer_name'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $service_id     = (int)($_POST['service_id'] ?? 0);
        $quantity       = max(1, (int)($_POST['quantity'] ?? 1));
        $paper_size     = trim($_POST['paper_size'] ?? '');
        $total_amount   = (float)($_POST['total_amount'] ?? 0);
        $notes          = trim($_POST['notes'] ?? '');

        if (!$customer_name)  $errors[] = 'Please enter your name.';
        if (!$customer_phone) $errors[] = 'Please enter your phone number.';
        if (!$service_id)     $errors[] = 'Please select a service.';

        if (empty($errors)) {
            // Get service details
            $svc = $db->prepare('SELECT name, base_price FROM services WHERE id = ?');
            $svc->execute([$service_id]);
            $svc_row = $svc->fetch();
            if (!$svc_row) { $errors[] = 'Invalid service selected.'; }

            if (empty($errors)) {
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

                $ref = next_ref_code();

                // Insert order
                $stmt = $db->prepare('INSERT INTO orders
                    (ref_code, customer_id, customer_name, customer_phone, service_id, service_name,
                     quantity, paper_size, total_amount, notes, status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $ref, $customer_id, $customer_name, $customer_phone,
                    $service_id, $svc_row['name'], $quantity, $paper_size, $total_amount, $notes,
                    'pending'
                ]);
                $order_id = (int)$db->lastInsertId();

                // Handle file uploads
                $uploadErrors = [];
                $uploadSuccess = 0;
                if (!empty($_FILES['files']['name'][0])) {
                    foreach ($_FILES['files']['tmp_name'] as $k => $tmp) {
                        $file = [
                            'tmp_name' => $tmp,
                            'name'     => $_FILES['files']['name'][$k],
                            'size'     => $_FILES['files']['size'][$k],
                            'error'    => $_FILES['files']['error'][$k],
                        ];
                        if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
                        $result = handle_upload($file, $order_id);
                        if (is_array($result)) {
                            $db->prepare('INSERT INTO order_files (order_id,file_name,file_path,file_size,mime_type) VALUES (?,?,?,?,?)')
                               ->execute([$order_id, $result['file_name'], $result['file_path'], $result['file_size'], $result['mime_type']]);
                            $uploadSuccess++;
                        } else {
                            $uploadErrors[] = basename($_FILES['files']['name'][$k]) . ': ' . $result;
                        }
                    }
                }

                // Set success message
                if (empty($uploadErrors)) {
                    $_SESSION['order_success'] = "Order #{$ref} received! We'll contact you soon.";
                } else {
                    $_SESSION['order_success'] = "Order #{$ref} received, but some files were rejected: " . implode(', ', $uploadErrors);
                }

                // Regenerate CSRF token after successful submission (prevents reuse)
                unset($_SESSION['order_csrf']);
                $_SESSION['order_csrf'] = bin2hex(random_bytes(32));

                header('Location: /fleur-c-print/order.php?done=1');
                exit;
            }
        }
    }
}

// Get token for form display
$csrf_token = $_SESSION['order_csrf'];

// If success flag, show message
if (isset($_GET['done']) && isset($_SESSION['order_success'])) {
    $success_msg = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
} else {
    $success_msg = '';
}

render_public_header('Place an Order', 'order');
?>

<section class="page-hero">
  <div class="container">
    <p class="label">Submit your print request</p>
    <div class="rule"></div>
    <h1 class="section-title">Place an <em>Order</em></h1>
    <p class="section-sub">Fill out the details below and we'll get back to you with a confirmation.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="contact-wrap" style="grid-template-columns:1fr;">
      <div class="contact-form-wrap" style="max-width:800px;margin:0 auto;">
        <div class="form-title">Order Details</div>

        <?php if ($success_msg): ?>
        <div class="form-msg success" style="display:block;"><?= e($success_msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="form-msg error" style="display:block;">
          <?= implode('<br>', array_map('e', $errors)) ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="customer_name">Full Name *</label>
              <input type="text" id="customer_name" name="customer_name" class="form-control"
                value="<?= e($_POST['customer_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="customer_phone">Phone Number *</label>
              <input type="tel" id="customer_phone" name="customer_phone" class="form-control"
                value="<?= e($_POST['customer_phone'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="service_id">Service *</label>
            <select id="service_id" name="service_id" class="form-control" required>
              <option value="">— Select a service —</option>
              <?php foreach ($services as $s): ?>
              <option value="<?= $s['id'] ?>" data-price="<?= $s['base_price'] ?>"
                <?= ($_POST['service_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
                <?= e($s['name']) ?> (₱<?= $s['base_price'] ?> / <?= e($s['unit_label']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="quantity">Quantity</label>
              <input type="number" id="quantity" name="quantity" class="form-control" min="1"
                value="<?= (int)($_POST['quantity'] ?? 1) ?>" oninput="updateTotal()">
            </div>
            <div class="form-group">
              <label class="form-label" for="paper_size">Paper Size</label>
              <select id="paper_size" name="paper_size" class="form-control">
                <option value="">— Select —</option>
                <option <?= ($_POST['paper_size'] ?? '') == 'A4' ? 'selected' : '' ?>>A4</option>
                <option <?= ($_POST['paper_size'] ?? '') == 'Short (Legal)' ? 'selected' : '' ?>>Short (Legal)</option>
                <option <?= ($_POST['paper_size'] ?? '') == 'Long (Legal)' ? 'selected' : '' ?>>Long (Legal)</option>
                <option <?= ($_POST['paper_size'] ?? '') == '4R (4×6)' ? 'selected' : '' ?>>4R (4×6)</option>
                <option <?= ($_POST['paper_size'] ?? '') == '5R (5×7)' ? 'selected' : '' ?>>5R (5×7)</option>
                <option <?= ($_POST['paper_size'] ?? '') == 'Custom' ? 'selected' : '' ?>>Custom</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="total_amount">Estimated Total (₱)</label>
            <!-- REMOVED readonly attribute to allow manual editing -->
            <input type="number" id="total_amount" name="total_amount" class="form-control" step="0.01"
              value="<?= $_POST['total_amount'] ?? '' ?>" placeholder="Auto-calculated">
          </div>

          <div class="form-group full">
            <label class="form-label" for="notes">Special Instructions / Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="4"
              placeholder="Tell us about your order – quantity, size, deadline, etc."><?= e($_POST['notes'] ?? '') ?></textarea>
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

          <button type="submit" class="btn btn-accent form-submit">Submit Order</button>
        </form>
      </div>
    </div>
  </div>
</section>

<script>
function updateTotal() {
  const sel = document.getElementById('service_id');
  const price = parseFloat(sel.options[sel.selectedIndex]?.dataset.price) || 0;
  const qty = parseInt(document.getElementById('quantity').value) || 1;
  document.getElementById('total_amount').value = (price * qty).toFixed(2);
}
function showFiles(input) {
  const list = document.getElementById('file-preview');
  list.innerHTML = [...input.files].map(f => `
    <div class="file-item">
      <span class="file-nm">${f.name}</span>
      <span class="file-sz">${(f.size/1024/1024).toFixed(1)} MB</span>
    </div>`).join('');
}
document.getElementById('service_id').addEventListener('change', updateTotal);
updateTotal();

// Drag & drop handling
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

<?php render_public_footer(); ?>