<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WebDev</title>
  <link rel="stylesheet" href="/fleur-c-print/style.css">
</head>
<body>
  
</body>
</html>
<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/public/header.php';
require_once __DIR__ . '/public/footer.php';

$services = db()->query(
    "SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order"
)->fetchAll();


$featured = array_slice($services, 0, 4);

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

render_public_header('Quality Printing Services', 'home');
?>

<!-- ── HERO ──────────────────────────────────────────────── -->
<section class="hero">
  <div class="container">
    <div class="hero-inner">

      <div class="hero-left">
        <p class="label hero-label">San Jose del Monte, Bulacan</p>
        <h1 class="hero-title">
          Print it <em>right,</em><br>
          print it <em>fast.</em>
        </h1>
        <p class="hero-body">
          From documents and ID photos to tarpaulins and stickers — Fleur C Print delivers quality output every time. Walk-ins welcome.
        </p>
        <div class="hero-actions">
          <a href="/fleur-c-print/contact.php"  class="btn btn-dark">Get a Quote</a>
          <a href="/fleur-c-print/services.php" class="btn btn-outline">See All Services</a>
        </div>
      </div>

      <!-- Pricing preview card (right side, hidden on mobile) -->
      <div class="hero-right">
        <div class="hero-badge">Live Pricing</div>
        <div class="hero-card">
          <div class="hero-card-title">Popular Services</div>
          <div class="hero-stat-list">
            <?php foreach ($featured as $svc): ?>
            <div class="hero-stat">
              <span class="svc"><?= e($svc['name']) ?></span>
              <span class="price">
                ₱<?= number_format($svc['base_price'], 2) ?>
                <small style="font-family:'Outfit',sans-serif;font-size:11px;color:var(--text-3);font-weight:400;">
                  /<?= e($svc['unit_label']) ?>
                </small>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
          <p style="font-size:11px;color:var(--text-3);margin-top:14px;text-align:center;">
            Prices updated by admin in real time
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ── MARQUEE ────────────────────────────────────────────── -->
<div class="marquee-wrap">
  <div class="marquee-track">
    <?php
    // Duplicate the list so the scroll loops seamlessly
    $items = ['Document Printing', 'ID Photos', 'Tarpaulin', 'Stickers', 'Brochures', 'Lamination', 'Fast Turnaround', 'GCash Accepted'];
    $all   = array_merge($items, $items); // duplicate for seamless loop
    foreach ($all as $item):
    ?>
    <div class="marquee-item">
      <div class="marquee-dot"></div>
      <?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── FEATURED SERVICES ──────────────────────────────────── -->
<section class="section section-alt">
  <div class="container">
    <div class="services-header">
      <div>
        <p class="label">What we offer</p>
        <div class="rule"></div>
        <h2 class="section-title">Our <em>Services</em></h2>
        <p class="section-sub">Prices are updated live from our admin panel.</p>
      </div>
      <a href="/fleur-c-print/services.php" class="btn btn-outline">View All</a>
    </div>

    <div class="services-grid">
      <?php foreach ($featured as $svc): ?>
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
  </div>
</section>

<!-- ── HOW IT WORKS ───────────────────────────────────────── -->
<section class="section">
  <div class="container">
    <p class="label">Simple process</p>
    <div class="rule"></div>
    <h2 class="section-title">How it <em>works</em></h2>

    <div class="steps">
      <div class="step">
        <div class="step-num">01</div>
        <div class="step-title">Send your file</div>
        <div class="step-body">Walk in, message us on GCash, or send your file via chat. We accept PDF, DOCX, JPG, PNG, and more.</div>
      </div>
      <div class="step">
        <div class="step-num">02</div>
        <div class="step-title">We confirm &amp; print</div>
        <div class="step-body">We review your order, confirm the details, and get it printed right away.</div>
      </div>
      <div class="step">
        <div class="step-num">03</div>
        <div class="step-title">Pick up or notify</div>
        <div class="step-body">We'll let you know when it's ready. Walk in to pick up and pay — Cash or GCash accepted.</div>
      </div>
      <div class="step">
        <div class="step-num">04</div>
        <div class="step-title">Done!</div>
        <div class="step-body">Quality output, every time. Come back whenever you need — we remember your preferences.</div>
      </div>
    </div>
  </div>
</section>

<!-- ── WHY US ─────────────────────────────────────────────── -->
<section class="section section-alt">
  <div class="container">
    <p class="label">Why choose us</p>
    <div class="rule"></div>
    <h2 class="section-title">Built for <em>your needs</em></h2>

    <div class="why-grid">
      <div class="why-item">
        <div class="why-num">01</div>
        <div class="why-title">Fast Turnaround</div>
        <div class="why-body">Most orders are ready in minutes, not hours. We know your time matters.</div>
      </div>
      <div class="why-item">
        <div class="why-num">02</div>
        <div class="why-title">Transparent Pricing</div>
        <div class="why-body">No hidden fees. What you see on our services page is exactly what you pay.</div>
      </div>
      <div class="why-item">
        <div class="why-num">03</div>
        <div class="why-title">GCash Accepted</div>
        <div class="why-body">Pay with cash or GCash — whatever is convenient for you.</div>
      </div>
      <div class="why-item">
        <div class="why-num">04</div>
        <div class="why-title">Quality Output</div>
        <div class="why-body">Sharp prints, accurate colors, and careful finishing — every single order.</div>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA STRIP ──────────────────────────────────────────── -->
<section class="section" style="background:var(--text);padding:64px 0;">
  <div class="container" style="text-align:center;">
    <p class="label" style="color:rgba(255,255,255,.4);">Ready to print?</p>
    <h2 class="section-title" style="color:rgba(255,255,255,.9);margin-top:10px;">
      Let's get your <em style="color:var(--accent);">order started</em>
    </h2>
    <p style="font-size:14px;color:rgba(255,255,255,.5);margin:16px auto 32px;max-width:400px;">
      Send us your file or drop by the shop. Walk-ins are always welcome.
    </p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
      <a href="/fleur-c-print/contact.php"  class="btn btn-accent">Send a Message</a>
      <a href="/fleur-c-print/services.php" class="btn" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.2);">View Pricing</a>
    </div>
  </div>
</section>

<?php render_public_footer(); ?>