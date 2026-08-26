<?php
// includes/navbar.php
$user = getCurrentUser();
$baseUrl = $baseUrl ?? '';
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom py-3 px-4 sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2 font-weight-bold" href="<?php echo $baseUrl; ?>/index.php">
            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="fa-solid fa-graduation-cap fs-5"></i>
            </div>
            <span class="fw-bold fs-4 text-dark tracking-tight">Tutor<span class="text-primary">Link</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 font-weight-semibold">
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $baseUrl; ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $baseUrl; ?>/student/find-tutor.php">Find Tutors</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $baseUrl; ?>/student/ai-matching.php">
                        <span class="badge bg-primary-subtle text-primary me-1"><i class="fa-solid fa-wand-magic-sparkles"></i> AI</span>
                        Matching
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $baseUrl; ?>/index.php#how-it-works">How It Works</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if ($user): ?>
                    <a href="<?php echo $baseUrl . '/' . $user['role']; ?>/dashboard.php" class="btn btn-primary-custom">
                        <i class="fa-solid fa-gauge me-1"></i> Dashboard
                    </a>
                    <a href="<?php echo $baseUrl; ?>/logout.php" class="btn btn-outline-custom">Logout</a>
                <?php else: ?>
                    <a href="<?php echo $baseUrl; ?>/login.php" class="btn btn-outline-custom">Log In</a>
                    <a href="<?php echo $baseUrl; ?>/register.php" class="btn btn-primary-custom">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
