<?php
/* Sales API with discounts, tax, payment methods (Cash, GCash, Maya), duplicate cart token check, verification status. */
require_once __DIR__ . '/../config/helpers.php';
$user = require_login();
$pdo = db();
$action = $_GET['action'] ?? 'history';

function get_receipt($pdo, $id, $user = null) {
    $sql = 'SELECT s.id, s.cashier_id, s.total_amount, s.payment, s.change_amount, s.created_at,
                   s.discount_amount, s.tax_amount, s.payment_method, s.reference_number, s.payment_status,
                   u.full_name AS cashier, COALESCE(c.full_name, "Walk-in") AS customer
            FROM sales s JOIN users u ON u.id = s.cashier_id
            LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = ?';
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
    $sale = $st->fetch();
    if (!$sale) return null;

    if ($user && $user['role'] !== 'admin' && (int)$sale['cashier_id'] !== (int)$user['id']) {
        fail('Access denied. Cashiers may only view their own receipts.', 403);
    }

    $it = $pdo->prepare('SELECT product_name, unit_price, quantity, subtotal FROM sale_items WHERE sale_id = ? ORDER BY id');
    $it->execute([$id]);
    $sale['items'] = $it->fetchAll();
    return $sale;
}

if ($action === 'checkout') {
    only_post();
    $b = body();
    $items        = $b['items'] ?? [];
    $payment      = filter_var($b['payment'] ?? null, FILTER_VALIDATE_FLOAT);
    $custId       = (int)($b['customer_id'] ?? 0);
    $discountId   = (int)($b['discount_id'] ?? 0);
    $payMethod    = clean($b['payment_method'] ?? 'Cash');
    $refNum       = clean($b['reference_number'] ?? '');
    $cartToken    = clean($b['cart_token'] ?? '');

    if (!is_array($items) || !$items)                     fail('The cart is empty.');
    if ($payment === false)                               fail('Enter a valid payment amount.');
    if (!in_array($payMethod, ['Cash', 'GCash', 'Maya'])) fail('Invalid payment method.');
    if (($payMethod === 'GCash' || $payMethod === 'Maya') && $refNum === '') {
        fail('Reference number is required for digital wallet payments.');
    }

    // Check duplicate checkout via cart_token
    if ($cartToken !== '') {
        $st = $pdo->prepare('SELECT id FROM sales WHERE cart_token = ?');
        $st->execute([$cartToken]);
        if ($st->fetch()) fail('This transaction has already been processed.');
    }

    // Merge duplicate products
    $wanted = [];
    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        $qty = (int)($it['quantity'] ?? 0);
        if ($pid <= 0 || $qty <= 0) fail('Invalid item in cart.');
        $wanted[$pid] = ($wanted[$pid] ?? 0) + $qty;
    }
    ksort($wanted);

    // Get Tax configuration from settings
    $taxEnabled = '0';
    $taxRate = 0.0;
    $stSet = $pdo->query('SELECT setting_key, setting_value FROM settings WHERE setting_key IN ("tax_enabled", "tax_rate")');
    while ($r = $stSet->fetch()) {
        if ($r['setting_key'] === 'tax_enabled') $taxEnabled = $r['setting_value'];
        if ($r['setting_key'] === 'tax_rate')    $taxRate = (float)$r['setting_value'];
    }

    // Get Discount configuration
    $discountAmount = 0.0;
    if ($discountId > 0) {
        $stDisc = $pdo->prepare('SELECT type, value, is_active FROM discounts WHERE id = ?');
        $stDisc->execute([$discountId]);
        $disc = $stDisc->fetch();
        if ($disc && $disc['is_active']) {
            $discType = $disc['type'];
            $discVal  = (float)$disc['value'];
        } else {
            $discountId = 0;
        }
    }

    $pdo->beginTransaction();
    try {
        $lines = []; $subtotal = 0.0;
        foreach ($wanted as $pid => $qty) {
            $st = $pdo->prepare('SELECT id, name, price, stock_quantity FROM products WHERE id = ? FOR UPDATE');
            $st->execute([$pid]);
            $p = $st->fetch();
            if (!$p) throw new RuntimeException('A product in the cart no longer exists.');
            if ($p['stock_quantity'] < $qty) throw new RuntimeException("Not enough stock for {$p['name']} (available: {$p['stock_quantity']}).");
            $lineSub = round((float)$p['price'] * $qty, 2);
            $subtotal += $lineSub;
            $lines[] = ['id' => $pid, 'name' => $p['name'], 'price' => (float)$p['price'], 'qty' => $qty, 'sub' => $lineSub];
        }
        $subtotal = round($subtotal, 2);

        // Calculate Discount
        if ($discountId > 0 && isset($discType)) {
            if ($discType === 'percent') {
                $discountAmount = round(($subtotal * $discVal) / 100, 2);
            } else {
                $discountAmount = min($subtotal, round($discVal, 2));
            }
        }
        $afterDiscount = max(0, $subtotal - $discountAmount);

        // Calculate Tax
        $taxAmount = 0.0;
        if ($taxEnabled === '1' && $taxRate > 0) {
            $taxAmount = round(($afterDiscount * $taxRate) / 100, 2);
        }

        $total = round($afterDiscount + $taxAmount, 2);

        if ($payMethod === 'Cash' && $payment < $total) {
            throw new RuntimeException('Payment is less than the total amount.');
        }
        $change = $payMethod === 'Cash' ? round($payment - $total, 2) : 0.00;

        // Payment status
        /*
         * Note for real payment gateway integration (e.g. Maya API / PayMongo / GCash Webhook):
         * Here is where you would call the external payment gateway API to initiate or verify payment.
         * For GCash/Maya, we store payment_status = 'pending' and ref number for admin manual verification.
         */
        $payStatus = $payMethod === 'Cash' ? 'verified' : 'pending';

        if ($custId > 0) {
            $c = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
            $c->execute([$custId]);
            if (!$c->fetch()) $custId = 0;
        }

        $insSale = $pdo->prepare(
            'INSERT INTO sales (cashier_id, customer_id, discount_id, discount_amount, tax_rate, tax_amount, total_amount, payment, change_amount, payment_method, reference_number, payment_status, cart_token)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $insSale->execute([
            $user['id'],
            $custId ?: null,
            $discountId ?: null,
            $discountAmount,
            $taxEnabled === '1' ? $taxRate : 0.00,
            $taxAmount,
            $total,
            $payment,
            $change,
            $payMethod,
            $refNum ?: null,
            $payStatus,
            $cartToken ?: null
        ]);
        $saleId = (int)$pdo->lastInsertId();

        $insItem = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, product_name, unit_price, quantity, subtotal) VALUES (?,?,?,?,?,?)');
        $deduct  = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?');
        $insMvt  = $pdo->prepare('INSERT INTO inventory_movements (product_id, type, quantity, reason, user_id, username) VALUES (?, "sold", ?, ?, ?, ?)');

        foreach ($lines as $l) {
            $insItem->execute([$saleId, $l['id'], $l['name'], $l['price'], $l['qty'], $l['sub']]);
            $deduct->execute([$l['qty'], $l['id']]);
            $insMvt->execute([$l['id'], -$l['qty'], "Sale #{$saleId}", $user['id'], $user['username']]);
        }
        $pdo->commit();

        log_activity('sale_checkout', "Sale #{$saleId} ({$payMethod}) completed for ₱{$total}", $user['id'], $user['username']);
    } catch (RuntimeException $e) {
        $pdo->rollBack();
        fail($e->getMessage());
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    json_out(['success' => true, 'message' => 'Sale completed.', 'receipt' => get_receipt($pdo, $saleId, $user)]);
}

if ($action === 'verify_payment') {
    only_post();
    require_role('admin');
    $b = body();
    $saleId = (int)($b['sale_id'] ?? 0);
    $status = clean($b['payment_status'] ?? '');

    if ($saleId <= 0 || !in_array($status, ['verified', 'rejected'])) fail('Invalid payment verification parameters.');

    $st = $pdo->prepare('UPDATE sales SET payment_status = ? WHERE id = ?');
    $st->execute([$status, $saleId]);

    log_activity('payment_verify', "Set sale #{$saleId} payment status to {$status}");
    json_out(['success' => true, 'message' => "Payment status updated to {$status}."]);
}

if ($action === 'receipt') {
    $r = get_receipt($pdo, (int)($_GET['id'] ?? 0), $user);
    if (!$r) fail('Transaction not found.', 404);
    json_out(['success' => true, 'receipt' => $r]);
}

if ($action === 'history') {
    $q    = clean($_GET['q'] ?? '');
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to'] ?? '';
    $sql = 'SELECT s.id, s.created_at, u.full_name AS cashier, COALESCE(c.full_name, "Walk-in") AS customer,
                   s.total_amount, s.payment, s.change_amount, s.discount_amount, s.tax_amount, s.payment_method, s.reference_number, s.payment_status
            FROM sales s JOIN users u ON u.id = s.cashier_id LEFT JOIN customers c ON c.id = s.customer_id WHERE 1=1';
    $args = [];
    if ($user['role'] !== 'admin') { $sql .= ' AND s.cashier_id = ?'; $args[] = $user['id']; }
    if ($q !== '') {
        $sql .= ' AND (s.id = ? OR u.full_name LIKE ? OR c.full_name LIKE ? OR s.reference_number LIKE ?)';
        array_push($args, ctype_digit($q) ? (int)$q : 0, "%$q%", "%$q%", "%$q%");
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(s.created_at) >= ?'; $args[] = $from; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $sql .= ' AND DATE(s.created_at) <= ?'; $args[] = $to; }
    $st = $pdo->prepare($sql . ' ORDER BY s.id DESC LIMIT 500');
    $st->execute($args);
    json_out(['success' => true, 'sales' => $st->fetchAll()]);
}
fail('Unknown action.');
