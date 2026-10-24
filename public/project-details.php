<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/ProjectVersion.php';
require_once __DIR__ . '/../app/Services/IntegrityChecker.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$currentUser = AuthMiddleware::requireLogin();
$projectId = (int)($_GET['id'] ?? 0);

$project = Project::findById($projectId);
if (!$project || $project->user_id !== $currentUser['id']) {
    header('Location: projects.php');
    exit;
}

$db = Database::getConnection();

// Handle Soft Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $project->softDelete();
        ActivityLog::log($currentUser['id'], "Moved project to Trash: {$project->project_name}", $project->id);
        header('Location: trash.php?deleted=1');
        exit;
    }
}

// Fetch Current Version Details
$currentVersion = ProjectVersion::findById($project->current_version_id ?? 0);
$allVersions = ProjectVersion::getByProject($project->id);

// Integrity Verification
$integrityResult = $currentVersion ? IntegrityChecker::verifyProjectVersion($currentVersion->id) : null;

// Fetch Requirements
$stmtReq = $db->prepare("SELECT * FROM project_requirements WHERE project_version_id = ?");
$stmtReq->execute([$currentVersion->id ?? 0]);
$requirements = $stmtReq->fetchAll();

// Fetch Files
$stmtFiles = $db->prepare("SELECT relative_path, file_size, checksum_sha256 FROM project_files WHERE project_version_id = ? ORDER BY relative_path ASC LIMIT 100");
$stmtFiles->execute([$currentVersion->id ?? 0]);
$files = $stmtFiles->fetchAll();

// Fetch Database Backups
$stmtDb = $db->prepare("SELECT * FROM database_backups WHERE project_version_id = ?");
$stmtDb->execute([$currentVersion->id ?? 0]);
$dbBackups = $stmtDb->fetchAll();

$csrfToken = AuthMiddleware::generateCsrfToken();
renderHeader($project->project_name, 'projects');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1">
            <i class="bi bi-file-code text-primary me-2"></i><?= htmlspecialchars($project->project_name) ?>
        </h2>
        <p class="text-muted mb-0"><?= htmlspecialchars($project->description ?: 'No description provided.') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="download.php?project_id=<?= $project->id ?>&type=standard" class="btn btn-outline-primary fw-bold">
            <i class="bi bi-file-earmark-zip me-1"></i> Standard ZIP
        </a>
        <a href="download.php?project_id=<?= $project->id ?>&type=setup_assisted" class="btn btn-success fw-bold">
            <i class="bi bi-download me-1"></i> Setup Assistant Download
        </a>
        <form method="POST" action="project-details.php?id=<?= $project->id ?>" onsubmit="return confirm('Are you sure you want to move this project to Trash?');">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
    </div>
</div>

<!-- Project Health Status Alert -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom p-3 border-start border-4 <?= ($integrityResult['status'] ?? '') === 'verified' ? 'border-success' : 'border-danger' ?>">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1">
                        <i class="bi bi-shield-check me-2 <?= ($integrityResult['status'] ?? '') === 'verified' ? 'text-success' : 'text-danger' ?>"></i>
                        Project Health: <?= ($integrityResult['status'] ?? '') === 'verified' ? 'SHA-256 Verified & Secure' : 'Integrity Issue Detected' ?>
                    </h5>
                    <small class="text-muted">
                        Verified <?= $integrityResult['passed_count'] ?? 0 ?> / <?= $integrityResult['total_files'] ?? 0 ?> files against original checksum manifests.
                    </small>
                </div>
                <span class="badge <?= ($integrityResult['status'] ?? '') === 'verified' ? 'bg-success' : 'bg-danger' ?> fs-6">
                    <?= strtoupper($integrityResult['status'] ?? 'UNKNOWN') ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Requirements & Files -->
    <div class="col-lg-8">
        <!-- Requirements Report -->
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-cpu text-info me-2"></i>Analysis & Requirement Report</h5>
            <?php if (empty($requirements)): ?>
                <p class="text-muted mb-0">No explicit requirements detected.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Type</th>
                                <th>Requirement</th>
                                <th>Constraint</th>
                                <th>Source</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requirements as $r): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['requirement_type']) ?></span></td>
                                    <td class="fw-bold"><?= htmlspecialchars($r['requirement_name']) ?></td>
                                    <td><code><?= htmlspecialchars($r['version_constraint'] ?: 'N/A') ?></code></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($r['detection_source']) ?></small></td>
                                    <td>
                                        <span class="badge <?= $r['confidence_status'] === 'verified' ? 'bg-success' : 'bg-info' ?>">
                                            <?= strtoupper($r['confidence_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Files Manifest -->
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-folder-symlink text-primary me-2"></i>Project File Structure (Showing up to 100)</h5>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted">
                            <th>Relative Path</th>
                            <th>Size</th>
                            <th>SHA-256 Checksum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $f): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($f['relative_path']) ?></code></td>
                                <td><?= number_format($f['file_size']) ?> B</td>
                                <td><small class="text-muted font-monospace"><?= substr($f['checksum_sha256'], 0, 16) ?>...</small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Versions & Meta -->
    <div class="col-lg-4">
        <!-- Database Backups Attached -->
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-database text-success me-2"></i>Attached Database Backups</h5>
            <?php if (empty($dbBackups)): ?>
                <p class="text-muted small mb-0"><i class="bi bi-exclamation-circle text-warning me-1"></i> No SQL database backup attached to this version.</p>
            <?php else: ?>
                <?php foreach ($dbBackups as $dbB): ?>
                    <div class="p-2 bg-dark rounded border border-secondary mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block text-truncate" style="max-width: 180px;"><?= htmlspecialchars($dbB['original_filename']) ?></strong>
                            <small class="text-muted"><?= round($dbB['file_size'] / 1024, 1) ?> KB</small>
                        </div>
                        <span class="badge bg-success">SQL</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Version History -->
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Version History</h5>
            <ul class="list-group list-group-flush bg-transparent">
                <?php foreach ($allVersions as $v): ?>
                    <li class="list-group-item bg-transparent text-light d-flex justify-content-between align-items-center border-secondary px-0">
                        <div>
                            <strong>Version <?= $v->version_number ?></strong>
                            <br><small class="text-muted"><?= date('M d, Y', strtotime($v->uploaded_at)) ?></small>
                        </div>
                        <span class="badge bg-secondary"><?= round($v->total_size / (1024 * 1024), 2) ?> MB</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
