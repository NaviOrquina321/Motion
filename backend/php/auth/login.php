<?php
/* POST {username, password} -> starts a session and returns redirect by role. Includes rate limiting. */
require_once __DIR__ . '/../config/helpers.php';
only_post(false);

// Rate limiting check using $_SESSION
$now = time();
if (!empty($_SESSION['login_lockout']) && $_SESSION['login_lockout'] > $now) {
    $wait = ceil(($_SESSION['login_lockout'] - $now) / 60);
    fail("Too many failed login attempts. Account temporarily locked for {$wait} minute(s).", 429);
}

$b = body();
$username = clean($b['username'] ?? '');
$password = (string)($b['password'] ?? '');

if ($username === '' || $password === '') fail('Username and password are required.');
if (strlen($username) > 50 || strlen($password) > 100) fail('Invalid username or password.');

$st = db()->prepare('SELECT id, username, full_name, password_hash, role FROM users WHERE username = ? AND is_active = 1');
$st->execute([$username]);
$u = $st->fetch();

if (!$u || !password_verify($password, $u['password_hash'])) {
    $_SESSION['login_failures'] = ($_SESSION['login_failures'] ?? 0) + 1;
    if ($_SESSION['login_failures'] >= 5) {
        $_SESSION['login_lockout'] = $now + 300; // 5 minutes lockout
    }
    log_activity('login_failed', "Failed login attempt for username: $username", null, $username);
    fail('Incorrect username or password.', 401);
}

// Reset failure count on success
$_SESSION['login_failures'] = 0;
unset($_SESSION['login_lockout']);

session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['user'] = [
    'id'        => (int)$u['id'],
    'username'  => $u['username'],
    'full_name' => $u['full_name'],
    'role'      => $u['role'],
];

log_activity('user_login', "User {$u['username']} logged in successfully.", (int)$u['id'], $u['username']);

json_out([
    'success'    => true,
    'user'       => $_SESSION['user'],
    'csrf_token' => $_SESSION['csrf_token'],
    'redirect'   => $u['role'] === 'admin' ? 'dashboard.html' : 'pos.html',
]);
