<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/ProjectVersion.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$currentUser = AuthMiddleware::requireLogin();
$projects = Project::getByUser($currentUser['id']);

renderHeader('Download Center', 'downloads');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1"><i class="bi bi-download text-success me-2"></i>Download Center</h2>
        <p class="text-muted mb-0">Select and download original project archives or Setup-Assisted restoration packages.</p>
    </div>
</div>

<div class="card card-custom p-4 mb-4">
    <?php if (empty($projects)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cloud-download display-1 text-muted"></i>
            <h5 class="mt-3 text-muted">No downloadable projects</h5>
            <p class="text-muted">Upload a project to access download options.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted">
                        <th>Project Name</th>
                        <th>Language</th>
                        <th>File Count</th>
                        <th>Size</th>
                        <th>Download Options</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $p): ?>
                        <tr>
                            <td>
                                <a href="project-details.php?id=<?= $p['id'] ?>" class="fw-bold text-light text-decoration-none">
                                    <i class="bi bi-folder-fill me-2 text-primary"></i><?= htmlspecialchars($p['project_name']) ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($p['project_type']) ?></span></td>
                            <td><?= number_format($p['file_count'] ?? 0) ?></td>
                            <td><?= round(($p['total_size'] ?? 0) / (1024 * 1024), 2) ?> MB</td>
                            <td>
                                <a href="download.php?project_id=<?= $p['id'] ?>&type=standard" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-file-earmark-zip me-1"></i> Standard ZIP
                                </a>
                                <a href="download.php?project_id=<?= $p['id'] ?>&type=setup_assisted" class="btn btn-sm btn-success">
                                    <i class="bi bi-magic me-1"></i> Setup Assistant Package
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
