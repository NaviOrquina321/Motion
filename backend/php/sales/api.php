<?php
/* Sales.  ?action=checkout (POST) | history (filters) | receipt (id) */
require_once __DIR__ . '/../config/helpers.php';
$user = require_login();
$pdo = db();
$action = $_GET['action'] ?? 'history';

function get_receipt($pdo, $id) {
    $st = $pdo->prepare(
        'SELECT s.id, s.total_amount, s.payment, s.change_amount, s.created_at,
                u.full_name AS cashier, COALESCE(c.full_name, "Walk-in") AS customer
         FROM sales s JOIN users u ON u.id = s.cashier_id
         LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = ?'
    );
    $st->execute([$id]);
    $sale = $st->fetch();
    if (!$sale) return null;
    $it = $pdo->prepare('SELECT product_name, unit_price, quantity, subtotal FROM sale_items WHERE sale_id = ? ORDER BY id');
    $it->execute([$id]);
    $sale['items'] = $it->fetchAll();
    return $sale;
}

if ($action === 'checkout') {
    only_post();
    $b = body();
    $items = $b['items'] ?? [];
    $payment = filter_var($b['payment'] ?? null, FILTER_VALIDATE_FLOAT);
    $custId = (int)($b['customer_id'] ?? 0);
    if (!is_array($items) || !$items)  fail('The cart is empty.');
    if ($payment === false)            fail('Enter a valid payment amount.');

    // Merge duplicate products, validate quantities
    $wanted = [];
    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        $qty = (int)($it['quantity'] ?? 0);
        if ($pid <= 0 || $qty <= 0) fail('Invalid item in cart.');
        $wanted[$pid] = ($wanted[$pid] ?? 0) + $qty;
    }

    $pdo->beginTransaction();
    try {
        $lines = []; $total = 0.0;
        foreach ($wanted as $pid => $qty) {
            // FOR UPDATE locks the row so two cashiers cannot sell the same last item
            $st = $pdo->prepare('SELECT id, name, price, stock_quantity FROM products WHERE id = ? FOR UPDATE');
            $st->execute([$pid]);
            $p = $st->fetch();
            if (!$p) throw new RuntimeException('A product in the cart no longer exists.');
            if ($p['stock_quantity'] < $qty) throw new RuntimeException("Not enough stock for {$p['name']} (available: {$p['stock_quantity']}).");
            $sub = round((float)$p['price'] * $qty, 2);   // price comes from the DB, never from the browser
            $total += $sub;
            $lines[] = ['id' => $pid, 'name' => $p['name'], 'price' => (float)$p['price'], 'qty' => $qty, 'sub' => $sub];
        }
        $total = round($total, 2);
        if ($payment < $total) throw new RuntimeException('Payment is less than the total amount.');
        $change = round($payment - $total, 2);

        if ($custId > 0) {
            $c = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
            $c->execute([$custId]);
            if (!$c->fetch()) $custId = 0;
        }
        $pdo->prepare('INSERT INTO sales (cashier_id, customer_id, total_amount, payment, change_amount) VALUES (?,?,?,?,?)')
            ->execute([$user['id'], $custId ?: null, $total, $payment, $change]);
        $saleId = (int)$pdo->lastInsertId();

        $insItem = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, product_name, unit_price, quantity, subtotal) VALUES (?,?,?,?,?,?)');
        $deduct  = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?');
        foreach ($lines as $l) {
            $insItem->execute([$saleId, $l['id'], $l['name'], $l['price'], $l['qty'], $l['sub']]);
            $deduct->execute([$l['qty'], $l['id']]);
        }
        $pdo->commit();
    } catch (RuntimeException $e) {
        $pdo->rollBack();
        fail($e->getMessage());
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    json_out(['success' => true, 'message' => 'Sale completed.', 'receipt' => get_receipt($pdo, $saleId)]);
}

if ($action === 'receipt') {
    $r = get_receipt($pdo, (int)($_GET['id'] ?? 0));
    if (!$r) fail('Transaction not found.', 404);
    json_out(['success' => true, 'receipt' => $r]);
}

if ($action === 'history') {
    $q    = clean($_GET['q'] ?? '');
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to'] ?? '';
    $sql = 'SELECT s.id, s.created_at, u.full_name AS cashier, COALESCE(c.full_name, "Walk-in") AS customer,
                   s.total_amount, s.payment, s.change_amount
            FROM sales s JOIN users u ON u.id = s.cashier_id LEFT JOIN customers c ON c.id = s.customer_id WHERE 1=1';
    $args = [];
    if ($user['role'] !== 'admin') { $sql .= ' AND s.cashier_id = ?'; $args[] = $user['id']; }   // cashiers see only their own sales
    if ($q !== '') {
        $sql .= ' AND (s.id = ? OR u.full_name LIKE ? OR c.full_name LIKE ?)';
        array_push($args, ctype_digit($q) ? (int)$q : 0, "%$q%", "%$q%");
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(s.created_at) >= ?'; $args[] = $from; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $sql .= ' AND DATE(s.created_at) <= ?'; $args[] = $to; }
    $st = $pdo->prepare($sql . ' ORDER BY s.id DESC LIMIT 500');
    $st->execute($args);
    json_out(['success' => true, 'sales' => $st->fetchAll()]);
}
fail('Unknown action.');
