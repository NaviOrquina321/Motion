<?php
// student/tutor-profile.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$tutorId = (int)($_GET['id'] ?? 1);

// Fetch Tutor profile data
$stmt = $pdo->prepare("
    SELECT t.*, u.name, u.email, u.profile_photo, u.created_at as joined_date
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$tutorId]);
$tutor = $stmt->fetch();

if (!$tutor) {
    die("Tutor profile not found.");
}

// Fetch subjects taught
$stmtSub = $pdo->prepare("
    SELECT s.*, ts.level
    FROM subjects s
    JOIN tutor_subjects ts ON s.id = ts.subject_id
    WHERE ts.tutor_id = ?
");
$stmtSub->execute([$tutorId]);
$subjectsTaught = $stmtSub->fetchAll();

// Fetch reviews
$stmtRev = $pdo->prepare("
    SELECT r.*, u.name as student_name, u.profile_photo as student_photo
    FROM reviews r
    JOIN students s ON r.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE r.tutor_id = ?
    ORDER BY r.created_at DESC
");
$stmtRev->execute([$tutorId]);
$reviews = $stmtRev->fetchAll();

$pageTitle = htmlspecialchars($tutor['name']) . " - Tutor Profile";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php if (getCurrentUser()): ?>
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="main-content flex-grow-1">
    <?php else: ?>
        <?php include __DIR__ . '/../includes/navbar.php'; ?>
        <div class="container py-4">
    <?php endif; ?>

        <!-- Tutor Profile Top Header Card (Inspired by UI Reference 1GzDx5oYVme...) -->
        <div class="card-static p-4 p-md-5 mb-4 rounded-4 border shadow-sm bg-white">
            <div class="row align-items-center g-4">
                <div class="col-md-3 text-center">
                    <img src="<?php echo htmlspecialchars($tutor['profile_photo']); ?>"
                         class="rounded-circle object-fit-cover shadow-sm mb-3 border border-4 border-light-subtle" width="130" height="130" alt="Tutor Photo">
                    <div class="badge bg-success-subtle text-success border rounded-pill font-size-xs px-3 py-1">
                        <i class="fa-solid fa-circle-check me-1"></i> Verified Educator
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h2 class="fw-extrabold text-dark mb-0"><?php echo htmlspecialchars($tutor['name']); ?></h2>
                        <i class="fa-solid fa-circle-check text-primary fs-4" title="Verified Badge"></i>
                        <span class="badge bg-primary-subtle text-primary font-size-xs ms-2">TOP TUTOR</span>
                    </div>
                    <p class="text-primary fw-semibold fs-5 mb-3"><?php echo htmlspecialchars($tutor['title']); ?></p>

                    <div class="d-flex flex-wrap gap-3 font-size-sm text-muted mb-3">
                        <div class="d-flex align-items-center gap-1">
                            <i class="fa-solid fa-briefcase text-primary"></i>
                            <span><?php echo $tutor['experience']; ?>+ Years Experience</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <i class="fa-solid fa-book-open text-primary"></i>
                            <span><?php echo $tutor['lessons_conducted']; ?> Lessons Conducted</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <i class="fa-solid fa-user-graduate text-primary"></i>
                            <span>250+ Students</span>
                        </div>
                    </div>

                    <p class="text-muted line-clamp-3 mb-0"><?php echo htmlspecialchars($tutor['bio']); ?></p>
                </div>

                <div class="col-md-3 text-md-end border-start-md ps-md-4">
                    <div class="bg-light p-3 rounded-4 mb-3 text-center">
                        <span class="text-muted font-size-xs d-block mb-1">Price Per Lesson</span>
                        <div class="fw-extrabold fs-2 text-dark">₱<?php echo number_format($tutor['hourly_rate'], 2); ?><span class="font-size-xs text-muted fw-normal">/hr</span></div>
                        <span class="text-muted font-size-xs d-block mt-1"><i class="fa-regular fa-clock me-1"></i><?php echo htmlspecialchars($tutor['response_time']); ?></span>
                    </div>

                    <a href="booking.php?tutor_id=<?php echo $tutor['id']; ?>" class="btn btn-primary-custom w-100 py-3 font-weight-bold mb-2">
                        <i class="fa-solid fa-calendar-plus me-1"></i> Book A Session
                    </a>
                    <a href="messages.php?tutor_id=<?php echo $tutor['user_id']; ?>" class="btn btn-outline-custom w-100 py-2 btn-sm">
                        <i class="fa-regular fa-comments me-1"></i> Chat With Tutor
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Info Column -->
            <div class="col-lg-8">
                <!-- Education & Qualifications -->
                <div class="card-static p-4 mb-4 rounded-4 shadow-sm">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-graduation-cap text-primary me-2"></i> Education & Background</h5>
                    <p class="text-dark fw-semibold mb-1"><?php echo htmlspecialchars($tutor['education']); ?></p>
                    <p class="text-muted font-size-sm mb-0">Certified subject matter expert with extensive experience in academic guidance, curriculum preparation, and personalized learning support.</p>
                </div>

                <!-- Subjects Offered -->
                <div class="card-static p-4 mb-4 rounded-4 shadow-sm">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-book text-primary me-2"></i> Subjects & Courses Offered</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($subjectsTaught as $sub): ?>
                            <div class="border rounded-3 p-3 bg-light d-flex align-items-center justify-content-between flex-grow-1" style="min-width: 220px;">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($sub['name']); ?></h6>
                                    <span class="text-muted font-size-xs"><?php echo htmlspecialchars($sub['category']); ?> • <?php echo htmlspecialchars($sub['level']); ?></span>
                                </div>
                                <span class="badge bg-primary-subtle text-primary"><i class="fa-solid fa-check"></i></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Student Reviews List -->
                <div class="card-static p-4 rounded-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-star text-warning me-2"></i> Student Reviews (<?php echo count($reviews); ?>)</h5>
                    </div>

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
                        <p class="text-muted mb-0">No student reviews written yet for this tutor.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Rating Summary Breakdown (Matching UI Ref 1GzDx5oYVme...) -->
            <div class="col-lg-4">
                <div class="card-static p-4 rounded-4 shadow-sm mb-4">
                    <h5 class="fw-bold text-dark mb-3">Rating Summary</h5>

                    <div class="text-center p-3 bg-light rounded-4 mb-4">
                        <h1 class="display-4 fw-extrabold text-dark mb-0"><?php echo number_format($tutor['rating'], 1); ?></h1>
                        <div class="text-warning mb-1">
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <span class="text-muted font-size-xs"><?php echo $tutor['total_reviews']; ?> Verified Ratings</span>
                    </div>

                    <!-- Breakdown sliders -->
                    <div class="d-flex flex-column gap-3 font-size-sm">
                        <div>
                            <div class="d-flex justify-content-between font-weight-semibold mb-1">
                                <span>Qualifications</span>
                                <span class="text-primary fw-bold">4.9</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 98%;"></div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between font-weight-semibold mb-1">
                                <span>Expertise</span>
                                <span class="text-primary fw-bold">4.8</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 95%;"></div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between font-weight-semibold mb-1">
                                <span>Communication</span>
                                <span class="text-primary fw-bold">5.0</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;"></div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between font-weight-semibold mb-1">
                                <span>Value for Money</span>
                                <span class="text-primary fw-bold">4.7</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 92%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-static p-4 rounded-4 shadow-sm text-center">
                    <i class="fa-solid fa-calendar-check text-primary fs-2 mb-2"></i>
                    <h6 class="fw-bold text-dark">Flexible Availability</h6>
                    <p class="text-muted font-size-xs mb-3">Available Monday to Saturday for 1-on-1 tutoring sessions.</p>
                    <a href="booking.php?tutor_id=<?php echo $tutor['id']; ?>" class="btn btn-primary-custom w-100 py-2 btn-sm">Check Available Slots</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
