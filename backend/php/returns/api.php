<?php
/* Returns & Refunds API. ?action=search_sale | process_return (POST) | history */
require_once __DIR__ . '/../config/helpers.php';
$user = require_login();
$pdo = db();
$action = $_GET['action'] ?? 'history';
$forUpdate = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') ? '' : ' FOR UPDATE';

if ($action === 'search_sale') {
    $saleId = (int)($_GET['sale_id'] ?? 0);
    if ($saleId <= 0) fail('Enter a valid Sale / Transaction ID.');

    $st = $pdo->prepare(
        'SELECT s.id, s.cashier_id, s.created_at, s.total_amount, s.discount_amount, s.tax_amount, s.payment_method, s.payment_status,
                u.full_name AS cashier, COALESCE(c.full_name, "Walk-in Customer") AS customer
         FROM sales s
         JOIN users u ON u.id = s.cashier_id
         LEFT JOIN customers c ON c.id = s.customer_id
         WHERE s.id = ?'
    );
    $st->execute([$saleId]);
    $sale = $st->fetch();
    if (!$sale) fail("Transaction #{$saleId} not found.", 404);

    // Cashier return policy check
    $saleDate = strtotime($sale['created_at']);
    $sevenDaysAgo = strtotime('-7 days');
    $isOwnSale = (int)$sale['cashier_id'] === (int)$user['id'];
    $isWithin7Days = $saleDate >= $sevenDaysAgo;

    if ($user['role'] !== 'admin' && (!$isOwnSale || !$isWithin7Days)) {
        fail('Cashiers can only return their own sales from the last 7 days. Admin approval required.', 403);
    }

    // Fetch sale items with already returned quantities
    $stItems = $pdo->prepare(
        'SELECT si.id AS sale_item_id, si.product_id, si.product_name, si.unit_price, si.quantity, si.subtotal,
                COALESCE(SUM(ri.quantity), 0) AS returned_quantity
         FROM sale_items si
         LEFT JOIN return_items ri ON ri.sale_item_id = si.id
         WHERE si.sale_id = ?
         GROUP BY si.id, si.product_id, si.product_name, si.unit_price, si.quantity, si.subtotal'
    );
    $stItems->execute([$saleId]);
    $items = $stItems->fetchAll();

    foreach ($items as &$i) {
        $i['unit_price'] = (float)$i['unit_price'];
        $i['subtotal'] = (float)$i['subtotal'];
        $i['quantity'] = (int)$i['quantity'];
        $i['returned_quantity'] = (int)$i['returned_quantity'];
        $i['returnable_quantity'] = max(0, $i['quantity'] - $i['returned_quantity']);
    }

    $sale['items'] = $items;
    json_out(['success' => true, 'sale' => $sale]);
}

if ($action === 'process_return') {
    only_post();
    $b = body();
    $saleId       = (int)($b['sale_id'] ?? 0);
    $reason       = clean($b['reason'] ?? '');
    $refundMethod = clean($b['refund_method'] ?? 'Cash');
    $items        = $b['items'] ?? []; // [{sale_item_id, quantity, restock}]

    if ($saleId <= 0)                                                    fail('Invalid Sale ID.');
    if ($reason === '')                                                  fail('Please provide a reason for the return.');
    if (!in_array($refundMethod, ['Cash', 'GCash', 'Maya', 'Store Credit'])) fail('Invalid refund method.');
    if (!is_array($items) || !$items)                                    fail('Select at least one item to return.');

    $pdo->beginTransaction();
    try {
        $stSale = $pdo->prepare("SELECT * FROM sales WHERE id = ?{$forUpdate}");
        $stSale->execute([$saleId]);
        $sale = $stSale->fetch();
        if (!$sale) throw new RuntimeException('Sale transaction not found.');

        // Cashier return policy check
        $saleDate = strtotime($sale['created_at']);
        $sevenDaysAgo = strtotime('-7 days');
        $isOwnSale = (int)$sale['cashier_id'] === (int)$user['id'];
        $isWithin7Days = $saleDate >= $sevenDaysAgo;

        if ($user['role'] !== 'admin' && (!$isOwnSale || !$isWithin7Days)) {
            throw new RuntimeException('Cashiers can only return their own sales from the last 7 days. Admin approval required.');
        }

        // Calculate previous total refunds for this sale
        $stPrevRefunds = $pdo->prepare('SELECT COALESCE(SUM(total_refund), 0.00) FROM returns WHERE sale_id = ?');
        $stPrevRefunds->execute([$saleId]);
        $prevTotalRefunded = (float)$stPrevRefunds->fetchColumn();

        $saleTotalAmount = (float)$sale['total_amount'];
        $maxRemainingRefund = max(0.00, round($saleTotalAmount - $prevTotalRefunded, 2));

        if ($maxRemainingRefund <= 0) {
            throw new RuntimeException('This sale transaction has already been fully refunded.');
        }

        // Calculate raw gross subtotal of all original sale items
        $stGross = $pdo->prepare('SELECT SUM(unit_price * quantity) FROM sale_items WHERE sale_id = ?');
        $stGross->execute([$saleId]);
        $rawGrossSubtotal = (float)$stGross->fetchColumn();
        if ($rawGrossSubtotal <= 0) $rawGrossSubtotal = $saleTotalAmount;

        $totalRefundRequested = 0.0;
        $returnLines = [];

        foreach ($items as $it) {
            $siId    = (int)($it['sale_item_id'] ?? 0);
            $retQty  = (int)($it['quantity'] ?? 0);
            $restock = !empty($it['restock']);

            if ($siId <= 0 || $retQty <= 0) continue;

            $stSi = $pdo->prepare(
                "SELECT si.id, si.product_id, si.product_name, si.unit_price, si.quantity,
                        COALESCE(SUM(ri.quantity), 0) AS returned_quantity
                 FROM sale_items si
                 LEFT JOIN return_items ri ON ri.sale_item_id = si.id
                 WHERE si.id = ? AND si.sale_id = ?
                 GROUP BY si.id, si.product_id, si.product_name, si.unit_price, si.quantity{$forUpdate}"
            );
            $stSi->execute([$siId, $saleId]);
            $si = $stSi->fetch();

            if (!$si) throw new RuntimeException("Sale item #{$siId} not found in transaction #{$saleId}.");

            $soldQty = (int)$si['quantity'];
            $alreadyRet = (int)$si['returned_quantity'];
            $maxReturnable = $soldQty - $alreadyRet;

            if ($retQty > $maxReturnable) {
                throw new RuntimeException("Cannot return {$retQty} units of {$si['product_name']}. Maximum returnable is {$maxReturnable}.");
            }

            // Proportional refund calculation: unit_price * retQty share of net sale total
            $lineGross = (float)$si['unit_price'] * $retQty;
            $lineProportionalRefund = round(($lineGross / $rawGrossSubtotal) * $saleTotalAmount, 2);

            $totalRefundRequested += $lineProportionalRefund;

            $returnLines[] = [
                'sale_item_id' => $siId,
                'product_id'   => $si['product_id'] ? (int)$si['product_id'] : null,
                'product_name' => $si['product_name'],
                'quantity'     => $retQty,
                'refund_amount'=> $lineProportionalRefund,
                'restock'      => $restock
            ];
        }

        if (empty($returnLines)) throw new RuntimeException('No valid return items selected.');

        // Total refund capped at max remaining refund
        $finalRefundAmount = min($totalRefundRequested, $maxRemainingRefund);

        // Insert Return Header
        $insRet = $pdo->prepare('INSERT INTO returns (sale_id, user_id, username, total_refund, refund_method, reason) VALUES (?,?,?,?,?,?)');
        $insRet->execute([$saleId, $user['id'], $user['username'], $finalRefundAmount, $refundMethod, $reason]);
        $returnId = (int)$pdo->lastInsertId();

        // Insert Return Items and Handle Restocking
        $insItem = $pdo->prepare('INSERT INTO return_items (return_id, sale_item_id, product_id, product_name, quantity, refund_amount, restocked) VALUES (?,?,?,?,?,?,?)');
        $addStock = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?');
        $insMvt   = $pdo->prepare('INSERT INTO inventory_movements (product_id, type, quantity, reason, user_id, username) VALUES (?, "returned", ?, ?, ?, ?)');

        foreach ($returnLines as $l) {
            $insItem->execute([$returnId, $l['sale_item_id'], $l['product_id'], $l['product_name'], $l['quantity'], $l['refund_amount'], $l['restock'] ? 1 : 0]);

            if ($l['restock'] && $l['product_id']) {
                $addStock->execute([$l['quantity'], $l['product_id']]);
                $insMvt->execute([$l['product_id'], $l['quantity'], "Return #{$returnId} for Sale #{$saleId}", $user['id'], $user['username']]);
            }
        }

        $pdo->commit();
        log_activity('sale_return', "Processed Return #{$returnId} for Sale #{$saleId} (Refund: ₱{$finalRefundAmount})", $user['id'], $user['username']);
    } catch (RuntimeException $e) {
        $pdo->rollBack();
        fail($e->getMessage());
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    json_out(['success' => true, 'message' => "Return #{$returnId} processed successfully.", 'return_id' => $returnId]);
}

if ($action === 'history') {
    $q    = clean($_GET['q'] ?? '');
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to'] ?? '';

    $sql = 'SELECT r.id, r.sale_id, r.total_refund, r.refund_method, r.reason, r.username, r.created_at
            FROM returns r WHERE 1=1';
    $args = [];

    if ($q !== '') {
        $sql .= ' AND (r.id = ? OR r.sale_id = ? OR r.reason LIKE ? OR r.username LIKE ?)';
        array_push($args, ctype_digit($q) ? (int)$q : 0, ctype_digit($q) ? (int)$q : 0, "%$q%", "%$q%");
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(r.created_at) >= ?'; $args[] = $from; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $sql .= ' AND DATE(r.created_at) <= ?'; $args[] = $to; }

    $st = $pdo->prepare($sql . ' ORDER BY r.id DESC LIMIT 500');
    $st->execute($args);

    json_out(['success' => true, 'returns' => $st->fetchAll()]);
}

fail('Unknown action.');
