<?php
// ── includes/functions.php ───────────────────────────────────

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';

// ── OUTPUT ESCAPING ──────────────────────────────────────────
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── CSRF ────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void {
    if (!isset($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(403);
        die('Invalid request — CSRF token mismatch.');
    }
}

// ── FLASH MESSAGES ───────────────────────────────────────────
function flash(string $msg, string $type = 'info'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── REF CODE GENERATOR ───────────────────────────────────────
function next_ref_code(): string {
    $stmt = db()->query('SELECT MAX(id) AS max_id FROM orders');
    $row  = $stmt->fetch();
    $next = ($row['max_id'] ?? 0) + 1;
    return 'PRNT-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// ── STATUS BADGE HTML ────────────────────────────────────────
function status_badge(string $status): string {
    $map = [
        'pending'     => 'badge-pending',
        'in-progress' => 'badge-in-progress',
        'ready'       => 'badge-ready',
        'completed'   => 'badge-completed',
        'cancelled'   => 'badge-cancelled',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return '<span class="badge ' . $cls . '">' . e($status) . '</span>';
}

function pay_badge(string $status): string {
    $map = [
        'unpaid'  => 'badge-unpaid',
        'partial' => 'badge-partial',
        'paid'    => 'badge-paid',
    ];
    $cls = $map[$status] ?? 'badge-unpaid';
    return '<span class="badge ' . $cls . '">' . e($status) . '</span>';
}

// ── FILE UPLOAD ──────────────────────────────────────────────
// Returns an array on success, or a string error message on failure.
// Callers should check: is_array($result) for success, is_string($result) for error.
function handle_upload(array $file, int $order_id): array|string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $codes = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp directory missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        ];
        return $codes[$file['error']] ?? 'Unknown upload error (code ' . $file['error'] . ').';
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return 'File "' . basename($file['name']) . '" exceeds the 50 MB size limit.';
    }

    // Validate MIME via finfo — never trust the extension
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!array_key_exists($mime, ALLOWED_MIME_TYPES)) {
        return 'File type not allowed: ' . $mime . '. Accepted: PDF, JPG, PNG, DOCX, XLSX.';
    }

    $ext    = ALLOWED_MIME_TYPES[$mime];
    $uuid   = bin2hex(random_bytes(16));
    $stored = $uuid . '.' . $ext;
    $month  = date('Y-m');
    $dir    = UPLOAD_BASE . $month . '/' . $order_id . '/';

    if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
        return 'Could not create upload directory. Check folder permissions.';
    }

    $dest = $dir . $stored;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return 'Could not save "' . basename($file['name']) . '" to disk.';
    }

    return [
        'file_name' => basename($file['name']),
        'file_path' => $month . '/' . $order_id . '/' . $stored,
        'file_size' => $file['size'],
        'mime_type' => $mime,
    ];
}

// ── LOGIN RATE LIMIT (session-based) ─────────────────────────
// Uses $_SESSION instead of a log file — no unbounded file growth,
// and rate-limit state is naturally scoped per browser session.
function is_locked_out(): bool {
    $attempts = $_SESSION['login_attempts'] ?? [];
    $cutoff   = time() - (LOCKOUT_MINUTES * 60);
    $recent   = array_filter($attempts, fn($t) => $t > $cutoff);
    return count($recent) >= MAX_LOGIN_ATTEMPTS;
}

function log_failed_login(): void {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    $_SESSION['login_attempts'][] = time();

    // Also write to log file for audit trail (non-blocking)
    $log = dirname(__DIR__) . '/logs/failed_logins.log';
    $dir = dirname($log);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    if (is_dir($dir)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        @file_put_contents($log, time() . '|' . $ip . "\n", FILE_APPEND | LOCK_EX);
    }
}

// ── MONEY FORMAT ─────────────────────────────────────────────
function money(float $v): string {
    return '₱' . number_format($v, 2);
}
