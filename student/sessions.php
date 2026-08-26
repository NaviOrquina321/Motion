<?php
// student/sessions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pdo = getDBConnection();
$stmtSt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmtSt->execute([$_SESSION['user_id']]);
$student = $stmtSt->fetch();

$stmt = $pdo->prepare("
    SELECT b.*, u.name as tutor_name, u.profile_photo as tutor_photo, s.name as subject_name
    FROM bookings b
    JOIN tutors t ON b.tutor_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE b.student_id = ?
    ORDER BY b.booking_date DESC
");
$stmt->execute([$student['id']]);
$sessions = $stmt->fetchAll();

$pageTitle = "My Tutoring Sessions";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-book-open me-1"></i> Learning History
            </span>
            <h2 class="fw-extrabold text-dark mb-1">My Sessions</h2>
            <p class="text-muted mb-0">Overview of all your booked, confirmed, and completed tutoring sessions.</p>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <?php if (count($sessions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light font-size-xs text-muted">
                            <tr>
                                <th>TUTOR</th>
                                <th>SUBJECT</th>
                                <th>DATE & TIME</th>
                                <th>DURATION</th>
                                <th>TOTAL</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?php echo htmlspecialchars($s['tutor_photo']); ?>" class="rounded-circle object-fit-cover" width="36" height="36" alt="Tutor">
                                            <span class="fw-bold text-dark font-size-sm"><?php echo htmlspecialchars($s['tutor_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-dark font-size-sm"><?php echo htmlspecialchars($s['subject_name']); ?></td>
                                    <td class="font-size-sm text-muted">
                                        <?php echo date('M d, Y', strtotime($s['booking_date'])); ?> @ <?php echo date('h:i A', strtotime($s['start_time'])); ?>
                                    </td>
                                    <td class="font-size-sm text-muted"><?php echo $s['duration_hours']; ?> hr</td>
                                    <td class="fw-bold text-dark font-size-sm">₱<?php echo number_format($s['total_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($s['status'] === 'confirmed'): ?>
                                            <span class="pill-badge pill-badge-primary"><i class="fa-solid fa-check me-1"></i> Confirmed</span>
                                        <?php elseif ($s['status'] === 'completed'): ?>
                                            <span class="pill-badge pill-badge-success"><i class="fa-solid fa-circle-check me-1"></i> Completed</span>
                                        <?php else: ?>
                                            <span class="pill-badge pill-badge-warning"><?php echo ucfirst($s['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No tutoring sessions booked yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
