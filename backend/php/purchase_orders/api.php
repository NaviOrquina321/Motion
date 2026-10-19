<?php
/* Purchase Orders API (Admin only). ?action=list | get | create (POST) | receive (POST) | cancel (POST) | restocking_needs */
require_once __DIR__ . '/../config/helpers.php';
$user = require_role('admin');
$pdo = db();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $q      = clean($_GET['q'] ?? '');
    $status = clean($_GET['status'] ?? '');
    $supId  = (int)($_GET['supplier_id'] ?? 0);

    $sql = 'SELECT po.*, s.name AS supplier_name
            FROM purchase_orders po
            JOIN suppliers s ON s.id = po.supplier_id
            WHERE 1=1';
    $args = [];

    if ($q !== '') {
        $sql .= ' AND (po.po_number LIKE ? OR s.name LIKE ? OR po.notes LIKE ?)';
        array_push($args, "%$q%", "%$q%", "%$q%");
    }
    if ($status !== '') {
        $sql .= ' AND po.status = ?';
        $args[] = $status;
    }
    if ($supId > 0) {
        $sql .= ' AND po.supplier_id = ?';
        $args[] = $supId;
    }

    $st = $pdo->prepare($sql . ' ORDER BY po.id DESC LIMIT 500');
    $st->execute($args);

    json_out(['success' => true, 'purchase_orders' => $st->fetchAll()]);
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) fail('Invalid Purchase Order ID.');

    $stPO = $pdo->prepare(
        'SELECT po.*, s.name AS supplier_name, s.phone AS supplier_phone, s.email AS supplier_email
         FROM purchase_orders po
         JOIN suppliers s ON s.id = po.supplier_id
         WHERE po.id = ?'
    );
    $stPO->execute([$id]);
    $po = $stPO->fetch();
    if (!$po) fail('Purchase Order not found.', 404);

    $stItems = $pdo->prepare(
        'SELECT poi.*, p.name AS product_name, p.stock_quantity AS current_stock
         FROM purchase_order_items poi
         JOIN products p ON p.id = poi.product_id
         WHERE poi.purchase_order_id = ?'
    );
    $stItems->execute([$id]);
    $items = $stItems->fetchAll();

    foreach ($items as &$i) {
        $i['unit_cost'] = (float)$i['unit_cost'];
        $i['subtotal'] = (float)$i['subtotal'];
        $i['ordered_quantity'] = (int)$i['ordered_quantity'];
        $i['received_quantity'] = (int)$i['received_quantity'];
        $i['outstanding_quantity'] = max(0, $i['ordered_quantity'] - $i['received_quantity']);
    }

    $po['items'] = $items;
    json_out(['success' => true, 'purchase_order' => $po]);
}

if ($action === 'create') {
    only_post();
    $b = body();
    $supplierId = (int)($b['supplier_id'] ?? 0);
    $notes      = clean($b['notes'] ?? '');
    $items      = $b['items'] ?? []; // [{product_id, unit_cost, quantity}]

    if ($supplierId <= 0)                      fail('Please select a supplier.');
    if (!is_array($items) || empty($items))   fail('Please add at least one product item to the purchase order.');

    $poNumber = 'PO-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));

    $pdo->beginTransaction();
    try {
        $totalCost = 0.0;
        $orderLines = [];

        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $cost = filter_var($it['unit_cost'] ?? 0, FILTER_VALIDATE_FLOAT);
            $qty  = (int)($it['quantity'] ?? 0);

            if ($pid <= 0 || $qty <= 0 || $cost === false || $cost < 0) {
                throw new RuntimeException('Invalid product item in purchase order list.');
            }

            $lineSub = round($cost * $qty, 2);
            $totalCost += $lineSub;

            $orderLines[] = [
                'product_id' => $pid,
                'unit_cost'  => $cost,
                'quantity'   => $qty,
                'subtotal'   => $lineSub
            ];
        }

        $insPO = $pdo->prepare(
            'INSERT INTO purchase_orders (po_number, supplier_id, user_id, username, status, total_cost, notes)
             VALUES (?,?,?,?, "Ordered", ?, ?)'
        );
        $insPO->execute([$poNumber, $supplierId, $user['id'], $user['username'], $totalCost, $notes]);
        $poId = (int)$pdo->lastInsertId();

        $insItem = $pdo->prepare('INSERT INTO purchase_order_items (purchase_order_id, product_id, unit_cost, ordered_quantity, subtotal) VALUES (?,?,?,?,?)');
        foreach ($orderLines as $l) {
            $insItem->execute([$poId, $l['product_id'], $l['unit_cost'], $l['quantity'], $l['subtotal']]);
        }

        $pdo->commit();
        log_activity('po_create', "Created Purchase Order {$poNumber} (Total: ₱{$totalCost})");
    } catch (Throwable $e) {
        $pdo->rollBack();
        fail($e->getMessage());
    }

    json_out(['success' => true, 'message' => "Purchase Order {$poNumber} created successfully.", 'po_id' => $poId]);
}

if ($action === 'receive') {
    only_post();
    $b = body();
    $poId  = (int)($b['po_id'] ?? 0);
    $items = $b['items'] ?? []; // [{item_id, receive_quantity}]

    if ($poId <= 0)                          fail('Invalid Purchase Order ID.');
    if (!is_array($items) || empty($items)) fail('No receive quantities provided.');

    $pdo->beginTransaction();
    try {
        $stPO = $pdo->prepare('SELECT id, po_number, status FROM purchase_orders WHERE id = ? FOR UPDATE');
        $stPO->execute([$poId]);
        $po = $stPO->fetch();

        if (!$po) throw new RuntimeException('Purchase Order not found.');
        if (in_array($po['status'], ['Completed', 'Cancelled'])) {
            throw new RuntimeException("Cannot receive items for a PO that is already {$po['status']}.");
        }

        $anyReceivedNow = false;

        foreach ($items as $it) {
            $poiId  = (int)($it['item_id'] ?? 0);
            $recQty = (int)($it['receive_quantity'] ?? 0);

            if ($poiId <= 0 || $recQty <= 0) continue;

            $stPoi = $pdo->prepare('SELECT poi.*, p.name as product_name FROM purchase_order_items poi JOIN products p ON p.id = poi.product_id WHERE poi.id = ? AND poi.purchase_order_id = ? FOR UPDATE');
            $stPoi->execute([$poiId, $poId]);
            $poi = $stPoi->fetch();

            if (!$poi) throw new RuntimeException("Item ID #{$poiId} not found in this Purchase Order.");

            $ordered   = (int)$poi['ordered_quantity'];
            $prevRec   = (int)$poi['received_quantity'];
            $outstanding = $ordered - $prevRec;

            if ($outstanding <= 0) {
                throw new RuntimeException("Item {$poi['product_name']} is already fully received.");
            }

            if ($recQty > $outstanding) {
                throw new RuntimeException("Cannot receive {$recQty} units of {$poi['product_name']}. Outstanding quantity is {$outstanding}.");
            }

            $newRec = $prevRec + $recQty;
            $pdo->prepare('UPDATE purchase_order_items SET received_quantity = ? WHERE id = ?')->execute([$newRec, $poiId]);

            // Update product inventory stock AND cost_price
            $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + ?, cost_price = ? WHERE id = ?')
                ->execute([$recQty, $poi['unit_cost'], $poi['product_id']]);

            // Record inventory movement
            $pdo->prepare('INSERT INTO inventory_movements (product_id, type, quantity, reason, user_id, username) VALUES (?, "received", ?, ?, ?, ?)')
                ->execute([$poi['product_id'], $recQty, "PO {$po['po_number']} Received", $user['id'], $user['username']]);

            $anyReceivedNow = true;
        }

        if (!$anyReceivedNow) {
            throw new RuntimeException('Please specify a positive quantity to receive for at least one item.');
        }

        // Check if all items in PO are fully received
        $stCheck = $pdo->prepare('SELECT COUNT(*) as unfulfilled FROM purchase_order_items WHERE purchase_order_id = ? AND received_quantity < ordered_quantity');
        $stCheck->execute([$poId]);
        $unfulfilled = (int)$stCheck->fetchColumn();

        $newStatus = $unfulfilled === 0 ? 'Completed' : 'Partially Received';
        $pdo->prepare('UPDATE purchase_orders SET status = ? WHERE id = ?')->execute([$newStatus, $poId]);

        $pdo->commit();
        log_activity('po_receive', "Received items for PO {$po['po_number']}. Status is now {$newStatus}");
    } catch (Throwable $e) {
        $pdo->rollBack();
        fail($e->getMessage());
    }

    json_out(['success' => true, 'message' => 'Items received and inventory updated successfully.']);
}

if ($action === 'cancel') {
    only_post();
    $id = (int)(body()['id'] ?? 0);
    $pdo->prepare('UPDATE purchase_orders SET status = "Cancelled" WHERE id = ? AND status IN ("Pending", "Ordered")')->execute([$id]);
    log_activity('po_cancel', "Cancelled Purchase Order ID #{$id}");
    json_out(['success' => true, 'message' => 'Purchase order cancelled.']);
}

if ($action === 'restocking_needs') {
    // Products where stock <= reorder level
    $st = $pdo->query(
        'SELECT p.id, p.name, p.stock_quantity, p.reorder_level, COALESCE(p.cost_price, 0.00) as cost_price, c.name as category_name
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.stock_quantity <= p.reorder_level
         ORDER BY p.stock_quantity ASC'
    );
    json_out(['success' => true, 'restock_items' => $st->fetchAll()]);
}

fail('Unknown action.');
