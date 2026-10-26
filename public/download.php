<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/ProjectVersion.php';
require_once __DIR__ . '/../app/Services/PackageBuilder.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';

$currentUser = AuthMiddleware::requireLogin();

$projectId = (int)($_GET['project_id'] ?? 0);
$downloadType = $_GET['type'] ?? 'standard';

$project = Project::findById($projectId);
if (!$project || $project->user_id !== $currentUser['id']) {
    http_response_code(403);
    die("Access Denied: You do not own this project.");
}

$version = ProjectVersion::findById($project->current_version_id ?? 0);
if (!$version) {
    http_response_code(404);
    die("Project version missing.");
}

try {
    if ($downloadType === 'setup_assisted') {
        $zipPath = PackageBuilder::buildSetupAssistedZip($project, $version);
        $downloadName = "CloudVault_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $project->project_name) . "_SetupAssisted.zip";
    } else {
        $zipPath = PackageBuilder::buildStandardZip($project, $version);
        $downloadName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $project->project_name) . "_Standard.zip";
    }

    // Record Download Activity
    $db = Database::getConnection();
    $stmtDl = $db->prepare("INSERT INTO downloads (user_id, project_version_id, download_type) VALUES (?, ?, ?)");
    $stmtDl->execute([$currentUser['id'], $version->id, $downloadType === 'setup_assisted' ? 'setup_assisted' : 'standard']);
    ActivityLog::log($currentUser['id'], "Downloaded project package ({$downloadType}): {$project->project_name}", $project->id);

    // Serve File Download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($zipPath);

    // Clean up temp zip file
    @unlink($zipPath);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    die("Download Generation Error: " . $e->getMessage());
}
