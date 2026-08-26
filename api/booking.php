<?php
// api/booking.php
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

// Get student id
$stmtSt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmtSt->execute([$_SESSION['user_id']]);
$student = $stmtSt->fetch();

if (!$student) {
    echo json_encode(['status' => 'error', 'message' => 'Student record not found.']);
    exit;
}

$tutor_id = (int)($_POST['tutor_id'] ?? 0);
$subject_id = (int)($_POST['subject_id'] ?? 0);
$booking_date = $_POST['booking_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$duration_hours = (int)($_POST['duration_hours'] ?? 1);
$notes = trim($_POST['notes'] ?? '');

if ($tutor_id <= 0 || $subject_id <= 0 || empty($booking_date) || empty($start_time)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all booking fields.']);
    exit;
}

// Calculate end time
$startTimestamp = strtotime("$booking_date $start_time");
$endTimestamp = strtotime("+$duration_hours hour", $startTimestamp);
$end_time = date('H:i:s', $endTimestamp);

// Check double-booking conflict for tutor
$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) FROM bookings
    WHERE tutor_id = ?
    AND booking_date = ?
    AND status IN ('confirmed', 'pending')
    AND (
        (start_time <= ? AND end_time > ?) OR
        (start_time < ? AND end_time >= ?) OR
        (start_time >= ? AND end_time <= ?)
    )
");
$stmtCheck->execute([
    $tutor_id, $booking_date,
    $start_time, $start_time,
    $end_time, $end_time,
    $start_time, $end_time
]);

if ($stmtCheck->fetchColumn() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Selected time slot conflicts with an existing booking for this tutor.']);
    exit;
}

// Fetch tutor rate
$stmtT = $pdo->prepare("SELECT hourly_rate FROM tutors WHERE id = ?");
$stmtT->execute([$tutor_id]);
$tutor = $stmtT->fetch();
$hourly_rate = (float)($tutor['hourly_rate'] ?? 250.00);
$total_amount = $hourly_rate * $duration_hours;

// Create Booking record
$stmtIns = $pdo->prepare("
    INSERT INTO bookings (student_id, tutor_id, subject_id, booking_date, start_time, end_time, duration_hours, hourly_rate, total_amount, status, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)
");

if ($stmtIns->execute([$student['id'], $tutor_id, $subject_id, $booking_date, $start_time, $end_time, $duration_hours, $hourly_rate, $total_amount, $notes])) {
    $booking_id = $pdo->lastInsertId();

    // Create Notification for tutor user
    $stmtTUser = $pdo->prepare("SELECT user_id FROM tutors WHERE id = ?");
    $stmtTUser->execute([$tutor_id]);
    $tutorUserId = $stmtTUser->fetchColumn();

    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'booking')");
    $stmtNotif->execute([$tutorUserId, 'New Session Booked', "A new tutoring session has been scheduled for $booking_date at $start_time."]);

    echo json_encode([
        'status' => 'success',
        'booking_id' => $booking_id,
        'total_amount' => $total_amount,
        'redirect' => "../student/payments.php?booking_id=$booking_id"
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error while saving booking.']);
}
