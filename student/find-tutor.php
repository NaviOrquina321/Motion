<?php
// student/find-tutor.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();

// Filter inputs
$subjectFilter = (int)($_GET['subject_id'] ?? 0);
$levelFilter = $_GET['level'] ?? '';
$maxPrice = (float)($_GET['max_price'] ?? 0);
$search = trim($_GET['search'] ?? '');

// Fetch subjects for filter dropdown
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll();

// Build Query
$sql = "
    SELECT DISTINCT t.*, u.name, u.profile_photo, u.email
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN tutor_subjects ts ON t.id = ts.tutor_id
    WHERE u.status = 'active' AND t.verification_status = 'verified'
";
$params = [];

if ($subjectFilter > 0) {
    $sql .= " AND ts.subject_id = ?";
    $params[] = $subjectFilter;
}
if (!empty($levelFilter)) {
    $sql .= " AND (ts.level = ? OR ts.level = 'All Levels')";
    $params[] = $levelFilter;
}
if ($maxPrice > 0) {
    $sql .= " AND t.hourly_rate <= ?";
    $params[] = $maxPrice;
}
if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR t.title LIKE ? OR t.bio LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY t.rating DESC, t.lessons_conducted DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tutors = $stmt->fetchAll();

$pageTitle = "Find Tutors";
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

        <!-- Search Header Banner inspired by UI Reference 1_VYjDWrA6... -->
        <div class="card-static p-4 p-md-5 mb-4 text-white position-relative overflow-hidden rounded-4" style="background: linear-gradient(135deg, #4F46E5 0%, #312E81 100%);">
            <div class="position-relative z-1 max-w-2xl">
                <span class="badge bg-white-20 text-white mb-2 px-3 py-2 rounded-pill font-size-xs fw-semibold">
                    <i class="fa-solid fa-graduation-cap me-1"></i> Qualified Educators
                </span>
                <h2 class="display-6 fw-extrabold mb-3">Find A Tutor That Will Make You Stand Out!</h2>

                <form method="GET" action="find-tutor.php" class="bg-white p-2 rounded-4 shadow d-flex align-items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-muted ps-3"></i>
                    <input type="text" name="search" class="form-control border-0 shadow-none text-dark" placeholder="What subject or tutor do you want to find?" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary-custom px-4 py-2 font-weight-bold rounded-3">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <!-- Filter Sidebar -->
            <div class="col-lg-3">
                <div class="card-static p-4 shadow-sm rounded-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-filter text-primary me-2"></i> Filters</h6>
                        <a href="find-tutor.php" class="font-size-xs text-muted text-decoration-none">Clear all</a>
                    </div>

                    <form method="GET" action="find-tutor.php">
                        <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold font-size-sm">Subject Taught</label>
                            <select name="subject_id" class="form-select font-size-sm rounded-3" onchange="this.form.submit()">
                                <option value="0">All Subjects</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $subjectFilter === $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold font-size-sm">Level of Teaching</label>
                            <select name="level" class="form-select font-size-sm rounded-3" onchange="this.form.submit()">
                                <option value="">All Levels</option>
                                <option value="Beginner" <?php echo $levelFilter === 'Beginner' ? 'selected' : ''; ?>>Beginner / Basic</option>
                                <option value="Intermediate" <?php echo $levelFilter === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo $levelFilter === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold font-size-sm">Max Hourly Rate</label>
                            <select name="max_price" class="form-select font-size-sm rounded-3" onchange="this.form.submit()">
                                <option value="0">Any Price</option>
                                <option value="250" <?php echo $maxPrice == 250 ? 'selected' : ''; ?>>Up to ₱250/hr</option>
                                <option value="300" <?php echo $maxPrice == 300 ? 'selected' : ''; ?>>Up to ₱300/hr</option>
                                <option value="400" <?php echo $maxPrice == 400 ? 'selected' : ''; ?>>Up to ₱400/hr</option>
                            </select>
                        </div>
                    </form>

                    <div class="p-3 bg-primary-light rounded-3 text-center">
                        <i class="fa-solid fa-wand-magic-sparkles text-primary fs-3 mb-2"></i>
                        <h6 class="fw-bold text-dark">Need AI Recommendation?</h6>
                        <p class="text-muted font-size-xs mb-3">Let our algorithm match you based on your learning style.</p>
                        <a href="ai-matching.php" class="btn btn-primary-custom btn-sm w-100 py-2">Try AI Matching</a>
                    </div>
                </div>
            </div>

            <!-- Tutor Cards List -->
            <div class="col-lg-9">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-dark mb-0">All Tutors <span class="badge bg-primary rounded-pill font-size-xs ms-1"><?php echo count($tutors); ?></span></h5>
                </div>

                <?php if (count($tutors) > 0): ?>
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($tutors as $tutor):
                            // Fetch subjects list for tutor
                            $stmtS = $pdo->prepare("SELECT s.name FROM subjects s JOIN tutor_subjects ts ON s.id = ts.subject_id WHERE ts.tutor_id = ?");
                            $stmtS->execute([$tutor['id']]);
                            $tutorSubNames = implode(', ', $stmtS->fetchAll(PDO::FETCH_COLUMN));
                        ?>
                            <div class="card-custom p-4">
                                <div class="row align-items-center g-4">
                                    <div class="col-md-3 text-center">
                                        <img src="<?php echo htmlspecialchars($tutor['profile_photo']); ?>"
                                             class="rounded-circle object-fit-cover shadow-sm mb-2" width="110" height="110" alt="Tutor">
                                        <div class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-size-xs px-3 py-1">
                                            <i class="fa-solid fa-shield-halved me-1"></i> Verified Tutor
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h4 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($tutor['name']); ?></h4>
                                            <i class="fa-solid fa-circle-check text-primary fs-5" title="Verified"></i>
                                        </div>
                                        <p class="text-primary fw-semibold font-size-sm mb-1"><?php echo htmlspecialchars($tutor['title']); ?></p>
                                        <div class="text-muted font-size-xs mb-2">
                                            <i class="fa-solid fa-book-open text-muted me-1"></i> Teaches: <strong><?php echo htmlspecialchars($tutorSubNames ?: 'General Subjects'); ?></strong>
                                        </div>
                                        <p class="text-muted font-size-sm line-clamp-2 mb-3">
                                            <?php echo htmlspecialchars($tutor['bio']); ?>
                                        </p>
                                        <div class="d-flex align-items-center gap-3 font-size-xs text-muted">
                                            <span><i class="fa-solid fa-briefcase me-1"></i> <?php echo $tutor['experience']; ?>+ years exp</span>
                                            <span><i class="fa-solid fa-graduation-cap me-1"></i> <?php echo $tutor['lessons_conducted']; ?> lessons</span>
                                            <span><i class="fa-regular fa-clock me-1"></i> <?php echo htmlspecialchars($tutor['response_time']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-md-end border-start-md ps-md-4">
                                        <div class="mb-2">
                                            <span class="text-warning font-weight-bold fs-5"><i class="fa-solid fa-star"></i> <?php echo number_format($tutor['rating'], 1); ?></span>
                                            <span class="text-muted font-size-xs d-block"><?php echo $tutor['total_reviews']; ?> student reviews</span>
                                        </div>
                                        <div class="mb-3">
                                            <span class="fw-extrabold fs-3 text-dark">₱<?php echo number_format($tutor['hourly_rate'], 2); ?></span>
                                            <span class="text-muted font-size-xs">/ hour</span>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <a href="booking.php?tutor_id=<?php echo $tutor['id']; ?>" class="btn btn-primary-custom py-2">Book Lesson</a>
                                            <a href="tutor-profile.php?id=<?php echo $tutor['id']; ?>" class="btn btn-outline-custom py-2">View Profile</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card-static p-5 text-center my-4">
                        <i class="fa-solid fa-user-slash text-muted fs-1 mb-3"></i>
                        <h5 class="fw-bold">No tutors found</h5>
                        <p class="text-muted mb-0">Try adjusting your filters or search keywords.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
