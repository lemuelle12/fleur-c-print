<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services</title>
    <link rel="stylesheet" href="/fleur-c-print/style.css">
</head>
<body>
    
</body>
</html>
<?php
// ── services.php ─────────────────────────────────────────────
// Public services & pricing page.
// All data pulled live from MySQL — admin edits reflect instantly.

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/public/header.php';
require_once __DIR__ . '/public/footer.php';

// All active services from the same table the admin manages
$services = db()->query(
    "SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order"
)->fetchAll();

$icons = [
    'doc_bw'     => '🖨️',
    'doc_color'  => '🎨',
    'photo_1x1'  => '🪪',
    'photo_2x2'  => '🪪',
    'brochure'   => '📄',
    'tarpaulin'  => '🖼️',
    'sticker'    => '⭐',
    'lamination' => '✨',
];

render_public_header('Services & Pricing', 'services');
?>

<!-- Page hero -->
<section class="page-hero">
  <div class="container">
    <p class="label">What we offer</p>
    <div class="rule"></div>
    <h1 class="section-title">Services <em>&amp; Pricing</em></h1>
    <p class="section-sub">
      All prices below are current and managed by our admin panel. Walk-ins always welcome — no appointment needed.
    </p>
  </div>
</section>

<!-- All services grid -->
<section class="section">
  <div class="container">

    <?php if (empty($services)): ?>
    <p style="text-align:center;color:var(--text-3);padding:60px 0;">
      No services available right now. Please check back later or call us directly.
    </p>
    <?php else: ?>

    <div class="services-grid">
      <?php foreach ($services as $svc): ?>
      <div class="service-card">
        <div class="service-icon">
          <?= $icons[$svc['slug']] ?? '🖨️' ?>
        </div>
        <div class="service-name"><?= e($svc['name']) ?></div>
        <div class="service-unit"><?= e($svc['unit_label']) ?></div>
        <div class="service-price">
          ₱<?= number_format($svc['base_price'], 2) ?>
          <span><?= e($svc['unit_label']) ?></span>
        </div>
        <?php if ($svc['est_minutes']): ?>
        <div class="service-time">
          Est. <?= (int)$svc['est_minutes'] ?> min turnaround
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

    <!-- Notes -->
    <div style="margin-top:40px;padding:24px 28px;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);">
      <p class="label" style="margin-bottom:12px;">Good to know</p>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:10px;">
        <li style="font-size:13px;color:var(--text-2);">
          ✓ &nbsp;Prices shown are base rates. Final price may vary by size, quantity, or special finish.
        </li>
        <li style="font-size:13px;color:var(--text-2);">
          ✓ &nbsp;Bulk orders (50+ pieces) may qualify for a discount — ask us when you visit or send a message.
        </li>
        <li style="font-size:13px;color:var(--text-2);">
          ✓ &nbsp;We accept Cash and GCash. Partial payments available for large orders.
        </li>
        <li style="font-size:13px;color:var(--text-2);">
          ✓ &nbsp;Bring your file on USB, email it to us, or send via chat before you arrive.
        </li>
      </ul>
    </div>

  </div>
</section>

<!-- CTA -->
<section class="section section-alt">
  <div class="container" style="text-align:center;">
    <p class="label">Not sure what you need?</p>
    <div class="rule rule-center"></div>
    <h2 class="section-title">Send us a <em>message</em></h2>
    <p style="font-size:14px;color:var(--text-3);margin:12px auto 28px;max-width:400px;">
      Describe your project and we'll recommend the best service and give you an accurate quote.
    </p>
    <a href="/fleur-c-print/contact.php" class="btn btn-dark">Get a Free Quote</a>
  </div>
</section>

<?php render_public_footer(); ?>