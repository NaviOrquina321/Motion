<?php
// tutor/reviews.php
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

// Fetch reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.name as student_name, u.profile_photo as student_photo
    FROM reviews r
    JOIN students s ON r.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE r.tutor_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$tutorId]);
$reviews = $stmt->fetchAll();

$pageTitle = "Student Reviews";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-regular fa-star me-1"></i> Feedback & Ratings
            </span>
            <h2 class="fw-extrabold text-dark mb-1">Student Reviews</h2>
            <p class="text-muted mb-0">Read feedback and ratings submitted by students who completed sessions with you.</p>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <?php if (count($reviews) > 0): ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="p-3 bg-light rounded-4 border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo htmlspecialchars($rev['student_photo']); ?>" class="rounded-circle object-fit-cover" width="36" height="36" alt="Student">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark font-size-sm"><?php echo htmlspecialchars($rev['student_name']); ?></h6>
                                        <span class="text-muted font-size-xs"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="text-warning font-size-xs">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa-<?php echo $i <= $rev['rating'] ? 'solid' : 'regular'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-muted font-size-sm mb-0"><?php echo htmlspecialchars($rev['comment']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No reviews recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
