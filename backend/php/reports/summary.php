<?php
/*
 * GET ?days=30 (admin)
 * PHP reads raw sales rows from MySQL, sends them as JSON to the Python script
 * (via its standard input), and returns Python's JSON answer to the browser.
 */
require_once __DIR__ . '/../config/helpers.php';
require_role('admin');

$days = max(1, min(365, (int)($_GET['days'] ?? 30)));

$st = db()->prepare(
    'SELECT s.id AS sale_id, s.total_amount, DATE(s.created_at) AS day,
            si.product_name, si.quantity, si.subtotal,
            COALESCE(c.name, "Uncategorized") AS category
     FROM sales s
     JOIN sale_items si ON si.sale_id = s.id
     LEFT JOIN products p ON p.id = si.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE s.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)'
);
$st->execute([$days - 1]);
$payload = json_encode(['days' => $days, 'rows' => $st->fetchAll()]);

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
