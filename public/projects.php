<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/ProjectVersion.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$currentUser = AuthMiddleware::requireLogin();

// Filters & Sorting
$search = trim($_GET['search'] ?? '');
$language = trim($_GET['language'] ?? '');
$sort = trim($_GET['sort'] ?? 'created_at_desc');

$projects = Project::getByUser($currentUser['id'], [
    'search' => $search,
    'language' => $language,
    'sort' => $sort
]);

renderHeader('My Projects', 'projects');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1"><i class="bi bi-folder2-open text-primary me-2"></i>My Projects</h2>
        <p class="text-muted mb-0">Manage and explore all your backed-up project repositories.</p>
    </div>
    <a href="upload.php" class="btn btn-primary fw-bold">
        <i class="bi bi-plus-lg me-1"></i> Upload New
    </a>
</div>

<!-- Search & Filter Controls -->
<div class="card card-custom p-3 mb-4">
    <form method="GET" action="projects.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search projects..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="language" class="form-select">
                <option value="">All Languages / Stacks</option>
                <option value="PHP" <?= $language === 'PHP' ? 'selected' : '' ?>>PHP</option>
                <option value="Python" <?= $language === 'Python' ? 'selected' : '' ?>>Python</option>
                <option value="JavaScript / Node.js" <?= $language === 'JavaScript / Node.js' ? 'selected' : '' ?>>JavaScript / Node.js</option>
                <option value="Java" <?= $language === 'Java' ? 'selected' : '' ?>>Java</option>
                <option value="C++" <?= $language === 'C++' ? 'selected' : '' ?>>C++</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="sort" class="form-select">
                <option value="created_at_desc" <?= $sort === 'created_at_desc' ? 'selected' : '' ?>>Newest First</option>
                <option value="created_at_asc" <?= $sort === 'created_at_asc' ? 'selected' : '' ?>>Oldest First</option>
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name A-Z</option>
                <option value="size_desc" <?= $sort === 'size_desc' ? 'selected' : '' ?>>Largest Size</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter"></i></button>
        </div>
    </form>
</div>

<!-- Projects Grid -->
<?php if (empty($projects)): ?>
    <div class="card card-custom p-5 text-center my-4">
        <i class="bi bi-folder-x display-1 text-muted"></i>
        <h4 class="mt-3 text-muted">No projects found</h4>
        <p class="text-muted">Try adjusting your filters or upload a new project.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($projects as $p): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-custom h-100 p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold text-light mb-0">
                                <a href="project-details.php?id=<?= $p['id'] ?>" class="text-decoration-none text-light">
                                    <i class="bi bi-folder-fill me-2 text-primary"></i><?= htmlspecialchars($p['project_name']) ?>
                                </a>
                            </h5>
                            <span class="badge bg-secondary"><?= htmlspecialchars($p['project_type']) ?></span>
                        </div>
                        <p class="text-muted small mb-3 text-truncate"><?= htmlspecialchars($p['description'] ?: 'No description provided.') ?></p>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between text-muted small border-top border-secondary pt-2 mb-3">
                            <span><i class="bi bi-files me-1"></i> <?= number_format($p['file_count'] ?? 0) ?> files</span>
                            <span><i class="bi bi-hdd me-1"></i> <?= round(($p['total_size'] ?? 0) / (1024 * 1024), 2) ?> MB</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="project-details.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info flex-grow-1">Details</a>
                            <a href="download.php?project_id=<?= $p['id'] ?>&type=setup_assisted" class="btn btn-sm btn-success" title="Download Setup Assisted">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php renderFooter(); ?>
