<?php
/* Customer CRUD & History. ?action=list (q) | sales_history (id) | save, delete (POST). */
require_once __DIR__ . '/../config/helpers.php';
$user = require_login();
$pdo = db();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $q = clean($_GET['q'] ?? '');
    $sql = 'SELECT c.*, COALESCE(COUNT(s.id), 0) as total_orders, COALESCE(SUM(s.total_amount), 0.00) as total_spent
            FROM customers c
            LEFT JOIN sales s ON s.customer_id = c.id';
    $args = [];
    if ($q !== '') {
        $sql .= ' WHERE c.full_name LIKE ? OR c.contact_number LIKE ? OR c.email LIKE ? OR c.id = ?';
        $args = ["%$q%", "%$q%", "%$q%", ctype_digit($q) ? (int)$q : 0];
    }
    $sql .= ' GROUP BY c.id ORDER BY c.full_name';
    $st = $pdo->prepare($sql);
    $st->execute($args);
    json_out(['success' => true, 'customers' => $st->fetchAll()]);
}

if ($action === 'sales_history') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) fail('Invalid customer ID.');

    $stCust = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stCust->execute([$id]);
    $customer = $stCust->fetch();
    if (!$customer) fail('Customer not found.', 404);

    $stSales = $pdo->prepare(
        'SELECT s.id, s.created_at, s.total_amount, s.payment_method, u.full_name as cashier_name
         FROM sales s
         JOIN users u ON u.id = s.cashier_id
         WHERE s.customer_id = ?
         ORDER BY s.id DESC'
    );
    $stSales->execute([$id]);
    $sales = $stSales->fetchAll();

    json_out(['success' => true, 'customer' => $customer, 'sales' => $sales]);
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
        log_activity('customer_update', "Updated customer ID #{$id}: {$name}");
    } else {
        $pdo->prepare('INSERT INTO customers (full_name, contact_number, email, address) VALUES (?,?,?,?)')
            ->execute([$name, $tel ?: null, $mail ?: null, $addr ?: null]);
        $id = (int)$pdo->lastInsertId();
        log_activity('customer_create', "Created customer ID #{$id}: {$name}");
    }
    json_out(['success' => true, 'message' => $id > 0 ? 'Customer updated.' : 'Customer added.']);
}

if ($action === 'delete') {
    only_post();
    require_role('admin');
    $id = (int)(body()['id'] ?? 0);
    $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    log_activity('customer_delete', "Deleted customer ID #{$id}");
    json_out(['success' => true, 'message' => 'Customer deleted.']);
}
fail('Unknown action.');
