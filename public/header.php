<?php
// ── public/header.php ────────────────────────────────────────
// Usage: render_public_header('Page Title', 'nav-key');
// nav-key options: 'home', 'services', 'contact'

function render_public_header(string $title, string $active = 'home'): void {
    $base = '/fleur-c-print';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Fleur C Print — Quality printing services. Documents, ID photos, tarpaulins, stickers, and more.">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — Fleur C Print</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/fleur-c-print/style.css">
</head>
<body>

<header id="nav">
  <div class="container">
    <div class="nav-inner">

      <!-- Wordmark -->
      <a href="<?= $base ?>/" class="nav-wordmark">
        Fleur <em>C</em> Print
      </a>

      <!-- Desktop nav links -->
      <ul class="nav-links">
        <li><a href="<?= $base ?>/"             class="<?= $active === 'home'     ? 'active' : '' ?>">Home</a></li>
        <li><a href="<?= $base ?>/services.php" class="<?= $active === 'services' ? 'active' : '' ?>">Services</a></li>
        <li><a href="<?= $base ?>/order.php"  class="<?= $active === 'order'  ? 'active' : '' ?>">Orders</a></li>
        <li><a href="<?= $base ?>/contact.php"  class="<?= $active === 'contact'  ? 'active' : '' ?>">Contact</a></li>
      </ul>

      <!-- Desktop CTAs: Quote button + subtle Admin link -->
      <div class="nav-cta">
        <a href="<?= $base ?>/contact.php" class="btn btn-dark">Get a Quote</a>
        <a href="<?= $base ?>/login.php" class="nav-admin-link" title="Admin Panel">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
          </svg>
          <span>Admin</span>
        </a>
      </div>

      <!-- Mobile burger -->
      <button class="nav-burger" id="burger" aria-label="Toggle navigation">
        <span></span>
        <span></span>
        <span></span>
      </button>

    </div><!-- /.nav-inner -->

    <!-- Mobile nav dropdown -->
    <nav class="nav-mobile" id="nav-mobile">
      <a href="<?= $base ?>/"             class="<?= $active === 'home'     ? 'active' : '' ?>">Home</a>
      <a href="<?= $base ?>/services.php" class="<?= $active === 'services' ? 'active' : '' ?>">Services</a>
      <a href="<?= $base ?>/order.php"  class="<?= $active === 'order'  ? 'active' : '' ?>">Order &amp; Quote</a>
      <a href="<?= $base ?>/contact.php"  class="<?= $active === 'contact'  ? 'active' : '' ?>">Contact &amp; Quote</a>
      <a href="<?= $base ?>/login.php"    style="opacity:.5;font-size:12px;">Admin Login</a>
    </nav>

  </div><!-- /.container -->
</header>

<style>
/* Admin link — subtle, tucked beside the CTA */
.nav-admin-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--text-3);
  text-decoration: none;
  padding: 6px 10px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: color .15s, border-color .15s;
  margin-left: 8px;
}
.nav-admin-link:hover {
  color: var(--text);
  border-color: var(--text-3);
}
.nav-admin-link svg {
  flex-shrink: 0;
}
</style>

<script>
window.addEventListener('scroll', () => {
  document.getElementById('nav').classList.toggle('scrolled', window.scrollY > 10);
});
document.getElementById('burger').addEventListener('click', () => {
  document.getElementById('nav-mobile').classList.toggle('open');
});
</script>

<?php } // end render_public_header ?>
