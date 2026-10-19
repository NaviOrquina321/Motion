<?php
/* Shared helpers: session start, JSON output, login/role checks, CSRF, activity logging. Included by every API file. */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function ($e) {
    error_log($e->getMessage());
    fail('Something went wrong.', 500);
});

function ensure_assets_copied() {
    static $done = false;
    if ($done) return;
    $done = true;
    $assetDir = __DIR__ . '/../../assets/products/';
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    if (is_dir($assetDir)) {
        foreach (glob($assetDir . '*.*') as $f) {
            $dest = $uploadDir . basename($f);
            if (!file_exists($dest)) {
                @copy($f, $dest);
            }
        }
    }
}
ensure_assets_copied();

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function fail($message, $code = 400) {
    json_out(['success' => false, 'message' => $message], $code);
}

function verify_csrf() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? (body()['csrf_token'] ?? '');
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        fail('CSRF token validation failed.', 403);
    }
}

function only_post($checkCsrf = true) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST request required.', 405);
    if ($checkCsrf) verify_csrf();
}

/* Reads JSON body (fetch with JSON) or falls back to normal form fields. */
function body() {
    static $b = null;
    if ($b === null) {
        $j = json_decode(file_get_contents('php://input') ?: '', true);
        $b = is_array($j) ? $j : $_POST;
    }
    return $b;
}

function require_login() {
    if (empty($_SESSION['user'])) fail('Please log in first.', 401);

    $now = time();
    $lastCheck = $_SESSION['user']['last_check'] ?? 0;
    if ($now - $lastCheck > 30) {
        try {
            $pdo = db();
            $st = $pdo->prepare('SELECT id, username, full_name, role, is_active FROM users WHERE id = ?');
            $st->execute([$_SESSION['user']['id']]);
            $u = $st->fetch();
            if (!$u || empty($u['is_active'])) {
                unset($_SESSION['user']);
                fail('Your account has been deactivated or disabled.', 401);
            }
            $_SESSION['user']['role'] = $u['role'];
            $_SESSION['user']['full_name'] = $u['full_name'];
            $_SESSION['user']['username'] = $u['username'];
            $_SESSION['user']['last_check'] = $now;
        } catch (Throwable $e) {
            // keep session on DB transient issue
        }
    }

    return $_SESSION['user'];
}

function require_role($role) {
    $u = require_login();
    if ($u['role'] !== $role) fail('Access denied. Admin only.', 403);
    return $u;
}

function clean($v) {
    return trim(strip_tags((string)$v));
}

function is_duplicate($e) {
    return $e instanceof PDOException && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062;
}

function log_activity($action, $details = null, $userId = null, $username = null) {
    try {
        $pdo = db();
        if ($userId === null && isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
        }
        if ($username === null && isset($_SESSION['user']['username'])) {
            $username = $_SESSION['user']['username'];
        }
        if (!$username) $username = 'system';

        $st = $pdo->prepare('INSERT INTO activity_logs (user_id, username, action, details) VALUES (?, ?, ?, ?)');
        $st->execute([$userId, $username, $action, is_array($details) || is_object($details) ? json_encode($details) : (string)$details]);
    } catch (Throwable $e) {
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}
