<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/SystemSetting.php';
require_once __DIR__ . '/../app/Views/Layout.php';

AuthMiddleware::initSession();
$user = AuthMiddleware::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudVault — Store & Restore Projects Securely</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-navy text-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary fs-3" href="index.php">
                <i class="bi bi-cloud-arrow-up-fill me-2"></i>CloudVault
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#languages">Supported Stacks</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <?php if ($user): ?>
                        <a href="dashboard.php" class="btn btn-primary fw-bold px-4">Go to Dashboard <i class="bi bi-arrow-right ms-1"></i></a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light px-4">Login</a>
                        <a href="register.php" class="btn btn-primary fw-bold px-4">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-5 text-center my-5">
        <div class="container py-5">
            <h1 class="display-3 fw-bold text-light mb-4">
                Store Your Projects.<br><span class="text-primary">Transfer Without Losing What Matters.</span>
            </h1>
            <p class="lead text-muted mx-auto mb-5" style="max-width: 750px;">
                CloudVault helps developers securely back up, organize, transfer, and restore complete programming projects while preserving essential nested folders, database backups, dependency manifests, and checksum integrity.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="register.php" class="btn btn-primary btn-lg px-5 py-3 fw-bold shadow">Start Backing Up Free</a>
                <a href="#features" class="btn btn-outline-secondary btn-lg px-5 py-3">Explore Features</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-dark border-top border-bottom border-secondary">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold fs-1">Built Specifically For Developers</h2>
                <p class="text-muted">Unlike ordinary cloud drives, CloudVault preserves your exact workspace ecosystem.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-custom h-100 p-4">
                        <i class="bi bi-folder-symlink fs-1 text-primary mb-3"></i>
                        <h4 class="fw-bold">Exact Directory Preservation</h4>
                        <p class="text-muted">Upload entire folder structures, nested assets, and configuration templates without flattening or file name mangling.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-custom h-100 p-4">
                        <i class="bi bi-cpu fs-1 text-info mb-3"></i>
                        <h4 class="fw-bold">Smart Dependency Detection</h4>
                        <p class="text-muted">Automatically inspects Composer, Pip, NPM, Maven, and CMake manifests to detect required versions and packages.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-custom h-100 p-4">
                        <i class="bi bi-database-check fs-1 text-success mb-3"></i>
                        <h4 class="fw-bold">Database Backup Pairings</h4>
                        <p class="text-muted">Attach MySQL database dumps (.sql) directly to project releases, ensuring your schema transfers with your code.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 text-center text-muted border-top border-secondary mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> CloudVault. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
