<?php
/* Discounts API (Admin for CRUD, Cashier/All for list). ?action=list | save | delete */
require_once __DIR__ . '/../config/helpers.php';
$user = require_login();
$pdo = db();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $st = $pdo->query('SELECT * FROM discounts ORDER BY name ASC');
    json_out(['success' => true, 'discounts' => $st->fetchAll()]);
}

if ($action === 'save') {
    only_post();
    require_role('admin');
    $b = body();
    $id = (int)($b['id'] ?? 0);
    $name = clean($b['name'] ?? '');
    $type = clean($b['type'] ?? 'percent');
    $value = filter_var($b['value'] ?? 0, FILTER_VALIDATE_FLOAT);
    $active = isset($b['is_active']) ? (int)$b['is_active'] : 1;

    if ($name === '' || strlen($name) > 100) fail('Discount name is required.');
    if (!in_array($type, ['percent', 'fixed'])) fail('Invalid discount type.');
    if ($value === false || $value < 0) fail('Enter a valid discount value.');
    if ($type === 'percent' && $value > 100) fail('Percentage discount cannot exceed 100%.');

    if ($id > 0) {
        $pdo->prepare('UPDATE discounts SET name=?, type=?, value=?, is_active=? WHERE id=?')
            ->execute([$name, $type, $value, $active, $id]);
        log_activity('discount_update', "Updated discount ID #{$id}: {$name}");
    } else {
        $pdo->prepare('INSERT INTO discounts (name, type, value, is_active) VALUES (?,?,?,?)')
            ->execute([$name, $type, $value, $active]);
        $id = (int)$pdo->lastInsertId();
        log_activity('discount_create', "Created discount ID #{$id}: {$name}");
    }
    json_out(['success' => true, 'message' => $id > 0 ? 'Discount updated.' : 'Discount added.']);
}

if ($action === 'delete') {
    only_post();
    require_role('admin');
    $id = (int)(body()['id'] ?? 0);
    $pdo->prepare('DELETE FROM discounts WHERE id = ?')->execute([$id]);
    log_activity('discount_delete', "Deleted discount ID #{$id}");
    json_out(['success' => true, 'message' => 'Discount deleted.']);
}

fail('Unknown action.');
