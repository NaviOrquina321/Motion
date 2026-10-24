<?php
require_once __DIR__ . '/../../config/database.php';

class IntegrityChecker {
    /**
     * Compares project_files sha256 records with actual file system hashes
     */
    public static function verifyProjectVersion(int $versionId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT pf.*, pv.storage_reference
            FROM project_files pf
            JOIN project_versions pv ON pf.project_version_id = pv.id
            WHERE pf.project_version_id = ?
        ");
        $stmt->execute([$versionId]);
        $files = $stmt->fetchAll();

        $total = count($files);
        $passed = 0;
        $failed = [];

        foreach ($files as $file) {
            $fullPath = PROJECTS_STORAGE_PATH . '/' . $file['storage_reference'] . '/' . $file['relative_path'];
            if (!file_exists($fullPath)) {
                $failed[] = [
                    'file' => $file['relative_path'],
                    'reason' => 'File missing on storage server'
                ];
                continue;
            }

            $currentHash = hash_file('sha256', $fullPath);
            if ($currentHash !== $file['checksum_sha256']) {
                $failed[] = [
                    'file' => $file['relative_path'],
                    'reason' => 'Checksum mismatch (file corrupted or modified)'
                ];
            } else {
                $passed++;
            }
        }

        return [
            'status' => count($failed) === 0 ? 'verified' : 'error',
            'total_files' => $total,
            'passed_count' => $passed,
            'failed_count' => count($failed),
            'failures' => $failed
        ];
    }
}
