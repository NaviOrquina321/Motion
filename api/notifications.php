<?php
// api/notifications.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'mark_read') {
    $notif_id = (int)($_POST['id'] ?? 0);
    if ($notif_id > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notif_id, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

// Fetch user notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifs = $stmt->fetchAll();

echo json_encode(['status' => 'success', 'notifications' => $notifs]);
