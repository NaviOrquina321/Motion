<?php
/* Inventory Management API (Admin only). ?action=overview | adjustments (POST) | movements */
require_once __DIR__ . '/../config/helpers.php';
$user = require_role('admin');
$pdo = db();
$action = $_GET['action'] ?? 'overview';

// Helper to record an inventory movement
function add_movement($pdo, $productId, $type, $qty, $reason, $userId, $username) {
    $st = $pdo->prepare('INSERT INTO inventory_movements (product_id, type, quantity, reason, user_id, username) VALUES (?,?,?,?,?,?)');
    $st->execute([$productId, $type, $qty, $reason, $userId, $username]);
}

if ($action === 'overview') {
    $q   = clean($_GET['q'] ?? '');
    $cat = (int)($_GET['category_id'] ?? 0);
    $status = $_GET['status'] ?? ''; // low, out, in_stock

    $sql = 'SELECT p.id, p.name, p.price, COALESCE(p.cost_price, 0.00) as cost_price, p.stock_quantity, p.reorder_level, c.name AS category_name,
                   (p.stock_quantity * p.price) AS stock_value_retail,
                   (p.stock_quantity * COALESCE(p.cost_price, 0.00)) AS stock_value_cost
            FROM products p
            JOIN categories c ON c.id = p.category_id WHERE 1=1';
    $args = [];

    if ($q !== '') {
        $sql .= ' AND (p.name LIKE ? OR p.id = ?)';
        $args[] = "%$q%";
        $args[] = ctype_digit($q) ? (int)$q : 0;
    }
    if ($cat > 0) {
        $sql .= ' AND p.category_id = ?';
        $args[] = $cat;
    }
    if ($status === 'out') {
        $sql .= ' AND p.stock_quantity <= 0';
    } elseif ($status === 'low') {
        $sql .= ' AND p.stock_quantity > 0 AND p.stock_quantity <= p.reorder_level';
    } elseif ($status === 'in_stock') {
        $sql .= ' AND p.stock_quantity > p.reorder_level';
    }

    $st = $pdo->prepare($sql . ' ORDER BY p.name');
    $st->execute($args);
    $products = $st->fetchAll();

    // Summary metrics
    $totalValRetail = 0.0;
    $totalValCost = 0.0;
    $totalItems = 0;
    foreach ($products as $p) {
        $totalValRetail += (float)$p['stock_value_retail'];
        $totalValCost += (float)$p['stock_value_cost'];
        $totalItems += (int)$p['stock_quantity'];
    }

    json_out([
        'success' => true,
        'products' => $products,
        'summary' => [
            'total_items' => $totalItems,
            'total_value_retail' => round($totalValRetail, 2),
            'total_value_cost' => round($totalValCost, 2)
        ]
    ]);
}

if ($action === 'adjust') {
    only_post();
    $b = body();
    $pid    = (int)($b['product_id'] ?? 0);
    $type   = clean($b['type'] ?? '');
    $qty    = (int)($b['quantity'] ?? 0);
    $reason = clean($b['reason'] ?? '');

    $allowedTypes = ['received', 'damaged', 'expired', 'adjustment'];
    if ($pid <= 0)                             fail('Select a valid product.');
    if (!in_array($type, $allowedTypes))       fail('Invalid adjustment type.');
    if ($qty <= 0)                             fail('Quantity must be greater than 0.');
    if ($reason === '')                        fail('Reason is required for stock adjustments.');

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT id, name, stock_quantity FROM products WHERE id = ? FOR UPDATE');
        $st->execute([$pid]);
        $p = $st->fetch();
        if (!$p) throw new RuntimeException('Product not found.');

        $stockDelta = $type === 'received' ? $qty : -$qty;
        $newStock = (int)$p['stock_quantity'] + $stockDelta;
        if ($newStock < 0) throw new RuntimeException("Adjustment would result in negative stock (current: {$p['stock_quantity']}).");

        $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?')->execute([$newStock, $pid]);
        add_movement($pdo, $pid, $type, $stockDelta, $reason, $user['id'], $user['username']);

        $pdo->commit();
        log_activity('stock_adjustment', "Adjusted stock for {$p['name']} ({$type} {$qty}): {$reason}");
    } catch (Throwable $e) {
        $pdo->rollBack();
        fail($e->getMessage());
    }

    json_out(['success' => true, 'message' => 'Stock adjusted successfully.']);
}

if ($action === 'movements') {
    $q    = clean($_GET['q'] ?? '');
    $type = clean($_GET['type'] ?? '');
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to'] ?? '';

    $sql = 'SELECT m.*, p.name AS product_name
            FROM inventory_movements m
            JOIN products p ON p.id = m.product_id WHERE 1=1';
    $args = [];

    if ($q !== '') {
        $sql .= ' AND (p.name LIKE ? OR m.username LIKE ? OR m.reason LIKE ?)';
        array_push($args, "%$q%", "%$q%", "%$q%");
    }
    if ($type !== '') {
        $sql .= ' AND m.type = ?';
        $args[] = $type;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(m.created_at) >= ?'; $args[] = $from; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $sql .= ' AND DATE(m.created_at) <= ?'; $args[] = $to; }

    $st = $pdo->prepare($sql . ' ORDER BY m.id DESC LIMIT 500');
    $st->execute($args);

    json_out(['success' => true, 'movements' => $st->fetchAll()]);
}

fail('Unknown action.');
