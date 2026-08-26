<?php
// student/dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get student record
$stmtSt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmtSt->execute([$userId]);
$student = $stmtSt->fetch();
$studentId = $student['id'];

// Stats
$upcomingCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE student_id = ? AND status = 'confirmed'");
$upcomingCount->execute([$studentId]);
$numUpcoming = $upcomingCount->fetchColumn();

$completedCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE student_id = ? AND status = 'completed'");
$completedCount->execute([$studentId]);
$numCompleted = $completedCount->fetchColumn();

$totalSpentStmt = $pdo->prepare("SELECT SUM(total_amount) FROM payments WHERE student_id = ? AND status = 'paid'");
$totalSpentStmt->execute([$studentId]);
$totalSpent = (float)($totalSpentStmt->fetchColumn() ?: 0.00);

$tutorsConnectedStmt = $pdo->prepare("SELECT COUNT(DISTINCT tutor_id) FROM bookings WHERE student_id = ?");
$tutorsConnectedStmt->execute([$studentId]);
$tutorsConnected = $tutorsConnectedStmt->fetchColumn();

// Upcoming session
$stmtNext = $pdo->prepare("
    SELECT b.*, u.name as tutor_name, u.profile_photo as tutor_photo, s.name as subject_name
    FROM bookings b
    JOIN tutors t ON b.tutor_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE b.student_id = ? AND b.status = 'confirmed'
    ORDER BY b.booking_date ASC, b.start_time ASC
    LIMIT 1
");
$stmtNext->execute([$studentId]);
$nextSession = $stmtNext->fetch();

$pageTitle = "Student Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <!-- Dashboard Header (Matching Prompt Section 7) -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-extrabold text-dark mb-1">Good morning, <?php echo htmlspecialchars($_SESSION['name']); ?> 👋</h2>
                <p class="text-muted mb-0">Ready to learn something new today?</p>
            </div>
            <a href="ai-matching.php" class="btn btn-primary-custom">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Find a Tutor
            </a>
        </div>

        <!-- 4 Stats Cards (Matching Prompt Section 7) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-primary">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Upcoming Sessions</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo $numUpcoming; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-success">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Completed Sessions</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo $numCompleted; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-info">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Total Spent</span>
                    <h2 class="fw-extrabold text-dark mb-0">₱<?php echo number_format($totalSpent, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-warning">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Tutors Connected</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo $tutorsConnected; ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Upcoming Session Banner -->
            <div class="col-lg-6">
                <div class="card-static p-4 rounded-4 shadow-sm h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-clock text-primary me-2"></i> Next Upcoming Session</h5>

                    <?php if ($nextSession): ?>
                        <div class="p-4 bg-primary-light rounded-4 border border-primary-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="badge bg-primary text-white px-3 py-1 font-size-xs fw-semibold">
                                    <?php echo htmlspecialchars($nextSession['subject_name']); ?>
                                </span>
                                <span class="text-primary font-weight-bold font-size-xs">
                                    <i class="fa-regular fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($nextSession['booking_date'])); ?>
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="<?php echo htmlspecialchars($nextSession['tutor_photo']); ?>" class="rounded-circle object-fit-cover shadow-sm" width="54" height="54" alt="Tutor">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($nextSession['tutor_name']); ?></h6>
                                    <span class="text-muted font-size-xs"><?php echo date('h:i A', strtotime($nextSession['start_time'])); ?> - <?php echo date('h:i A', strtotime($nextSession['end_time'])); ?></span>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="calendar.php" class="btn btn-primary-custom flex-grow-1 py-2 font-size-sm">Join Session</a>
                                <a href="tutor-profile.php?id=<?php echo $nextSession['tutor_id']; ?>" class="btn btn-outline-custom flex-grow-1 py-2 font-size-sm">View Details</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-calendar-plus text-muted fs-1 mb-2"></i>
                            <h6 class="fw-bold text-dark">No Upcoming Sessions</h6>
                            <p class="text-muted font-size-xs mb-3">Book your next tutoring lesson now.</p>
                            <a href="find-tutor.php" class="btn btn-primary-custom btn-sm">Find a Tutor</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick AI Recommendation Widget -->
            <div class="col-lg-6">
                <div class="card-static p-4 rounded-4 shadow-sm h-100 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i> Recommended AI Tutors</h5>
                        <a href="ai-matching.php" class="font-size-xs text-primary fw-bold text-decoration-none">View All Matches</a>
                    </div>
                    <p class="text-muted font-size-sm mb-3">Based on your learning preferences and schedule.</p>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 bg-light rounded-4 border d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=150" class="rounded-circle object-fit-cover" width="40" height="40" alt="Tutor">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark font-size-sm">Maria Santos</h6>
                                    <span class="text-muted font-size-xs">Mathematics • ₱250/hr</span>
                                </div>
                            </div>
                            <span class="match-score-badge font-size-xs"><i class="fa-solid fa-bolt"></i> 95% Match</span>
                        </div>

                        <div class="p-3 bg-light rounded-4 border d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150" class="rounded-circle object-fit-cover" width="40" height="40" alt="Tutor">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark font-size-sm">Juan Dela Cruz</h6>
                                    <span class="text-muted font-size-xs">Programming • ₱300/hr</span>
                                </div>
                            </div>
                            <span class="match-score-badge font-size-xs"><i class="fa-solid fa-bolt"></i> 88% Match</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
