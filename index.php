<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';
$pdo = getDBConnection();

// Fetch featured tutors
$stmtT = $pdo->query("
    SELECT t.*, u.name, u.profile_photo, s.name as primary_subject
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN tutor_subjects ts ON t.id = ts.tutor_id
    LEFT JOIN subjects s ON ts.subject_id = s.id
    GROUP BY t.id
    ORDER BY t.rating DESC
    LIMIT 3
");
$featuredTutors = $stmtT->fetchAll();

$pageTitle = "Find the Right Tutor. Learn Better.";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="py-5 bg-gradient-light position-relative overflow-hidden">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="pill-badge pill-badge-primary mb-3">
                    <i class="fa-solid fa-sparkles me-1"></i> AI-Powered Matching System
                </span>
                <h1 class="display-4 fw-extrabold text-dark tracking-tight mb-3">
                    Find the Right Tutor.<br>
                    <span class="text-primary">Learn Better.</span>
                </h1>
                <p class="lead text-muted mb-4">
                    Connect with qualified, verified tutors tailored to your exact learning needs, budget, and preferred schedule. Powered by intelligent AI matching.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="student/ai-matching.php" class="btn btn-primary-custom btn-lg py-3 px-4">
                        <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Find My Tutor (AI Match)
                    </a>
                    <a href="register.php" class="btn btn-outline-custom btn-lg py-3 px-4">
                        Become a Tutor
                    </a>
                </div>
                <div class="d-flex align-items-center gap-4 text-muted font-size-sm">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-circle-check text-success fs-5"></i> Verified Educators
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-circle-check text-success fs-5"></i> GCash Payment Ready
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="position-relative">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80&w=800"
                         class="img-fluid rounded-4 shadow-lg border" alt="Student & Tutor Learning">

                    <!-- Floating Widget 1 -->
                    <div class="card-static position-absolute bottom-0 start-0 m-3 p-3 shadow-lg rounded-4 d-flex align-items-center gap-3 border" style="max-width: 260px;">
                        <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fa-solid fa-bullseye fs-4"></i>
                        </div>
                        <div>
                            <span class="fw-extrabold fs-5 d-block text-dark">95% Match</span>
                            <span class="text-muted font-size-xs">AI Learning Preference</span>
                        </div>
                    </div>

                    <!-- Floating Widget 2 -->
                    <div class="card-static position-absolute top-0 end-0 m-3 p-3 shadow-lg rounded-4 d-flex align-items-center gap-3 border" style="max-width: 240px;">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=150" class="rounded-circle object-fit-cover" width="40" height="40" alt="Tutor">
                        <div>
                            <span class="fw-bold d-block text-dark font-size-sm">Maria Santos <i class="fa-solid fa-circle-check text-primary"></i></span>
                            <span class="text-warning font-size-xs"><i class="fa-solid fa-star"></i> 4.9 (127 reviews)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Bar -->
<section class="py-4 bg-white border-y">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <h2 class="fw-extrabold text-primary mb-1">500+</h2>
                <span class="text-muted fw-semibold">Active Students</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="fw-extrabold text-primary mb-1">100+</h2>
                <span class="text-muted fw-semibold">Verified Tutors</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="fw-extrabold text-primary mb-1">1,000+</h2>
                <span class="text-muted fw-semibold">Sessions Conducted</span>
            </div>
            <div class="col-6 col-md-3">
                <h2 class="fw-extrabold text-primary mb-1">95%</h2>
                <span class="text-muted fw-semibold">Satisfaction Rate</span>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="py-5">
    <div class="container py-4">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="pill-badge pill-badge-primary mb-2">Step-by-Step Flow</span>
            <h2 class="fw-bold fs-1 text-dark">How TutorLink Works</h2>
            <p class="text-muted">Simple 4-step process from AI recommendations to personalized progress tracking.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card-custom p-4 h-100 text-center">
                    <div class="bg-primary-subtle text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <span class="fw-extrabold fs-4">01</span>
                    </div>
                    <h5 class="fw-bold">Create Profile</h5>
                    <p class="text-muted font-size-sm">Tell us your subject, education level, budget, and preferred learning style.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom p-4 h-100 text-center">
                    <div class="bg-primary-subtle text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <span class="fw-extrabold fs-4">02</span>
                    </div>
                    <h5 class="fw-bold">Get AI Matched</h5>
                    <p class="text-muted font-size-sm">Our intelligent scoring engine ranks tutors with match percentages best fitted for you.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom p-4 h-100 text-center">
                    <div class="bg-primary-subtle text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <span class="fw-extrabold fs-4">03</span>
                    </div>
                    <h5 class="fw-bold">Book & Pay GCash</h5>
                    <p class="text-muted font-size-sm">Choose an available time slot and complete payment instantly via GCash integration.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom p-4 h-100 text-center">
                    <div class="bg-primary-subtle text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <span class="fw-extrabold fs-4">04</span>
                    </div>
                    <h5 class="fw-bold">Start Learning</h5>
                    <p class="text-muted font-size-sm">Attend your tutoring session, receive notifications, and track your performance.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Tutors Section -->
<section class="py-5 bg-white border-top">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="pill-badge pill-badge-primary mb-2">Verified Educators</span>
                <h2 class="fw-bold fs-1 text-dark mb-0">Featured Top Tutors</h2>
            </div>
            <a href="student/find-tutor.php" class="btn btn-outline-custom">View All Tutors <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredTutors as $tutor): ?>
                <div class="col-md-4">
                    <div class="card-custom p-4 h-100 d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?php echo htmlspecialchars($tutor['profile_photo']); ?>"
                                 class="rounded-circle object-fit-cover" width="64" height="64" alt="Tutor">
                            <div>
                                <h5 class="fw-bold mb-1 d-flex align-items-center gap-1">
                                    <?php echo htmlspecialchars($tutor['name']); ?>
                                    <i class="fa-solid fa-circle-check text-primary font-size-sm" title="Verified Tutor"></i>
                                </h5>
                                <span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($tutor['title']); ?></span>
                            </div>
                        </div>
                        <p class="text-muted font-size-sm line-clamp-2 mb-3">
                            <?php echo htmlspecialchars($tutor['bio']); ?>
                        </p>
                        <div class="d-flex align-items-center justify-content-between border-top pt-3 mt-auto">
                            <div>
                                <span class="fw-bold text-dark fs-5">₱<?php echo number_format($tutor['hourly_rate'], 2); ?></span>
                                <span class="text-muted font-size-xs">/ hour</span>
                            </div>
                            <div class="text-warning font-weight-bold font-size-sm">
                                <i class="fa-solid fa-star"></i> <?php echo number_format($tutor['rating'], 1); ?>
                                <span class="text-muted font-weight-normal">(<?php echo $tutor['total_reviews']; ?>)</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="student/tutor-profile.php?id=<?php echo $tutor['id']; ?>" class="btn btn-outline-custom w-100">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
