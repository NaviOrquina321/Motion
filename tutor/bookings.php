<?php
// tutor/bookings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('tutor');

$pdo = getDBConnection();
$stmtT = $pdo->prepare("SELECT id FROM tutors WHERE user_id = ?");
$stmtT->execute([$_SESSION['user_id']]);
$tutorId = $stmtT->fetchColumn();

// Process actions
$action = $_GET['action'] ?? '';
$bookingId = (int)($_GET['id'] ?? 0);

if ($bookingId > 0 && in_array($action, ['accept', 'decline', 'complete'])) {
    $newStatus = ($action === 'accept') ? 'confirmed' : (($action === 'complete') ? 'completed' : 'cancelled');
    $stmtUpd = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND tutor_id = ?");
    $stmtUpd->execute([$newStatus, $bookingId, $tutorId]);

    // Send notification to student
    $stmtBStudent = $pdo->prepare("SELECT st.user_id FROM bookings b JOIN students st ON b.student_id = st.id WHERE b.id = ?");
    $stmtBStudent->execute([$bookingId]);
    $studentUserId = $stmtBStudent->fetchColumn();

    if ($studentUserId) {
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'booking')");
        $stmtNotif->execute([$studentUserId, "Booking $action", "Your tutoring session status was updated to $newStatus."]);
    }

    header("Location: bookings.php");
    exit;
}

// Fetch all bookings for this tutor
$stmtAll = $pdo->prepare("
    SELECT b.*, u.name as student_name, u.profile_photo as student_photo, s.name as subject_name
    FROM bookings b
    JOIN students st ON b.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE b.tutor_id = ?
    ORDER BY b.booking_date DESC, b.start_time ASC
");
$stmtAll->execute([$tutorId]);
$bookings = $stmtAll->fetchAll();

$pageTitle = "Manage Booking Requests";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-calendar-check me-1"></i> Session Requests
            </span>
            <h2 class="fw-extrabold text-dark mb-1">Booking Requests</h2>
            <p class="text-muted mb-0">Accept or decline incoming session requests from students.</p>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light font-size-xs text-muted">
                            <tr>
                                <th>STUDENT</th>
                                <th>SUBJECT</th>
                                <th>DATE & TIME</th>
                                <th>AMOUNT</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?php echo htmlspecialchars($b['student_photo']); ?>" class="rounded-circle object-fit-cover" width="36" height="36" alt="Student">
                                            <span class="fw-bold text-dark font-size-sm"><?php echo htmlspecialchars($b['student_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark font-size-sm"><?php echo htmlspecialchars($b['subject_name']); ?></span>
                                    </td>
                                    <td class="font-size-sm text-muted">
                                        <?php echo date('M d, Y', strtotime($b['booking_date'])); ?> @ <?php echo date('h:i A', strtotime($b['start_time'])); ?>
                                    </td>
                                    <td class="fw-bold text-dark">₱<?php echo number_format($b['total_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($b['status'] === 'confirmed'): ?>
                                            <span class="pill-badge pill-badge-primary"><i class="fa-solid fa-check me-1"></i> Confirmed</span>
                                        <?php elseif ($b['status'] === 'completed'): ?>
                                            <span class="pill-badge pill-badge-success"><i class="fa-solid fa-circle-check me-1"></i> Completed</span>
                                        <?php elseif ($b['status'] === 'pending'): ?>
                                            <span class="pill-badge pill-badge-warning"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <a href="bookings.php?action=accept&id=<?php echo $b['id']; ?>" class="btn btn-primary-custom btn-sm py-1 font-size-xs">Accept</a>
                                            <a href="bookings.php?action=decline&id=<?php echo $b['id']; ?>" class="btn btn-outline-custom btn-sm py-1 font-size-xs text-danger">Decline</a>
                                        <?php elseif ($b['status'] === 'confirmed'): ?>
                                            <a href="bookings.php?action=complete&id=<?php echo $b['id']; ?>" class="btn btn-success btn-sm py-1 font-size-xs text-white">Mark Complete</a>
                                        <?php else: ?>
                                            <span class="text-muted font-size-xs">Done</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No booking requests found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
