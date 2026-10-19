<?php
/* Activity Log API (Admin only). ?action=list &q=... &from=... &to=... */
require_once __DIR__ . '/../config/helpers.php';
require_role('admin');
$pdo = db();

$q    = clean($_GET['q'] ?? '');
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$sql = 'SELECT id, user_id, username, action, details, created_at FROM activity_logs WHERE 1=1';
$args = [];

if ($q !== '') {
    $sql .= ' AND (username LIKE ? OR action LIKE ? OR details LIKE ?)';
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND DATE(created_at) >= ?'; $args[] = $from; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $sql .= ' AND DATE(created_at) <= ?'; $args[] = $to; }

$st = $pdo->prepare($sql . ' ORDER BY id DESC LIMIT 500');
$st->execute($args);

json_out(['success' => true, 'logs' => $st->fetchAll()]);
