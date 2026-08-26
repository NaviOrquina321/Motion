<?php
// admin/bookings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT b.*, u_st.name as student_name, u_tu.name as tutor_name, s.name as subject_name
    FROM bookings b
    JOIN students st ON b.student_id = st.id
    JOIN users u_st ON st.user_id = u_st.id
    JOIN tutors tu ON b.tutor_id = tu.id
    JOIN users u_tu ON tu.user_id = u_tu.id
    JOIN subjects s ON b.subject_id = s.id
    ORDER BY b.booking_date DESC, b.start_time ASC
");
$allBookings = $stmt->fetchAll();

$pageTitle = "All Platform Bookings";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-calendar-check me-1"></i> System Schedules
            </span>
            <h2 class="fw-extrabold text-dark mb-1">All Platform Bookings</h2>
            <p class="text-muted mb-0">Complete ledger of tutoring bookings and scheduled sessions.</p>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light font-size-xs text-muted">
                        <tr>
                            <th>ID</th>
                            <th>STUDENT</th>
                            <th>TUTOR</th>
                            <th>SUBJECT</th>
                            <th>DATE & TIME</th>
                            <th>TOTAL AMOUNT</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allBookings as $bk): ?>
                            <tr>
                                <td class="fw-bold text-muted font-size-xs">#<?php echo $bk['id']; ?></td>
                                <td class="fw-bold text-dark font-size-sm"><?php echo htmlspecialchars($bk['student_name']); ?></td>
                                <td class="fw-semibold text-primary font-size-sm"><?php echo htmlspecialchars($bk['tutor_name']); ?></td>
                                <td class="font-size-sm text-dark"><?php echo htmlspecialchars($bk['subject_name']); ?></td>
                                <td class="font-size-sm text-muted">
                                    <?php echo date('M d, Y', strtotime($bk['booking_date'])); ?> @ <?php echo date('h:i A', strtotime($bk['start_time'])); ?>
                                </td>
                                <td class="fw-extrabold text-dark font-size-sm">₱<?php echo number_format($bk['total_amount'], 2); ?></td>
                                <td>
                                    <?php if ($bk['status'] === 'confirmed'): ?>
                                        <span class="pill-badge pill-badge-primary">Confirmed</span>
                                    <?php elseif ($bk['status'] === 'completed'): ?>
                                        <span class="pill-badge pill-badge-success">Completed</span>
                                    <?php else: ?>
                                        <span class="pill-badge pill-badge-warning"><?php echo ucfirst($bk['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
