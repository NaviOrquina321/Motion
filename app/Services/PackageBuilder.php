<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/SetupGuideGenerator.php';

class PackageBuilder {
    /**
     * Builds standard ZIP package for project version
     */
    public static function buildStandardZip(Project $project, ProjectVersion $version): string {
        $storageDir = PROJECTS_STORAGE_PATH . '/' . $version->storage_reference;
        $tempZipPath = TEMP_STORAGE_PATH . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $project->project_name) . '_v' . $version->version_number . '_Standard.zip';

        if (file_exists($tempZipPath)) {
            unlink($tempZipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Could not create standard archive.");
        }

        self::addDirToZip($zip, $storageDir, '');

        // Add standard README & Checksums
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT relative_path, checksum_sha256 FROM project_files WHERE project_version_id = ?");
        $stmt->execute([$version->id]);
        $files = $stmt->fetchAll();

        $manifest = [
            'project_name' => $project->project_name,
            'version' => $version->version_number,
            'file_count' => count($files),
            'checksums' => $files
        ];
        $zip->addFromString('checksums.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('README.md', "# " . $project->project_name . "\n\nStandard CloudVault Backup Download\nVersion: " . $version->version_number);

        $zip->close();
        return $tempZipPath;
    }

    /**
     * Builds Setup Assistant package for project version
     */
    public static function buildSetupAssistedZip(Project $project, ProjectVersion $version): string {
        $storageDir = PROJECTS_STORAGE_PATH . '/' . $version->storage_reference;
        $tempZipPath = TEMP_STORAGE_PATH . '/CloudVault_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $project->project_name) . '_v' . $version->version_number . '_SetupAssisted.zip';

        if (file_exists($tempZipPath)) {
            unlink($tempZipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Could not create setup assisted archive.");
        }

        // Add original project files into project/ folder
        self::addDirToZip($zip, $storageDir, 'project');

        // Add database backups if existing
        $db = Database::getConnection();
        $stmtDb = $db->prepare("SELECT * FROM database_backups WHERE project_version_id = ?");
        $stmtDb->execute([$version->id]);
        $dbBackups = $stmtDb->fetchAll();

        foreach ($dbBackups as $dbB) {
            $dbFile = PROJECTS_STORAGE_PATH . '/' . $dbB['storage_reference'];
            if (file_exists($dbFile)) {
                $zip->addFile($dbFile, 'database/' . $dbB['original_filename']);
            }
        }

        // Requirements & Checksums
        $stmtReq = $db->prepare("SELECT * FROM project_requirements WHERE project_version_id = ?");
        $stmtReq->execute([$version->id]);
        $reqs = $stmtReq->fetchAll();

        $stmtFiles = $db->prepare("SELECT relative_path, file_size, checksum_sha256 FROM project_files WHERE project_version_id = ?");
        $stmtFiles->execute([$version->id]);
        $files = $stmtFiles->fetchAll();

        $zip->addFromString('requirements/requirements.json', json_encode($reqs, JSON_PRETTY_PRINT));
        $zip->addFromString('checksums/manifest.json', json_encode($files, JSON_PRETTY_PRINT));

        // Generate Setup Guides
        $htmlGuide = SetupGuideGenerator::generateHtmlGuide($project, $version, $reqs, !empty($dbBackups));
        $txtGuide = SetupGuideGenerator::generateTxtGuide($project, $version, $reqs, !empty($dbBackups));

        $zip->addFromString('setup/installation_guide.html', $htmlGuide);
        $zip->addFromString('setup/installation_guide.txt', $txtGuide);
        $zip->addFromString('README.md', "# " . $project->project_name . " - Setup Assisted Package\n\nPlease refer to setup/installation_guide.html for complete restoration steps.");

        $zip->close();
        return $tempZipPath;
    }

    private static function addDirToZip(ZipArchive $zip, string $dir, string $zipPrefix) {
        $files = @scandir($dir);
        if (!$files) return;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . '/' . $file;
            $zipPath = $zipPrefix !== '' ? $zipPrefix . '/' . $file : $file;

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($zipPath);
                self::addDirToZip($zip, $fullPath, $zipPath);
            } else {
                $zip->addFile($fullPath, $zipPath);
            }
        }
    }
}
