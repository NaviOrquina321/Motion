<?php
/*
 * GET ?type=sales|products|returns (admin only)
 * Exports CSV reports respecting date range parameters and formula escaping.
 */
require_once __DIR__ . '/../config/helpers.php';
require_role('admin');
$pdo = db();

$type = clean($_GET['type'] ?? 'sales');
$from = clean($_GET['from'] ?? '');
$to   = clean($_GET['to'] ?? '');

$filename = "quickbuy_{$type}_report_" . date('Ymd_His') . ".csv";

function sanitize_csv_row($row) {
    return array_map(function ($val) {
        $str = (string)($val ?? '');
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@'], true)) {
            return "'" . $str;
        }
        return $str;
    }, $row);
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

if ($type === 'sales') {
    fputcsv($out, sanitize_csv_row(['Txn ID', 'Date & Time', 'Cashier', 'Customer', 'Payment Method', 'Discount (₱)', 'Tax (₱)', 'Total Amount (₱)', 'Status']));

    $sql = 'SELECT s.id, s.created_at, u.full_name AS cashier, COALESCE(c.full_name, "Walk-in") AS customer,
                   s.payment_method, s.discount_amount, s.tax_amount, s.total_amount, s.payment_status
            FROM sales s
            JOIN users u ON u.id = s.cashier_id
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE 1=1';
    $args = [];

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(s.created_at) >= ?'; $args[] = $from; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $sql .= ' AND DATE(s.created_at) <= ?'; $args[] = $to; }

    $st = $pdo->prepare($sql . ' ORDER BY s.id DESC');
    $st->execute($args);

    while ($r = $st->fetch()) {
        fputcsv($out, sanitize_csv_row([
            $r['id'], $r['created_at'], $r['cashier'], $r['customer'],
            $r['payment_method'], $r['discount_amount'], $r['tax_amount'],
            $r['total_amount'], $r['payment_status']
        ]));
    }
} elseif ($type === 'products') {
    fputcsv($out, sanitize_csv_row(['Product ID', 'Barcode', 'Product Name', 'Category', 'Cost Price (₱)', 'Selling Price (₱)', 'Stock Quantity', 'Reorder Level']));

    $st = $pdo->query('SELECT p.id, p.barcode, p.name, c.name AS category_name, COALESCE(p.cost_price, 0.00) AS cost_price, p.price, p.stock_quantity, p.reorder_level FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.name');
    while ($r = $st->fetch()) {
        fputcsv($out, sanitize_csv_row([
            $r['id'], $r['barcode'] ?: '-', $r['name'], $r['category_name'],
            $r['cost_price'], $r['price'], $r['stock_quantity'], $r['reorder_level']
        ]));
    }
} elseif ($type === 'returns') {
    fputcsv($out, sanitize_csv_row(['Return ID', 'Date', 'Sale Txn ID', 'Staff', 'Refund Method', 'Total Refund (₱)', 'Reason']));

    $sql = 'SELECT r.id, r.created_at, r.sale_id, r.username, r.refund_method, r.total_refund, r.reason
            FROM returns r WHERE 1=1';
    $args = [];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(r.created_at) >= ?'; $args[] = $from; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $sql .= ' AND DATE(r.created_at) <= ?'; $args[] = $to; }

    $st = $pdo->prepare($sql . ' ORDER BY r.id DESC');
    $st->execute($args);

    while ($r = $st->fetch()) {
        fputcsv($out, sanitize_csv_row([
            $r['id'], $r['created_at'], $r['sale_id'], $r['username'],
            $r['refund_method'], $r['total_refund'], $r['reason']
        ]));
    }
}

fclose($out);
exit;
