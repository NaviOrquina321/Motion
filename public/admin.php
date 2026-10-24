<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';
require_once __DIR__ . '/../app/Models/SystemSetting.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$adminUser = AuthMiddleware::requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_quota') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $newQuotaGb = (float)($_POST['quota_gb'] ?? 10);
            $targetUser = User::findById($targetUserId);
            if ($targetUser) {
                $targetUser->storage_quota = (int)($newQuotaGb * 1024 * 1024 * 1024);
                $targetUser->save();
                ActivityLog::log($adminUser['id'], "Updated storage quota for user {$targetUser->username} to {$newQuotaGb} GB");
                $message = "Quota updated for {$targetUser->username}.";
            }
        } elseif ($action === 'update_settings') {
            $maxUploadMb = (int)($_POST['max_upload_size_mb'] ?? 500);
            SystemSetting::set('max_upload_size', (string)($maxUploadMb * 1024 * 1024));
            ActivityLog::log($adminUser['id'], "Updated system max upload size to {$maxUploadMb} MB");
            $message = "System settings updated.";
        }
    }
}

$allUsers = User::getAll();
$totalSystemProjects = Project::countAllSystem();
$totalUsersCount = User::countAll();
$recentLogs = ActivityLog::getRecent(15);
$maxUploadMb = round((int)SystemSetting::get('max_upload_size', 536870912) / (1024 * 1024));

$csrfToken = AuthMiddleware::generateCsrfToken();
renderHeader('Admin Dashboard', 'admin');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1"><i class="bi bi-shield-lock text-warning me-2"></i>Administrator Control Panel</h2>
        <p class="text-muted mb-0">System health monitoring, user storage management, and activity logs.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- System Overview Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-bold">REGISTERED USERS</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= $totalUsersCount ?></h3>
                </div>
                <div class="bg-primary bg-opacity-20 p-3 rounded-3 text-primary">
                    <i class="bi bi-people fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-bold">TOTAL SYSTEM PROJECTS</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= $totalSystemProjects ?></h3>
                </div>
                <div class="bg-info bg-opacity-20 p-3 rounded-3 text-info">
                    <i class="bi bi-diagram-3 fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-bold">MAX UPLOAD LIMIT</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= $maxUploadMb ?> <span class="fs-6 text-muted">MB</span></h3>
                </div>
                <div class="bg-warning bg-opacity-20 p-3 rounded-3 text-warning">
                    <i class="bi bi-sliders fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- User Storage Quotas Table -->
    <div class="col-lg-7">
        <div class="card card-custom p-4 h-100">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-person-gear me-2 text-primary"></i>User Accounts & Storage Quotas</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-muted">
                            <th>User</th>
                            <th>Role</th>
                            <th>Used Storage</th>
                            <th>Quota (GB)</th>
                            <th class="text-end">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                            <?php
                                $used = round($u->getUsedStorage() / (1024 * 1024 * 1024), 2);
                                $qGb = round($u->storage_quota / (1024 * 1024 * 1024), 1);
                            ?>
                            <tr>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($u->full_name) ?></strong>
                                    <small class="text-muted">@<?= htmlspecialchars($u->username) ?></small>
                                </td>
                                <td><span class="badge <?= $u->role === 'admin' ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= strtoupper($u->role) ?></span></td>
                                <td><?= $used ?> GB</td>
                                <td>
                                    <form method="POST" action="admin.php" class="d-flex gap-1 align-items-center">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="update_quota">
                                        <input type="hidden" name="user_id" value="<?= $u->id ?>">
                                        <input type="number" name="quota_gb" class="form-control form-control-sm text-end" style="width: 70px;" value="<?= $qGb ?>" step="1" min="1">
                                </td>
                                <td class="text-end">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- System Settings Form -->
    <div class="col-lg-5">
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-gear-wide-connected me-2 text-info"></i>Global System Configuration</h5>
            <form method="POST" action="admin.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="update_settings">

                <div class="mb-3">
                    <label class="form-label fw-bold">Max Upload File Size (MB)</label>
                    <input type="number" name="max_upload_size_mb" class="form-control" value="<?= $maxUploadMb ?>" required min="10">
                </div>

                <button type="submit" class="btn btn-info fw-bold py-2 px-4">
                    Save System Config <i class="bi bi-save ms-1"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- System Audit Logs -->
<div class="card card-custom p-4 mb-4">
    <h5 class="fw-bold text-light mb-3"><i class="bi bi-journal-text me-2 text-warning"></i>Recent System Activity Audit Log</h5>
    <div class="table-responsive">
        <table class="table table-dark table-sm align-middle mb-0">
            <thead>
                <tr class="text-muted">
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Associated Project</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td><small class="text-muted"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></small></td>
                        <td class="fw-bold"><?= htmlspecialchars($log['username'] ?: 'System') ?></td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><small class="text-primary"><?= htmlspecialchars($log['project_name'] ?: 'N/A') ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>
