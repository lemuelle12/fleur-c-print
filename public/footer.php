<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ftr</title>
  <link rel="stylesheet" href="/fleur-c-print/style.css">
</head>
<body>
  
</body>
</html>
<?php
// ── public/footer.php ────────────────────────────────────────
// Usage: render_public_footer();

function render_public_footer(): void {
    $base = '/fleur-c-print';
    $year = date('Y');
?>

<footer id="footer">
  <div class="container">

    <div class="footer-inner">

      <!-- Brand column -->
      <div class="footer-brand">
        <div class="footer-wordmark">Fleur <em>C</em> Print</div>
        <p class="footer-tagline">
          Your trusted local printing partner. Fast turnaround, quality results, and friendly service — every order.
        </p>
      </div>

      <!-- Quick links -->
      <div>
        <div class="footer-col-title">Quick Links</div>
        <div class="footer-links">
          <a href="<?= $base ?>/">Home</a>
          <a href="<?= $base ?>/services.php">Services &amp; Pricing</a>
          <a href="<?= $base ?>/contact.php">Contact Us</a>
          <a href="<?= $base ?>/contact.php">Request a Quote</a>
        </div>
      </div>

      <!-- Contact info -->
      <div>
        <div class="footer-col-title">Contact</div>
        <div class="footer-links">
          <a href="tel:+63900000000">+63 900 000 0000</a>
          <a href="mailto:hello@fleurcprint.com">hello@fleurcprint.com</a>
          <span style="color:rgba(255,255,255,.35);font-size:12px;line-height:1.5">
            Mon – Sat<br>8:00 AM – 6:00 PM
          </span>
        </div>
      </div>

    </div><!-- /.footer-inner -->

    <div class="footer-bottom">
      <span class="footer-copy">&copy; <?= $year ?> Fleur C Print. All rights reserved.</span>
      <span class="footer-hours">Open Mon – Sat &middot; 8:00 AM – 6:00 PM</span>
    </div>

  </div><!-- /.container -->
</footer>

</body>
</html>
<?php } // end render_public_footer ?>