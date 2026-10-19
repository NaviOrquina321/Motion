<?php
/* POST {username, full_name, password} -> registers a new user (role cashier), starts a session, and returns redirect URL. */
require_once __DIR__ . '/../config/helpers.php';
only_post();

$b = body();
$username = clean($b['username'] ?? '');
$fullName = clean($b['full_name'] ?? '');
$password = (string)($b['password'] ?? '');

if ($username === '' || $fullName === '' || $password === '') {
    fail('All fields are required.');
}

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    fail('Username must be 3-50 letters, numbers, or underscores.');
}

if (strlen($fullName) > 100) {
    fail('Full name must be 100 characters or less.');
}

if (strlen($password) < 6) {
    fail('Password must be at least 6 characters long.');
}

$pdo = db();

try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role, is_active) VALUES (?, ?, ?, "cashier", 1)');
    $st->execute([$username, $fullName, $hash]);
    $newId = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    if (is_duplicate($e)) {
        fail('Username is already taken.');
    }
    throw $e;
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id'        => $newId,
    'username'  => $username,
    'full_name' => $fullName,
    'role'      => 'cashier',
];

json_out([
    'success'  => true,
    'message'  => 'Registration successful.',
    'user'     => $_SESSION['user'],
    'redirect' => 'pos.html',
]);
