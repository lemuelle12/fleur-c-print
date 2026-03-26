<?php
require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/footer.php';

$db = db();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id         = (int)($_POST['id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $slug       = trim($_POST['slug'] ?? '');
        $price      = round((float)($_POST['base_price'] ?? 0), 2);
        $unit       = trim($_POST['unit_label'] ?? '');
        $mins       = (int)($_POST['est_minutes'] ?? 15);
        $sort       = (int)($_POST['sort_order'] ?? 0);

        if ($name && $slug && $price > 0) {
            if ($id) {
                $db->prepare('UPDATE services SET name=?,slug=?,base_price=?,unit_label=?,est_minutes=?,sort_order=? WHERE id=?')
                   ->execute([$name,$slug,$price,$unit,$mins,$sort,$id]);
                flash('Service updated.','success');
            } else {
                $db->prepare('INSERT INTO services (name,slug,base_price,unit_label,est_minutes,sort_order) VALUES (?,?,?,?,?,?)')
                   ->execute([$name,$slug,$price,$unit,$mins,$sort]);
                flash('Service added.','success');
            }
        }
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id'] ?? 0);
        $val = (int)($_POST['is_active'] ?? 0);
        $db->prepare('UPDATE services SET is_active=? WHERE id=?')->execute([$val,$id]);
        flash('Service status updated.','info');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM services WHERE id=?')->execute([$id]);
        flash('Service deleted.','info');
    }

    if ($action === 'price') {
        $id    = (int)($_POST['id'] ?? 0);
        $price = round((float)($_POST['base_price'] ?? 0), 2);
        $db->prepare('UPDATE services SET base_price=? WHERE id=?')->execute([$price,$id]);
        flash('Price updated.','success');
    }

    header('Location: ' . BASE_URL . 'admin/services.php'); exit;
}

$services = $db->query('SELECT * FROM services ORDER BY sort_order, id')->fetchAll();
$edit_id   = (int)($_GET['edit'] ?? 0);
$edit_svc  = $edit_id ? array_filter($services, fn($s) => $s['id'] === $edit_id) : null;
$edit_svc  = $edit_svc ? array_values($edit_svc)[0] : null;

render_header('Services & Prices', 'services');
?>

<div class="page-header">
  <div>
    <div class="page-title">Services <em>&amp; Prices</em></div>
    <div class="page-subtitle">Edit prices and manage your service catalog</div>
  </div>
  <a href="?edit=0" class="btn btn-primary">+ Add Service</a>
</div>

<?php if ($edit_id === 0 || $edit_svc): ?>
<div class="card" style="max-width:640px;margin-bottom:28px">
  <div class="card-title"><?= $edit_svc ? 'Edit Service' : 'New Service' ?></div>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit_svc['id'] ?? 0 ?>">
    <div class="form-grid">
      <div class="form-group full"><label class="form-label">Service Name *</label>
        <input class="form-control" name="name" required value="<?= e($edit_svc['name'] ?? '') ?>" placeholder="1×1 ID Photo"></div>
      <div class="form-group"><label class="form-label">Slug *</label>
        <input class="form-control" name="slug" required value="<?= e($edit_svc['slug'] ?? '') ?>" placeholder="photo_1x1"></div>
      <div class="form-group"><label class="form-label">Base Price (₱) *</label>
        <input class="form-control" type="number" step="0.01" name="base_price" required value="<?= $edit_svc['base_price'] ?? '' ?>" placeholder="0.00"></div>
      <div class="form-group"><label class="form-label">Unit Label</label>
        <input class="form-control" name="unit_label" value="<?= e($edit_svc['unit_label'] ?? '') ?>" placeholder="per piece"></div>
      <div class="form-group"><label class="form-label">Est. Minutes</label>
        <input class="form-control" type="number" name="est_minutes" value="<?= $edit_svc['est_minutes'] ?? 15 ?>"></div>
      <div class="form-group"><label class="form-label">Sort Order</label>
        <input class="form-control" type="number" name="sort_order" value="<?= $edit_svc['sort_order'] ?? 0 ?>"></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <button type="submit" class="btn btn-primary btn-sm">Save Service</button>
      <a href="<?= BASE_URL ?>admin/services.php" class="btn btn-outline btn-sm">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr>
      <th>Service</th><th>Slug</th><th>Base Price</th><th>Unit</th><th>Est. Min</th><th>Active</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($services as $s): ?>
    <tr>
      <td style="font-weight:500"><?= e($s['name']) ?></td>
      <td><span class="mono" style="font-size:11px;color:var(--text-3)"><?= e($s['slug']) ?></span></td>
      <td>
        <form method="POST" style="display:inline-flex;align-items:center;gap:8px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="price">
          <input type="hidden" name="id" value="<?= $s['id'] ?>">
          <input type="number" name="base_price" step="0.01" value="<?= $s['base_price'] ?>"
            style="background:transparent;border:none;border-bottom:1.5px solid var(--border2);width:70px;font-size:13px;padding:2px 0;outline:none;font-family:'Outfit',sans-serif;color:var(--text)">
          <button type="submit" class="btn btn-xs btn-outline">Save</button>
        </form>
      </td>
      <td style="color:var(--text-3)"><?= e($s['unit_label']) ?></td>
      <td class="mono" style="font-size:12px"><?= $s['est_minutes'] ?></td>
      <td>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= $s['id'] ?>">
          <input type="hidden" name="is_active" value="<?= $s['is_active'] ? 0 : 1 ?>">
          <label class="toggle-switch">
            <input type="checkbox" <?= $s['is_active'] ? 'checked' : '' ?> onchange="this.closest('form').submit()">
            <span class="toggle-track"></span>
          </label>
        </form>
      </td>
      <td>
        <a href="?edit=<?= $s['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this service?')">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $s['id'] ?>">
          <button type="submit" class="btn btn-xs btn-danger" style="margin-left:4px">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php render_footer(); ?>
