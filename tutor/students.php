<?php
// tutor/students.php
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

// Fetch distinct students who booked this tutor
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id as user_id, u.name, u.email, u.profile_photo, st.education_level, st.learning_style,
           COUNT(b.id) as total_sessions
    FROM bookings b
    JOIN students st ON b.student_id = st.id
    JOIN users u ON st.user_id = u.id
    WHERE b.tutor_id = ?
    GROUP BY u.id
");
$stmt->execute([$tutorId]);
$studentsList = $stmt->fetchAll();

$pageTitle = "My Students";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-user-graduate me-1"></i> Student Roster
            </span>
            <h2 class="fw-extrabold text-dark mb-1">My Enrolled Students</h2>
            <p class="text-muted mb-0">Overview of students who have completed or booked sessions with you.</p>
        </div>

        <div class="row g-4">
            <?php if (count($studentsList) > 0): ?>
                <?php foreach ($studentsList as $st): ?>
                    <div class="col-md-4">
                        <div class="card-custom p-4 text-center">
                            <img src="<?php echo htmlspecialchars($st['profile_photo']); ?>" class="rounded-circle object-fit-cover shadow-sm mb-3" width="70" height="70" alt="Student">
                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($st['name']); ?></h5>
                            <span class="text-muted font-size-xs d-block mb-2"><?php echo htmlspecialchars($st['email']); ?></span>

                            <div class="d-flex justify-content-center gap-2 mb-3 font-size-xs">
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($st['education_level']); ?></span>
                                <span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($st['learning_style']); ?></span>
                            </div>

                            <div class="p-2 bg-light rounded-3 font-size-xs text-muted mb-3">
                                <strong><?php echo $st['total_sessions']; ?></strong> Sessions Booked
                            </div>

                            <a href="messages.php?partner_id=<?php echo $st['user_id']; ?>" class="btn btn-outline-custom btn-sm w-100">
                                <i class="fa-regular fa-comments me-1"></i> Message Student
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card-static p-5 text-center">
                        <i class="fa-solid fa-users-slash text-muted fs-1 mb-2"></i>
                        <h5 class="fw-bold">No Students Yet</h5>
                        <p class="text-muted mb-0">Students who book your sessions will appear here.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
