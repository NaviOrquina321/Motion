<?php
// api/messages.php
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
$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? 'fetch';

if ($action === 'send') {
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($receiverId <= 0 || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Message content or receiver missing.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$userId, $receiverId, $message])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
    }
    exit;
}

// Fetch conversation thread
$partnerId = (int)($_GET['partner_id'] ?? 0);
if ($partnerId <= 0) {
    echo json_encode(['status' => 'success', 'messages' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.*, u.name as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$userId, $partnerId, $partnerId, $userId]);
$messages = $stmt->fetchAll();

echo json_encode(['status' => 'success', 'messages' => $messages]);
