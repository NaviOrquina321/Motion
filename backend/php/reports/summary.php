<?php
/*
 * GET ?days=30 or ?from=YYYY-MM-DD&to=YYYY-MM-DD (admin only)
 * PHP reads sales, returns, purchase orders, movements, and products from MySQL,
 * passes them as JSON to Python (via stdin), and returns Python's calculated JSON answer.
 */
require_once __DIR__ . '/../config/helpers.php';
require_role('admin');
$pdo = db();

$days = max(1, min(365, (int)($_GET['days'] ?? 30)));
$from = clean($_GET['from'] ?? '');
$to   = clean($_GET['to'] ?? '');

$dateWhereSales = 's.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)';
$dateParamsSales = [$days - 1];

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $dateWhereSales = 'DATE(s.created_at) BETWEEN ? AND ?';
    $dateParamsSales = [$from, $to];
}

// 1. Sales Rows
$stSales = $pdo->prepare(
    "SELECT s.id AS sale_id, s.total_amount, s.discount_amount, s.tax_amount, s.payment_method, DATE(s.created_at) AS day,
            si.product_name, si.quantity, si.subtotal, COALESCE(p.cost_price, 0.00) AS cost_price,
            COALESCE(c.name, 'Uncategorized') AS category
     FROM sales s
     JOIN sale_items si ON si.sale_id = s.id
     LEFT JOIN products p ON p.id = si.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE {$dateWhereSales}"
);
$stSales->execute($dateParamsSales);
$salesRows = $stSales->fetchAll();

// 2. Returns
$stRet = $pdo->prepare(
    "SELECT r.id, r.sale_id, r.total_refund, r.created_at
     FROM returns r
     WHERE " . str_replace('s.created_at', 'r.created_at', $dateWhereSales)
);
$stRet->execute($dateParamsSales);
$returnsRows = $stRet->fetchAll();

// 3. Purchase Orders
$stPO = $pdo->prepare(
    "SELECT po.id, po.po_number, po.total_cost, po.status, po.created_at
     FROM purchase_orders po
     WHERE " . str_replace('s.created_at', 'po.created_at', $dateWhereSales)
);
$stPO->execute($dateParamsSales);
$poRows = $stPO->fetchAll();

// 4. Products Valuation Data
$stProd = $pdo->query("SELECT id, name, price, cost_price, stock_quantity FROM products");
$productRows = $stProd->fetchAll();

$payload = json_encode([
    'days'            => $days,
    'from'            => $from,
    'to'              => $to,
    'rows'            => $salesRows,
    'returns'         => $returnsRows,
    'purchase_orders' => $poRows,
    'products'        => $productRows
]);

$script = realpath(__DIR__ . '/../../python/reports/sales_summary.py');
$proc = proc_open([PYTHON_BIN, $script], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($proc)) fail('Could not start Python. Check PYTHON_BIN in config/db.php.', 500);

fwrite($pipes[0], $payload); fclose($pipes[0]);
$out = stream_get_contents($pipes[1]); fclose($pipes[1]);
$err = stream_get_contents($pipes[2]); fclose($pipes[2]);
$code = proc_close($proc);

$result = json_decode($out, true);
if ($code !== 0 || !is_array($result)) {
    error_log('Python error: ' . $err);
    fail('The Python report service failed. Is Python installed and PYTHON_BIN correct?', 500);
}

json_out(['success' => true, 'report' => $result]);
