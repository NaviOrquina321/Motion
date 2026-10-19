<?php
/* POST {username, password} -> starts session. Rate limiting via login_attempts table. */
require_once __DIR__ . '/../config/helpers.php';
only_post(false);

$b = body();
$username = clean($b['username'] ?? '');
$password = (string)($b['password'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if ($username === '' || $password === '') fail('Username and password are required.');
if (strlen($username) > 50 || strlen($password) > 100) fail('Invalid username or password.');

$pdo = db();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

// Ensure table exists
try {
    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_attempts_user_ip (username, ip_address, attempted_at)
        ) ENGINE=InnoDB");
    }
} catch (Throwable $e) {}

$timeClause = ($driver === 'sqlite') ? "attempted_at >= datetime('now', '-5 minutes')" : "attempted_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";

// Rate limit check: count failures in last 5 minutes for username + ip
$st = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE username = ? AND ip_address = ? AND {$timeClause}");
$st->execute([$username, $ip]);
$attempts = (int)$st->fetchColumn();

if ($attempts >= 5) {
    fail('Too many failed login attempts. Account temporarily locked for 5 minutes.', 429);
}

$stUser = $pdo->prepare('SELECT id, username, full_name, password_hash, role, is_active FROM users WHERE username = ?');
$stUser->execute([$username]);
$u = $stUser->fetch();

if (!$u || empty($u['is_active']) || !password_verify($password, $u['password_hash'])) {
    $pdo->prepare('INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)')->execute([$username, $ip]);
    log_activity('login_failed', "Failed login attempt for username: $username from IP: $ip", null, $username);
    fail('Incorrect username or password.', 401);
}

// Clear failures on success
$pdo->prepare('DELETE FROM login_attempts WHERE username = ? AND ip_address = ?')->execute([$username, $ip]);

session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['user'] = [
    'id'         => (int)$u['id'],
    'username'   => $u['username'],
    'full_name'  => $u['full_name'],
    'role'       => $u['role'],
    'last_check' => time()
];

log_activity('user_login', "User {$u['username']} logged in successfully.", (int)$u['id'], $u['username']);

json_out([
    'success'    => true,
    'user'       => $_SESSION['user'],
    'csrf_token' => $_SESSION['csrf_token'],
    'redirect'   => $u['role'] === 'admin' ? 'dashboard.html' : 'pos.html',
]);
