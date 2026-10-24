<?php
require_once __DIR__ . '/../../config/database.php';

class BackupService {
    /**
     * Stores uploaded project files or zip, preserving relative path structure and integrity.
     */
    public static function processUpload(array $filesPayload, string $storageRef, array $excludedFiles = []): array {
        $targetDir = PROJECTS_STORAGE_PATH . '/' . $storageRef;
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $processedFiles = [];
        $totalBytes = 0;

        // Check if payload is a single ZIP file
        if (isset($filesPayload['tmp_name']) && !is_array($filesPayload['tmp_name']) && strtolower(pathinfo($filesPayload['name'], PATHINFO_EXTENSION)) === 'zip') {
            return self::extractZipUpload($filesPayload['tmp_name'], $targetDir, $excludedFiles);
        }

        // Multi-file folder or archive handling
        if (isset($filesPayload['tmp_name']) && is_array($filesPayload['tmp_name'])) {
            $count = count($filesPayload['tmp_name']);
            for ($i = 0; $i < $count; $i++) {
                if ($filesPayload['error'][$i] !== UPLOAD_ERR_OK) continue;

                $relPath = $filesPayload['full_path'][$i] ?? $filesPayload['name'][$i];
                $relPath = self::sanitizeRelativePath($relPath);

                if (self::isExcluded($relPath, $excludedFiles)) {
                    continue;
                }

                $destPath = $targetDir . '/' . $relPath;
                $destDir = dirname($destPath);
                if (!file_exists($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                if (move_uploaded_file($filesPayload['tmp_name'][$i], $destPath)) {
                    $size = filesize($destPath);
                    $hash = hash_file('sha256', $destPath);
                    $totalBytes += $size;

                    $processedFiles[] = [
                        'relative_path' => $relPath,
                        'file_size' => $size,
                        'checksum_sha256' => $hash,
                        'full_storage_path' => $destPath
                    ];
                }
            }
        }

        return [
            'file_count' => count($processedFiles),
            'total_size' => $totalBytes,
            'files' => $processedFiles,
            'storage_directory' => $targetDir
        ];
    }

    private static function extractZipUpload(string $zipTmpPath, string $targetDir, array $excludedFiles): array {
        $zip = new ZipArchive();
        if ($zip->open($zipTmpPath) !== true) {
            throw new Exception("Failed to open uploaded ZIP archive.");
        }

        $processedFiles = [];
        $totalBytes = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $relPath = self::sanitizeRelativePath($stat['name']);

            // Ignore directories or invalid paths
            if (substr($stat['name'], -1) === '/' || empty($relPath)) {
                continue;
            }

            if (self::isExcluded($relPath, $excludedFiles)) {
                continue;
            }

            $destPath = $targetDir . '/' . $relPath;
            $destDir = dirname($destPath);
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Stream extract file
            $fp = $zip->getStream($stat['name']);
            if ($fp) {
                $destFp = fopen($destPath, 'wb');
                if ($destFp) {
                    stream_copy_to_stream($fp, $destFp);
                    fclose($destFp);
                }
                fclose($fp);

                $size = filesize($destPath);
                $hash = hash_file('sha256', $destPath);
                $totalBytes += $size;

                $processedFiles[] = [
                    'relative_path' => $relPath,
                    'file_size' => $size,
                    'checksum_sha256' => $hash,
                    'full_storage_path' => $destPath
                ];
            }
        }

        $zip->close();

        return [
            'file_count' => count($processedFiles),
            'total_size' => $totalBytes,
            'files' => $processedFiles,
            'storage_directory' => $targetDir
        ];
    }

    public static function sanitizeRelativePath(string $path): string {
        $path = str_replace('\\', '/', $path);
        // Prevent directory traversal
        $parts = explode('/', $path);
        $safeParts = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..') continue;
            $safeParts[] = preg_replace('/[^a-zA-Z0-9_\-\.\ \(\)\[\]]/', '', $part);
        }
        return implode('/', $safeParts);
    }

    private static function isExcluded(string $relPath, array $excludedFiles): bool {
        foreach ($excludedFiles as $excluded) {
            if ($relPath === $excluded || str_starts_with($relPath, $excluded . '/')) {
                return true;
            }
        }
        return false;
    }
}
