<?php
// ── config/auth.php ──────────────────────────────────────────
// Single-operator credentials.
// To change password: run  password_hash('yourpassword', PASSWORD_BCRYPT)
// in a PHP console and paste the result into AUTH_PASSWORD_HASH below.
// Then remove the SETUP_COMPLETE line once done.

define('AUTH_USERNAME',     'admin');
define('AUTH_PASSWORD_HASH', '$2y$10$c7kmgNA/RJE3S63r0jDznOSYO2EvRyH39DZkuSun7gljyG4EYDLPe');

define('SETUP_COMPLETE', true);   // <-- ADD THIS LINE

// ── SAFETY GUARD ─────────────────────────────────────────────
if (!defined('SETUP_COMPLETE')) {
    $default_hash = '$2y$10$c7kmgNA/RJE3S63r0jDznOSYO2EvRyH39DZkuSun7gljyG4EYDLPe';
    if (AUTH_PASSWORD_HASH === $default_hash) {
        http_response_code(503);
        die(
            '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f7f6f4}</style>' .
            '<div style="max-width:440px;padding:32px;border:1px solid #e4e2de;border-radius:6px;background:#fff">' .
            '<h2 style="font-size:18px;margin:0 0 12px">Setup required</h2>' .
            '<p style="font-size:14px;color:#5a574f;line-height:1.6">The admin panel is using the default password.<br>' .
            'Open <code>config/auth.php</code>, generate a new hash with<br>' .
            '<code>password_hash(\'your-password\', PASSWORD_BCRYPT)</code><br>' .
            'and replace <code>AUTH_PASSWORD_HASH</code>. Then add<br>' .
            '<code>define(\'SETUP_COMPLETE\', true);</code> to the same file.</p>' .
            '</div>'
        );
    }
}

define('SESSION_TIMEOUT',    28800); // 8 hours in seconds
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_MINUTES',    15);
define('SHOP_NAME',          'Fleur C Print');
define('SHOP_HOURS',         '8:00 AM – 6:00 PM');

define('BASE_URL', '/');   // Railway serves from root
// Upload settings
define('UPLOAD_BASE',   dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50 MB

define('ALLOWED_MIME_TYPES', [
    'application/pdf'                                                         => 'pdf',
    'image/jpeg'                                                              => 'jpg',
    'image/png'                                                               => 'png',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/msword'                                                      => 'doc',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'      => 'xlsx',
]);
