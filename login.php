<?php
// ── login.php ────────────────────────────────────────────────
session_start();
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

// CHECK: Already logged in
if (!empty($_SESSION['logged_in'])) {
    header('Location: /fleur-c-print/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_locked_out()) {
        $error = 'Too many failed attempts. Please wait ' . LOCKOUT_MINUTES . ' minutes.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (
            $username === AUTH_USERNAME &&
            password_verify($password, AUTH_PASSWORD_HASH)
        ) {
            session_regenerate_id(true);
            $_SESSION['logged_in']   = true;
            $_SESSION['last_active'] = time();
            
            header('Location: /fleur-c-print/admin/index.php');
            exit;
        } else {
            log_failed_login();
            $error = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Fleur C Print</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--white:#fff;--bg:#f7f6f4;--border:#e4e2de;--border2:#d5d2cc;--text:#1a1917;--text-2:#5a574f;--text-3:#9b9790;--accent:#c07b54;--red:#b94040;--red-lt:#faeaea;--radius:4px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Outfit',sans-serif;background:var(--bg);display:flex;align-items:center;justify-content:center;min-height:100vh;color:var(--text);-webkit-font-smoothing:antialiased;position:relative}
.wrap{width:320px;animation:rise .45s ease both}
@keyframes rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.brand{text-align:center;margin-bottom:40px}
.wordmark{font-family:'Cormorant Garamond',serif;font-size:34px;font-weight:300;letter-spacing:3px;text-transform:uppercase}
.wordmark em{font-style:italic;color:var(--accent)}
.tagline{font-size:9px;font-weight:500;letter-spacing:3px;text-transform:uppercase;color:var(--text-3);margin-top:6px}
.rule{width:28px;height:1px;background:var(--accent);margin:10px auto 0}
.field{margin-bottom:20px}
.field label{display:block;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-3);margin-bottom:7px}
.field input{width:100%;border:none;border-bottom:1.5px solid var(--border2);background:transparent;color:var(--text);padding:9px 2px;font-size:14px;outline:none;transition:border-color .15s;font-family:'Outfit',sans-serif}
.field input:focus{border-color:var(--accent)}
.err{font-size:11px;color:var(--red);min-height:16px;margin-bottom:14px;background:var(--red-lt);padding:<?= $error?'8px 12px':'0' ?>;border-radius:var(--radius)}
.submit{width:100%;padding:13px;background:var(--text);color:#fff;border:none;cursor:pointer;font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;transition:background .15s;border-radius:var(--radius);font-family:'Outfit',sans-serif}
.submit:hover{background:var(--accent)}
.hint{text-align:center;font-size:11px;color:var(--text-3);margin-top:14px}

/* Back to Main Page Button - Top Right */
.back-link{
  position:fixed;
  top:24px;
  right:24px;
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 18px;
  background:var(--white);
  border:1.5px solid var(--border2);
  border-radius:var(--radius);
  color:var(--text-2);
  font-size:12px;
  font-weight:500;
  letter-spacing:0.5px;
  text-decoration:none;
  transition:all .2s ease;
  z-index:100;
}
.back-link:hover{
  border-color:var(--accent);
  color:var(--accent);
  background:var(--white);
}
.back-link svg{
  width:14px;
  height:14px;
  stroke:currentColor;
  stroke-width:2;
  fill:none;
}
</style>
</head>
<body>

<!-- Back to Main Page Button -->
<a href="/fleur-c-print/index.php" class="back-link">
  <svg viewBox="0 0 24 24">
    <path d="M19 12H5M12 19l-7-7 7-7"/>
  </svg>
  Back to Main Page
</a>

<div class="wrap">
  <div class="brand">
    <div class="wordmark">Fleur <em>C</em> Print</div>
    <div class="tagline">Printing Services</div>
    <div class="rule"></div>
  </div>
  <form method="POST">
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" autocomplete="username" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
   <?php if ($error): ?>
      <div class="err"><?= e($error) ?></div>
   <?php endif; ?>
    <button type="submit" class="submit">Sign In</button>
  </form>
  <div class="hint">Fleur C Print · Admin Panel</div>
</div>
</body>
</html>