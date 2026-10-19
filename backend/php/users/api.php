<?php
/* User accounts (admin only).  ?action=list | save | delete */
require_once __DIR__ . '/../config/helpers.php';
$me = require_role('admin');
$pdo = db();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $rows = $pdo->query('SELECT id, username, full_name, role, is_active, created_at FROM users ORDER BY username')->fetchAll();
    json_out(['success' => true, 'users' => $rows]);
}

if ($action === 'save') {
    only_post();
    $b = body();
    $id = (int)($b['id'] ?? 0);
    $username = clean($b['username'] ?? '');
    $name = clean($b['full_name'] ?? '');
    $role = $b['role'] ?? '';
    $pass = (string)($b['password'] ?? '');
    $active = !empty($b['is_active']) ? 1 : 0;
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) fail('Username: 3-50 letters, numbers or underscore.');
    if ($name === '' || strlen($name) > 100)              fail('Full name is required.');
    if (!in_array($role, ['admin', 'cashier'], true))     fail('Choose a valid role.');
    if ($id === 0 && strlen($pass) < 6)                   fail('Password must be at least 6 characters.');
    if ($id > 0 && $pass !== '' && strlen($pass) < 6)     fail('Password must be at least 6 characters.');
    if ($id === $me['id'] && ($role !== 'admin' || !$active)) fail('You cannot demote or deactivate your own account.');
    try {
        if ($id > 0) {
            $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, is_active=? WHERE id=?')->execute([$username, $name, $role, $active, $id]);
            if ($pass !== '') $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
        } else {
            $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role, is_active) VALUES (?,?,?,?,?)')
                ->execute([$username, $name, password_hash($pass, PASSWORD_DEFAULT), $role, $active]);
        }
    } catch (PDOException $e) {
        if (is_duplicate($e)) fail('That username is already taken.');
        throw $e;
    }
    json_out(['success' => true, 'message' => $id > 0 ? 'User updated.' : 'User added.']);
}

if ($action === 'delete') {
    only_post();
    $id = (int)(body()['id'] ?? 0);
    if ($id === $me['id']) fail('You cannot delete your own account.');
    try {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1451) fail('This user has recorded sales. Deactivate the account instead.');
        throw $e;
    }
    json_out(['success' => true, 'message' => 'User deleted.']);
}
fail('Unknown action.');
