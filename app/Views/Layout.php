<?php
function renderHeader(string $title, string $activeNav = ''): void {
    $user = AuthMiddleware::getCurrentUser();
    $usedStorage = 0;
    $quota = 10737418240;
    if ($user) {
        $uObj = User::findById($user['id']);
        if ($uObj) {
            $usedStorage = $uObj->getUsedStorage();
            $quota = $uObj->storage_quota;
        }
    }
    $usedPercent = $quota > 0 ? min(100, round(($usedStorage / $quota) * 100, 1)) : 0;
    $usedGb = round($usedStorage / (1024 * 1024 * 1024), 2);
    $quotaGb = round($quota / (1024 * 1024 * 1024), 1);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - CloudVault</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-navy">

<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;"></div>

<div class="container-fluid">
    <div class="row">
        <?php if ($user): ?>
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar p-3 d-flex flex-column">
            <a href="dashboard.php" class="d-flex align-items-center mb-4 text-decoration-none text-light px-2">
                <i class="bi bi-cloud-arrow-up-fill fs-3 text-gradient me-2"></i>
                <span class="fs-4 fw-bold">CloudVault</span>
            </a>

            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mb-1">
                    <a href="dashboard.php" class="nav-link-custom <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="upload.php" class="nav-link-custom <?= $activeNav === 'upload' ? 'active' : '' ?>">
                        <i class="bi bi-cloud-upload"></i> Upload Project
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="projects.php" class="nav-link-custom <?= $activeNav === 'projects' ? 'active' : '' ?>">
                        <i class="bi bi-folder2-open"></i> My Projects
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="showcase.php" class="nav-link-custom text-info <?= $activeNav === 'showcase' ? 'active' : '' ?>">
                        <i class="bi bi-film"></i> AI Motion Showcase
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="downloads.php" class="nav-link-custom <?= $activeNav === 'downloads' ? 'active' : '' ?>">
                        <i class="bi bi-download"></i> Download Center
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="trash.php" class="nav-link-custom <?= $activeNav === 'trash' ? 'active' : '' ?>">
                        <i class="bi bi-trash3"></i> Trash
                    </a>
                </li>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <hr class="text-secondary my-2">
                <li class="nav-item mb-1">
                    <a href="admin.php" class="nav-link-custom text-warning <?= $activeNav === 'admin' ? 'active' : '' ?>">
                        <i class="bi bi-shield-lock"></i> Admin Panel
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Storage Quota Widget -->
            <div class="p-3 card-custom mt-4 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted fw-bold">STORAGE USED </small>
                    <small class="text-primary fw-bold"><?= $usedPercent ?>%</small>
                </div>
                <div class="progress progress-thin mb-2 bg-dark">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $usedPercent ?>%"></div>
                </div>
                <small class="text-muted"><?= $usedGb ?> GB of <?= $quotaGb ?> GB</small>
            </div>

            <!-- User Profile Dropdown -->
            <div class="dropdown border-top border-secondary pt-3">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle px-2" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4 text-primary me-2"></i>
                    <strong class="text-truncate" style="max-width: 120px;"><?= htmlspecialchars($user['full_name']) ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><button id="themeToggleBtn" class="dropdown-item" type="button"><i class="bi bi-moon-stars me-2"></i>Toggle Theme</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
                </ul>
            </div>
        </div>
        <div class="col-md-9 col-lg-10 p-4">
        <?php else: ?>
        <div class="col-12 p-0">
        <?php endif; ?>
<?php
}

function renderFooter(): void {
?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
<?php
}
