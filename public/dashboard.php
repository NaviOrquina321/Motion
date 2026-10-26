<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/ProjectVersion.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$currentUser = AuthMiddleware::requireLogin();
$userObj = User::findById($currentUser['id']);

$totalProjects = Project::countByUser($userObj->id);
$usedStorage = $userObj->getUsedStorage();
$usedGb = round($usedStorage / (1024 * 1024 * 1024), 2);
$quotaGb = round($userObj->storage_quota / (1024 * 1024 * 1024), 1);
$availableGb = max(0, round(($userObj->storage_quota - $usedStorage) / (1024 * 1024 * 1024), 2));

$recentProjects = Project::getByUser($userObj->id, ['sort' => 'created_at_desc']);
$recentProjects = array_slice($recentProjects, 0, 5);

renderHeader('Dashboard', 'dashboard');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1">Welcome back, <?= htmlspecialchars($userObj->full_name) ?> 👋</h2>
        <p class="text-muted mb-0">Here is an overview of your project storage and recent activity.</p>
    </div>
    <a href="upload.php" class="btn btn-primary fw-bold px-3 py-2">
        <i class="bi bi-cloud-upload me-1"></i> Upload New Project
    </a>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-bold">TOTAL PROJECTS</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= $totalProjects ?></h3>
                </div>
                <div class="bg-primary bg-opacity-20 p-3 rounded-3 text-primary">
                    <i class="bi bi-folder2-open fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-bold">STORAGE USED</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= $usedGb ?> <span class="fs-6 text-muted">GB</span></h3>
                </div>
                <div class="bg-info bg-opacity-20 p-3 rounded-3 text-info">
                    <i class="bi bi-hdd-network fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-bold">AVAILABLE STORAGE</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= $availableGb ?> <span class="fs-6 text-muted">GB</span></h3>
                </div>
                <div class="bg-success bg-opacity-20 p-3 rounded-3 text-success">
                    <i class="bi bi-pie-chart fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-bold">MAX QUOTA</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= $quotaGb ?> <span class="fs-6 text-muted">GB</span></h3>
                </div>
                <div class="bg-warning bg-opacity-20 p-3 rounded-3 text-warning">
                    <i class="bi bi-shield-check fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Projects Table -->
<div class="card card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-light mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Projects</h4>
        <a href="projects.php" class="btn btn-sm btn-outline-primary">View All Projects</a>
    </div>

    <?php if (empty($recentProjects)): ?>
        <div class="text-center py-5">
            <i class="bi bi-folder-plus display-1 text-muted"></i>
            <h5 class="mt-3 text-muted">No projects uploaded yet</h5>
            <p class="text-muted">Start by uploading your first programming project archive or folder.</p>
            <a href="upload.php" class="btn btn-primary mt-2">Upload First Project</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted">
                        <th>Project Name</th>
                        <th>Language</th>
                        <th>Files</th>
                        <th>Size</th>
                        <th>Backup Mode</th>
                        <th>Uploaded Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentProjects as $p): ?>
                        <tr>
                            <td>
                                <a href="project-details.php?id=<?= $p['id'] ?>" class="fw-bold text-light text-decoration-none">
                                    <i class="bi bi-file-code me-2 text-primary"></i><?= htmlspecialchars($p['project_name']) ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($p['project_type']) ?></span></td>
                            <td><?= number_format($p['file_count'] ?? 0) ?></td>
                            <td><?= round(($p['total_size'] ?? 0) / (1024 * 1024), 2) ?> MB</td>
                            <td>
                                <span class="badge <?= ($p['backup_mode'] ?? '') === 'smart' ? 'bg-success' : 'bg-primary' ?>">
                                    <?= strtoupper($p['backup_mode'] ?? 'manual') ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="project-details.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="download.php?project_id=<?= $p['id'] ?>&type=setup_assisted" class="btn btn-sm btn-outline-success me-1" title="Download Setup Assisted">
                                    <i class="bi bi-download"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
