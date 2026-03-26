<?php
// ── config/database.php ──────────────────────────────────────
// PDO connection singleton.
// Reads credentials from environment variables first, then falls back
// to local defaults for development. Never hardcode production passwords here.
//
// Set env vars in your .env file (loaded via dotenv or server config):
//   DB_HOST=localhost
//   DB_NAME=fleur_c_print
//   DB_USER=your_db_user
//   DB_PASS=your_db_password

// Load .env if it exists (simple key=value parser, no extra library needed)
$_env_file = dirname(__DIR__) . '/.env';
if (file_exists($_env_file)) {
    foreach (file($_env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#') || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k); $_v = trim($_v);
        if (!empty($_k) && !isset($_ENV[$_k])) {
            $_ENV[$_k] = $_v;
            putenv("$_k=$_v");
        }
    }
}

define('DB_HOST',    $_ENV['DB_HOST']    ?? 'localhost');
define('DB_NAME',    $_ENV['DB_NAME']    ?? 'fleur_c_print');
define('DB_USER',    $_ENV['DB_USER']    ?? 'root');       // override via .env in production
define('DB_PASS',    $_ENV['DB_PASS']    ?? '');           // override via .env in production
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(503);
            // Show a clean error page instead of a raw die() string
            $err_page = dirname(__DIR__) . '/public/error_db.php';
            if (file_exists($err_page)) {
                include $err_page;
            } else {
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Service Unavailable</title>'
                   . '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f7f6f4}</style></head>'
                   . '<body><div style="text-align:center"><h2>We\'ll be right back</h2>'
                   . '<p style="color:#9b9790">A technical issue occurred. Please try again in a moment.</p></div></body></html>';
            }
            exit;
        }
    }
    return $pdo;
}
