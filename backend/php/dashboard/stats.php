<?php
/* GET -> numbers for the dashboard cards + the Low Stock Alert list. */
require_once __DIR__ . '/../config/helpers.php';
require_login();
$pdo = db();

$one = function ($sql) use ($pdo) { return $pdo->query($sql)->fetch(); };
$products  = $one('SELECT COUNT(*) AS n FROM products')['n'];
$customers = $one('SELECT COUNT(*) AS n FROM customers')['n'];
$all       = $one('SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS amt FROM sales');
$today     = $one('SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS amt FROM sales WHERE DATE(created_at) = CURDATE()');

// Low stock = stock is equal to or below the reorder level
$low = $pdo->query(
    'SELECT p.id, p.name, c.name AS category_name, p.stock_quantity, p.reorder_level
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.stock_quantity <= p.reorder_level
     ORDER BY p.stock_quantity ASC'
)->fetchAll();

json_out([
    'success' => true,
    'total_products'    => (int)$products,
    'total_customers'   => (int)$customers,
    'total_sales_count' => (int)$all['n'],
    'total_sales_amount'=> (float)$all['amt'],
    'today_count'       => (int)$today['n'],
    'today_amount'      => (float)$today['amt'],
    'low_stock_count'   => count($low),
    'low_stock'         => $low,
]);
