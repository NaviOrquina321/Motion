<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/ProjectVersion.php';
require_once __DIR__ . '/../app/Services/BackupService.php';
require_once __DIR__ . '/../app/Services/ProjectAnalyzer.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$currentUser = AuthMiddleware::requireLogin();
$userObj = User::findById($currentUser['id']);

$error = '';
$success = '';
$analysisResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $projectName = trim($_POST['project_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $backupMode = $_POST['backup_mode'] ?? 'manual';
        $languageOverride = $_POST['project_type'] ?? '';
        $excludedFiles = isset($_POST['exclude_files']) ? array_filter(explode("\n", str_replace("\r", "", $_POST['exclude_files']))) : [];

        if (empty($projectName)) {
            $error = 'Project name is required.';
        } elseif (empty($_FILES['project_files']['name'])) {
            $error = 'Please select project files or a ZIP archive to upload.';
        } else {
            $storageRef = uniqid('proj_') . '_' . time();
            $targetDir = PROJECTS_STORAGE_PATH . '/' . $storageRef;

            try {
                // 1. Process Main Project Uploads
                $uploadResult = BackupService::processUpload($_FILES['project_files'], $storageRef, $excludedFiles);

                // Quota Check
                if ($userObj->getUsedStorage() + $uploadResult['total_size'] > $userObj->storage_quota) {
                    throw new Exception("Storage quota exceeded. Please delete existing projects or upgrade quota.");
                }

                // 2. Process Attached Database Backup prior to analysis so it is detected
                $dbBackupInfo = null;
                if (!empty($_FILES['database_backup']['tmp_name']) && $_FILES['database_backup']['error'] === UPLOAD_ERR_OK) {
                    $dbOriginalName = basename($_FILES['database_backup']['name']);
                    $dbTargetDir = $uploadResult['storage_directory'] . '/database';
                    if (!file_exists($dbTargetDir)) {
                        mkdir($dbTargetDir, 0755, true);
                    }

                    $dbDest = $dbTargetDir . '/' . $dbOriginalName;
                    if (move_uploaded_file($_FILES['database_backup']['tmp_name'], $dbDest)) {
                        $dbBackupInfo = [
                            'original_filename' => $dbOriginalName,
                            'storage_reference' => $storageRef . '/database/' . $dbOriginalName,
                            'file_size' => filesize($dbDest),
                            'checksum_sha256' => hash_file('sha256', $dbDest)
                        ];
                    }
                }

                // 3. Analyze Uploaded Directory Structure & Files
                $analysis = ProjectAnalyzer::analyze($uploadResult['storage_directory']);

                // Create Project Entry
                $project = new Project();
                $project->user_id = $userObj->id;
                $project->project_name = $projectName;
                $project->description = $description;
                $project->project_type = !empty($languageOverride) ? $languageOverride : $analysis['detected_language'];
                $project->save();

                // Create Version Entry
                $version = new ProjectVersion();
                $version->project_id = $project->id;
                $version->version_number = 1;
                $version->storage_reference = $storageRef;
                $version->total_size = $uploadResult['total_size'];
                $version->file_count = $uploadResult['file_count'];
                $version->backup_mode = $backupMode;
                $version->save();

                // Link Current Version
                $project->current_version_id = $version->id;
                $project->save();

                // Save File Manifest Entries
                $db = Database::getConnection();
                $stmtFile = $db->prepare("INSERT INTO project_files (project_version_id, relative_path, file_size, checksum_sha256) VALUES (?, ?, ?, ?)");
                foreach ($uploadResult['files'] as $f) {
                    $stmtFile->execute([$version->id, $f['relative_path'], $f['file_size'], $f['checksum_sha256']]);
                }

                // Save Requirements
                $stmtReq = $db->prepare("INSERT INTO project_requirements (project_version_id, requirement_type, requirement_name, version_constraint, detection_source, confidence_status) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($analysis['requirements'] as $req) {
                    $stmtReq->execute([$version->id, $req['type'], $req['name'], $req['version'], $req['source'], $req['status']]);
                }

                // Save Database Backup Entry if present
                if ($dbBackupInfo) {
                    $stmtDb = $db->prepare("INSERT INTO database_backups (project_version_id, original_filename, storage_reference, file_size, checksum_sha256) VALUES (?, ?, ?, ?, ?)");
                    $stmtDb->execute([
                        $version->id,
                        $dbBackupInfo['original_filename'],
                        $dbBackupInfo['storage_reference'],
                        $dbBackupInfo['file_size'],
                        $dbBackupInfo['checksum_sha256']
                    ]);
                }

                ActivityLog::log($userObj->id, "Uploaded new project: {$projectName}", $project->id);
                $success = "Project '{$projectName}' uploaded and analyzed successfully!";
                $analysisResult = $analysis;

            } catch (Exception $e) {
                // Cleanup orphaned storage directory on error
                BackupService::deleteDirectory($targetDir);
                $error = "Upload Failed: " . $e->getMessage();
            }
        }
    }
}

$csrfToken = AuthMiddleware::generateCsrfToken();
renderHeader('Upload Project', 'upload');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1"><i class="bi bi-cloud-upload text-primary me-2"></i>Upload Project</h2>
        <p class="text-muted mb-0">Select project files or a ZIP archive to back up and analyze.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <a href="projects.php" class="btn btn-sm btn-outline-success ms-3">Go to My Projects</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom p-4 mb-4">
            <form method="POST" action="upload.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Project Name</label>
                        <input type="text" name="project_name" class="form-control" required placeholder="My Software Project">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Language / Stack Override (Optional)</label>
                        <select name="project_type" class="form-select">
                            <option value="">Auto-Detect Stack</option>
                            <option value="PHP">PHP</option>
                            <option value="Python">Python</option>
                            <option value="JavaScript">JavaScript / Node.js</option>
                            <option value="Java">Java</option>
                            <option value="C++">C++</option>
                            <option value="HTML/CSS">HTML/CSS</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Project Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief details about what this project does..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Backup Mode</label>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="backup_mode" id="modeSmart" value="smart" checked>
                            <label class="form-check-label fw-bold text-success" for="modeSmart">
                                <i class="bi bi-magic me-1"></i> Smart Backup (With Requirement Analysis)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="backup_mode" id="modeManual" value="manual">
                            <label class="form-check-label fw-bold text-primary" for="modeManual">
                                <i class="bi bi-folder-check me-1"></i> Manual Backup (Direct File Preservation)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Dropzone Area -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Project Files or ZIP Archive</label>
                    <div id="dropZone" class="drop-zone" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-arrow-up display-3 text-primary"></i>
                        <h5 class="fw-bold mt-2">Drag and drop folder / ZIP archive here</h5>
                        <p class="text-muted mb-2">Or click to select files from your computer</p>
                        <span id="selectedFilesCount" class="badge bg-primary fs-6 d-none">0 file(s) selected</span>
                        <input type="file" id="fileInput" name="project_files[]" class="d-none" multiple webkitdirectory directory>
                    </div>
                </div>

                <!-- Optional Database Upload -->
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-database me-1 text-info"></i> Optional Database Dump (.sql)</label>
                    <input type="file" name="database_backup" class="form-control" accept=".sql">
                    <small class="text-muted">Attach an exported MySQL database backup file if applicable.</small>
                </div>

                <!-- Exclude Secrets / Sensitive Files -->
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-shield-exclamation text-warning me-1"></i> Exclude Sensitive Files (Optional)</label>
                    <textarea name="exclude_files" class="form-control" rows="2" placeholder=".env&#10;config/keys.json"></textarea>
                    <small class="text-muted">List relative file paths to exclude from backup (one path per line).</small>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-2">
                    Start Backup Upload & Analysis <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Upload Guidelines Sidecard -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-info-circle text-info me-2"></i>Upload Guidelines</h5>
            <ul class="text-muted ps-3 small">
                <li class="mb-2"><strong>Preservation:</strong> Nested directory paths and relative filenames are maintained exactly as uploaded.</li>
                <li class="mb-2"><strong>Secret Detection:</strong> Potential API keys, private key blocks, and <code>.env</code> configurations will be flagged.</li>
                <li class="mb-2"><strong>Integrity:</strong> Every file receives a SHA-256 checksum upon storage.</li>
                <li class="mb-2"><strong>Safety:</strong> CloudVault never executes uploaded project code on the server.</li>
            </ul>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
