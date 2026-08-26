<?php
// tutor/dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('tutor');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get tutor record
$stmtT = $pdo->prepare("SELECT * FROM tutors WHERE user_id = ?");
$stmtT->execute([$userId]);
$tutor = $stmtT->fetch();

if (!$tutor) {
    die("Tutor profile missing.");
}

$tutorId = $tutor['id'];

// Calculate total earnings
$stmtEarn = $pdo->prepare("
    SELECT SUM(p.amount) FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    WHERE b.tutor_id = ? AND p.status = 'paid'
");
$stmtEarn->execute([$tutorId]);
$totalEarnings = (float)($stmtEarn->fetchColumn() ?: 0.00);

// Total distinct students count
$stmtStCount = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM bookings WHERE tutor_id = ?");
$stmtStCount->execute([$tutorId]);
$totalStudents = $stmtStCount->fetchColumn();

// Pending requests count
$stmtPend = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tutor_id = ? AND status = 'pending'");
$stmtPend->execute([$tutorId]);
$pendingCount = $stmtPend->fetchColumn();

// Today's schedule
$today = date('Y-m-d');
$stmtToday = $pdo->prepare("
    SELECT b.*, u.name as student_name, s.name as subject_name
    FROM bookings b
    JOIN students st ON b.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE b.tutor_id = ? AND b.booking_date = ?
    ORDER BY b.start_time ASC
");
$stmtToday->execute([$tutorId, $today]);
$todaySessions = $stmtToday->fetchAll();

// Pending booking requests list
$stmtRequests = $pdo->prepare("
    SELECT b.*, u.name as student_name, u.profile_photo as student_photo, s.name as subject_name
    FROM bookings b
    JOIN students st ON b.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE b.tutor_id = ? AND b.status = 'pending'
    ORDER BY b.created_at DESC
");
$stmtRequests->execute([$tutorId]);
$pendingRequests = $stmtRequests->fetchAll();

$pageTitle = "Tutor Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <!-- Top Welcome Header (Inspired by Section 15 in Prompt) -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-extrabold text-dark mb-1">Good morning, <?php echo htmlspecialchars($_SESSION['name']); ?> 👋</h2>
                <p class="text-muted mb-0">Here is your tutoring performance summary and today's schedule.</p>
            </div>
            <a href="availability.php" class="btn btn-primary-custom">
                <i class="fa-regular fa-clock me-1"></i> Update Availability
            </a>
        </div>

        <!-- 4 Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-primary">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Today's Sessions</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo count($todaySessions); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-warning">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Pending Requests</span>
                    <h2 class="fw-extrabold text-warning mb-0"><?php echo $pendingCount; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-info">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Total Students</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo $totalStudents; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-success">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">This Month's Earnings</span>
                    <h2 class="fw-extrabold text-success mb-0">₱<?php echo number_format($totalEarnings, 2); ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Today's Schedule -->
            <div class="col-lg-6">
                <div class="card-static p-4 rounded-4 shadow-sm h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-day text-primary me-2"></i> Today's Schedule (<?php echo date('M d, Y'); ?>)</h5>

                    <?php if (count($todaySessions) > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($todaySessions as $ts): ?>
                                <div class="p-3 bg-light rounded-4 border d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary text-white rounded-3 p-2 text-center" style="min-width: 75px;">
                                            <span class="fw-bold d-block font-size-sm"><?php echo date('h:i A', strtotime($ts['start_time'])); ?></span>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($ts['student_name']); ?></h6>
                                            <span class="text-muted font-size-xs"><?php echo htmlspecialchars($ts['subject_name']); ?> • <?php echo $ts['duration_hours']; ?> hr</span>
                                        </div>
                                    </div>
                                    <span class="pill-badge pill-badge-success"><i class="fa-solid fa-check me-1"></i> Confirmed</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-mug-hot text-muted fs-2 mb-2"></i>
                            <p class="text-muted mb-0">No tutoring sessions scheduled for today.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking Requests Approval Card -->
            <div class="col-lg-6">
                <div class="card-static p-4 rounded-4 shadow-sm h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-user-clock text-warning me-2"></i> Booking Requests</h5>

                    <?php if (count($pendingRequests) > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($pendingRequests as $req): ?>
                                <div class="p-3 bg-light rounded-4 border">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?php echo htmlspecialchars($req['student_photo']); ?>" class="rounded-circle object-fit-cover" width="36" height="36" alt="Student">
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark font-size-sm"><?php echo htmlspecialchars($req['student_name']); ?></h6>
                                                <span class="text-muted font-size-xs"><?php echo htmlspecialchars($req['subject_name']); ?></span>
                                            </div>
                                        </div>
                                        <span class="fw-bold text-primary font-size-sm">₱<?php echo number_format($req['total_amount'], 2); ?></span>
                                    </div>

                                    <div class="font-size-xs text-muted mb-3">
                                        <i class="fa-regular fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($req['booking_date'])); ?> @ <?php echo date('h:i A', strtotime($req['start_time'])); ?>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="bookings.php?action=accept&id=<?php echo $req['id']; ?>" class="btn btn-primary-custom btn-sm flex-grow-1 py-1">Accept</a>
                                        <a href="bookings.php?action=decline&id=<?php echo $req['id']; ?>" class="btn btn-outline-custom btn-sm flex-grow-1 py-1 text-danger">Decline</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-circle-check text-muted fs-2 mb-2"></i>
                            <p class="text-muted mb-0">No pending booking requests.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
