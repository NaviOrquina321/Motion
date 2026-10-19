<?php
/* POST {full_name, username, password, confirm_password} -> Registers a new user account, starts a session, and returns redirect. */
require_once __DIR__ . '/../config/helpers.php';
only_post(false);

$b = body();
$fullName = clean($b['full_name'] ?? '');
$username = clean($b['username'] ?? '');
$password = (string)($b['password'] ?? '');
$confirmPass = (string)($b['confirm_password'] ?? '');

if ($fullName === '') fail('Full name is required.');
if (strlen($fullName) > 100) fail('Full name cannot exceed 100 characters.');
if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) fail('Username must be 3-50 letters, numbers, or underscores.');
if (strlen($password) < 6) fail('Password must be at least 6 characters long.');
if ($password !== $confirmPass) fail('Passwords do not match.');

$pdo = db();

try {
    $passHash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role, is_active) VALUES (?, ?, ?, "cashier", 1)');
    $st->execute([$username, $fullName, $passHash]);
    $userId = (int)$pdo->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['user'] = [
        'id'        => $userId,
        'username'  => $username,
        'full_name' => $fullName,
        'role'      => 'cashier',
    ];

    log_activity('user_register', "New user account registered: {$username}", $userId, $username);

    json_out([
        'success'    => true,
        'message'    => 'Account registered successfully!',
        'user'       => $_SESSION['user'],
        'csrf_token' => $_SESSION['csrf_token'],
        'redirect'   => 'pos.html',
    ]);
} catch (PDOException $e) {
    if (is_duplicate($e)) {
        fail('That username is already taken. Please choose another one.');
    }
    throw $e;
}
