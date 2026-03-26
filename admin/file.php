<?php
// ── admin/file.php ───────────────────────────────────────────
// Serves uploaded files securely — never exposes real file paths.
// Usage: /admin/file.php?id=42&download=1
//        /admin/file.php?id=42          (inline preview for PDF)

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$file_id = (int)($_GET['id'] ?? 0);
if (!$file_id) { http_response_code(400); die('Invalid request.'); }

$stmt = db()->prepare('SELECT * FROM order_files WHERE id = ?');
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) { http_response_code(404); die('File not found.'); }

$path = UPLOAD_BASE . $file['file_path'];
if (!file_exists($path)) { http_response_code(404); die('File missing on disk.'); }

$download  = (int)($_GET['download'] ?? 0);
$is_pdf    = ($file['mime_type'] === 'application/pdf');
$disp_type = ($download || !$is_pdf) ? 'attachment' : 'inline';

header('Content-Type: '       . $file['mime_type']);
header('Content-Length: '     . filesize($path));
header('Content-Disposition: ' . $disp_type . '; filename="' . rawurlencode($file['file_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
