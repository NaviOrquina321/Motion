<?php
/* GET -> numbers for the dashboard cards + Low Stock Alert + Top Products + Recent Sales + 7-Day Chart Series. */
require_once __DIR__ . '/../config/helpers.php';
require_login();
$pdo = db();

$one = function ($sql, $params = []) use ($pdo) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
};

$all = $one('SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS amt FROM sales');
$today = $one('SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS amt FROM sales WHERE DATE(created_at) = CURDATE()');
$this_week = $one('SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS amt FROM sales WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$this_month = $one('SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS amt FROM sales WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())');

$total_tx = (int)($all['n'] ?? 0);
$total_amt = (float)($all['amt'] ?? 0);
$avg_sale = $total_tx > 0 ? ($total_amt / $total_tx) : 0;

$products_count = $one('SELECT COUNT(*) AS n FROM products')['n'] ?? 0;
$customers_count = $one('SELECT COUNT(*) AS n FROM customers')['n'] ?? 0;

// Low stock = stock is equal to or below the reorder level
$low = $pdo->query(
    'SELECT p.id, p.name, c.name AS category_name, p.stock_quantity, p.reorder_level
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.stock_quantity <= p.reorder_level
     ORDER BY p.stock_quantity ASC'
)->fetchAll();

// Top 5 products sold
$top_products = $pdo->query(
    'SELECT p.name, SUM(si.quantity) as total_qty, SUM(si.subtotal) as total_revenue
     FROM sale_items si
     JOIN products p ON p.id = si.product_id
     GROUP BY si.product_id, p.name
     ORDER BY total_qty DESC
     LIMIT 5'
)->fetchAll();

// 5 Recent Sales
$recent_sales = $pdo->query(
    'SELECT s.id, s.total_amount, s.created_at, COALESCE(c.full_name, "Walk-in Customer") as customer_name, u.full_name as cashier_name
     FROM sales s
     LEFT JOIN customers c ON c.id = s.customer_id
     LEFT JOIN users u ON u.id = s.cashier_id
     ORDER BY s.id DESC
     LIMIT 5'
)->fetchAll();

// 7-day daily chart series
$daily_series = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $st = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0.00) as rev, COUNT(*) as cnt FROM sales WHERE DATE(created_at) = ?');
    $st->execute([$date]);
    $row = $st->fetch();
    $daily_series[] = [
        'day' => $date,
        'revenue' => (float)$row['rev'],
        'count' => (int)$row['cnt']
    ];
}

json_out([
    'success' => true,
    'total_products'     => (int)$products_count,
    'total_customers'    => (int)$customers_count,
    'total_sales_count'  => $total_tx,
    'total_sales_amount' => $total_amt,
    'average_sale'       => round($avg_sale, 2),
    'today_count'        => (int)($today['n'] ?? 0),
    'today_amount'       => (float)($today['amt'] ?? 0),
    'week_count'         => (int)($this_week['n'] ?? 0),
    'week_amount'        => (float)($this_week['amt'] ?? 0),
    'month_count'        => (int)($this_month['n'] ?? 0),
    'month_amount'       => (float)($this_month['amt'] ?? 0),
    'low_stock_count'    => count($low),
    'low_stock'          => $low,
    'top_products'       => $top_products,
    'recent_sales'       => $recent_sales,
    'daily_series'       => $daily_series
]);
