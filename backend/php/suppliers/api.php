<?php
/* Suppliers API (Admin only). ?action=list (q) | save | delete */
require_once __DIR__ . '/../config/helpers.php';
require_role('admin');
$pdo = db();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $q = clean($_GET['q'] ?? '');
    $sql = 'SELECT s.*, COALESCE(COUNT(po.id), 0) as po_count
            FROM suppliers s
            LEFT JOIN purchase_orders po ON po.supplier_id = s.id';
    $args = [];
    if ($q !== '') {
        $sql .= ' WHERE s.name LIKE ? OR s.contact_person LIKE ? OR s.phone LIKE ? OR s.email LIKE ? OR s.id = ?';
        $args = ["%$q%", "%$q%", "%$q%", "%$q%", ctype_digit($q) ? (int)$q : 0];
    }
    $sql .= ' GROUP BY s.id ORDER BY s.name';
    $st = $pdo->prepare($sql);
    $st->execute($args);
    json_out(['success' => true, 'suppliers' => $st->fetchAll()]);
}

if ($action === 'save') {
    only_post();
    $b = body();
    $id      = (int)($b['id'] ?? 0);
    $name    = clean($b['name'] ?? '');
    $person  = clean($b['contact_person'] ?? '');
    $email   = clean($b['email'] ?? '');
    $phone   = clean($b['phone'] ?? '');
    $address = clean($b['address'] ?? '');

    if ($name === '' || strlen($name) > 120) fail('Supplier name is required (max 120 chars).');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Enter a valid email address.');

    try {
        if ($id > 0) {
            $pdo->prepare('UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, address=? WHERE id=?')
                ->execute([$name, $person ?: null, $email ?: null, $phone ?: null, $address ?: null, $id]);
            log_activity('supplier_update', "Updated supplier ID #{$id}: {$name}");
        } else {
            $pdo->prepare('INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?,?,?,?,?)')
                ->execute([$name, $person ?: null, $email ?: null, $phone ?: null, $address ?: null]);
            $id = (int)$pdo->lastInsertId();
            log_activity('supplier_create', "Created supplier ID #{$id}: {$name}");
        }
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) fail('A supplier with this name already exists.');
        throw $e;
    }

    json_out(['success' => true, 'message' => $id > 0 ? 'Supplier updated.' : 'Supplier added.']);
}

if ($action === 'delete') {
    only_post();
    $id = (int)(body()['id'] ?? 0);
    try {
        $pdo->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
        log_activity('supplier_delete', "Deleted supplier ID #{$id}");
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1451) fail('Cannot delete supplier because purchase orders exist for them.');
        throw $e;
    }
    json_out(['success' => true, 'message' => 'Supplier deleted.']);
}

fail('Unknown action.');
