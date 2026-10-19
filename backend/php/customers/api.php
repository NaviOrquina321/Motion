<?php
/* Customer CRUD.  ?action=list (q)  |  save, delete (POST). Admin and cashier can use it. */
require_once __DIR__ . '/../config/helpers.php';
require_login();
$pdo = db();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $q = clean($_GET['q'] ?? '');
    $sql = 'SELECT * FROM customers'; $args = [];
    if ($q !== '') {
        $sql .= ' WHERE full_name LIKE ? OR contact_number LIKE ? OR email LIKE ? OR id = ?';
        $args = ["%$q%", "%$q%", "%$q%", ctype_digit($q) ? (int)$q : 0];
    }
    $st = $pdo->prepare($sql . ' ORDER BY full_name');
    $st->execute($args);
    json_out(['success' => true, 'customers' => $st->fetchAll()]);
}

if ($action === 'save') {
    only_post();
    $b = body();
    $id = (int)($b['id'] ?? 0);
    $name = clean($b['full_name'] ?? '');
    $tel  = clean($b['contact_number'] ?? '');
    $mail = clean($b['email'] ?? '');
    $addr = clean($b['address'] ?? '');
    if ($name === '' || strlen($name) > 100)                    fail('Full name is required (max 100 characters).');
    if ($tel !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $tel)) fail('Enter a valid contact number.');
    if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL))  fail('Enter a valid email address.');
    if (strlen($addr) > 255)                                    fail('Address is too long.');
    if ($id > 0) {
        $pdo->prepare('UPDATE customers SET full_name=?, contact_number=?, email=?, address=? WHERE id=?')
            ->execute([$name, $tel ?: null, $mail ?: null, $addr ?: null, $id]);
    } else {
        $pdo->prepare('INSERT INTO customers (full_name, contact_number, email, address) VALUES (?,?,?,?)')
            ->execute([$name, $tel ?: null, $mail ?: null, $addr ?: null]);
    }
    json_out(['success' => true, 'message' => $id > 0 ? 'Customer updated.' : 'Customer added.']);
}

if ($action === 'delete') {
    only_post();
    $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([(int)(body()['id'] ?? 0)]);
    json_out(['success' => true, 'message' => 'Customer deleted.']);
}
fail('Unknown action.');
