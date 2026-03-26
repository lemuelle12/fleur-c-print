<?php
// ── admin/auth_check.php ─────────────────────────────────────
session_start();

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (
    empty($_SESSION['logged_in']) ||
    (time() - ($_SESSION['last_active'] ?? 0)) > SESSION_TIMEOUT
) {
    session_destroy();
    header('Location: /fleur-c-print/login.php');  // ← ADD /fleur-c-print/
    exit;
}

$_SESSION['last_active'] = time();
?>