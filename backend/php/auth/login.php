<?php
/* POST {username, password} -> starts a session and tells the frontend where to go next. */
require_once __DIR__ . '/../config/helpers.php';
only_post();

$b = body();
$username = clean($b['username'] ?? '');
$password = (string)($b['password'] ?? '');

if ($username === '' || $password === '') fail('Username and password are required.');
if (strlen($username) > 50 || strlen($password) > 100) fail('Invalid username or password.');

$st = db()->prepare('SELECT id, username, full_name, password_hash, role FROM users WHERE username = ? AND is_active = 1');
$st->execute([$username]);
$u = $st->fetch();

if (!$u || !password_verify($password, $u['password_hash'])) {
    fail('Incorrect username or password.', 401);
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => (int)$u['id'], 'username' => $u['username'],
    'full_name' => $u['full_name'], 'role' => $u['role'],
];
json_out([
    'success'  => true,
    'user'     => $_SESSION['user'],
    'redirect' => $u['role'] === 'admin' ? 'dashboard.html' : 'pos.html',
]);
