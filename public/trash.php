<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$currentUser = AuthMiddleware::requireLogin();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        $project = Project::findById($projectId, true);
        if ($project && $project->user_id === $currentUser['id']) {
            if ($action === 'restore') {
                $project->restore();
                ActivityLog::log($currentUser['id'], "Restored project from Trash: {$project->project_name}", $project->id);
                $message = "Project '{$project->project_name}' restored successfully.";
            } elseif ($action === 'permanent_delete') {
                $project->hardDelete();
                ActivityLog::log($currentUser['id'], "Permanently deleted project: {$project->project_name}");
                $message = "Project permanently removed.";
            }
        }
    }
}

$trashProjects = Project::getTrashByUser($currentUser['id']);
$csrfToken = AuthMiddleware::generateCsrfToken();
renderHeader('Trash', 'trash');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1"><i class="bi bi-trash3 text-danger me-2"></i>Trash / Deleted Projects</h2>
        <p class="text-muted mb-0">Projects here will be automatically purged after 30 days.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card card-custom p-4 mb-4">
    <?php if (empty($trashProjects)): ?>
        <div class="text-center py-5">
            <i class="bi bi-trash display-1 text-muted"></i>
            <h5 class="mt-3 text-muted">Trash is empty</h5>
            <p class="text-muted">No soft-deleted projects found.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted">
                        <th>Project Name</th>
                        <th>Language</th>
                        <th>Size</th>
                        <th>Deleted Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trashProjects as $tp): ?>
                        <tr>
                            <td class="fw-bold"><i class="bi bi-folder-x me-2 text-danger"></i><?= htmlspecialchars($tp['project_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($tp['project_type']) ?></span></td>
                            <td><?= round(($tp['total_size'] ?? 0) / (1024 * 1024), 2) ?> MB</td>
                            <td><?= date('M d, Y H:i', strtotime($tp['deleted_at'])) ?></td>
                            <td class="text-end">
                                <form method="POST" action="trash.php" class="d-inline-block">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="project_id" value="<?= $tp['id'] ?>">
                                    <input type="hidden" name="action" value="restore">
                                    <button type="submit" class="btn btn-sm btn-outline-success me-1">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                    </button>
                                </form>
                                <form method="POST" action="trash.php" class="d-inline-block" onsubmit="return confirm('Permanently delete this project? This action cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="project_id" value="<?= $tp['id'] ?>">
                                    <input type="hidden" name="action" value="permanent_delete">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-x-circle me-1"></i> Delete Forever
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
