<?php
// api/payment.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$pdo = getDBConnection();

// Get student record
$stmtSt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmtSt->execute([$_SESSION['user_id']]);
$student = $stmtSt->fetch();

$booking_id = (int)($_POST['booking_id'] ?? 0);
$gcash_number = trim($_POST['gcash_number'] ?? '');
$reference_number = trim($_POST['reference_number'] ?? '');

if ($booking_id <= 0 || empty($gcash_number)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid GCash account number.']);
    exit;
}

// Fetch booking details
$stmtB = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND student_id = ?");
$stmtB->execute([$booking_id, $student['id']]);
$booking = $stmtB->fetch();

if (!$booking) {
    echo json_encode(['status' => 'error', 'message' => 'Booking record not found.']);
    exit;
}

$amount = (float)$booking['total_amount'];
$service_fee = 10.00;
$total_amount = $amount + $service_fee;
$transaction_id = 'TL-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

if (empty($reference_number)) {
    $reference_number = 'GCASH-REF-' . rand(100000, 999999);
}

// Check if payment already exists
$stmtPCheck = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ?");
$stmtPCheck->execute([$booking_id]);
$existingPayment = $stmtPCheck->fetch();

if ($existingPayment) {
    $stmtUpd = $pdo->prepare("
        UPDATE payments SET account_number = ?, reference_number = ?, transaction_id = ?, status = 'paid', paid_at = CURRENT_TIMESTAMP
        WHERE booking_id = ?
    ");
    $stmtUpd->execute([$gcash_number, $reference_number, $transaction_id, $booking_id]);
} else {
    $stmtIns = $pdo->prepare("
        INSERT INTO payments (booking_id, student_id, amount, service_fee, total_amount, payment_method, account_number, reference_number, transaction_id, status)
        VALUES (?, ?, ?, ?, ?, 'GCash', ?, ?, ?, 'paid')
    ");
    $stmtIns->execute([$booking_id, $student['id'], $amount, $service_fee, $total_amount, $gcash_number, $reference_number, $transaction_id]);
}

// Update booking status to confirmed
$stmtBUpd = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
$stmtBUpd->execute([$booking_id]);

// Add Notification for student
$stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'payment')");
$stmtNotif->execute([
    $_SESSION['user_id'],
    'GCash Payment Successful',
    "Your payment of ₱" . number_format($total_amount, 2) . " (Tx: $transaction_id) was processed successfully."
]);

echo json_encode([
    'status' => 'success',
    'transaction_id' => $transaction_id,
    'total_amount' => $total_amount,
    'reference_number' => $reference_number,
    'message' => 'GCash Payment completed successfully!'
]);
