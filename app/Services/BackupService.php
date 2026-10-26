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

        // Check if payload contains a single ZIP archive upload
        $isSingleZip = isset($filesPayload['tmp_name']) && !is_array($filesPayload['tmp_name'])
            && strtolower(pathinfo($filesPayload['name'] ?? '', PATHINFO_EXTENSION)) === 'zip';

        $isArrayZip = isset($filesPayload['tmp_name']) && is_array($filesPayload['tmp_name']) && count($filesPayload['tmp_name']) === 1
            && strtolower(pathinfo($filesPayload['name'][0] ?? '', PATHINFO_EXTENSION)) === 'zip';

        if ($isSingleZip) {
            return self::extractZipUpload($filesPayload['tmp_name'], $targetDir, $excludedFiles);
        } elseif ($isArrayZip && $filesPayload['error'][0] === UPLOAD_ERR_OK) {
            return self::extractZipUpload($filesPayload['tmp_name'][0], $targetDir, $excludedFiles);
        }

        // Multi-file folder upload handling
        if (isset($filesPayload['tmp_name']) && is_array($filesPayload['tmp_name'])) {
            $count = count($filesPayload['tmp_name']);
            for ($i = 0; $i < $count; $i++) {
                if ($filesPayload['error'][$i] !== UPLOAD_ERR_OK) continue;

                $relPath = $filesPayload['full_path'][$i] ?? $filesPayload['name'][$i];
                $relPath = self::sanitizeRelativePath($relPath);

                if (empty($relPath) || self::isExcluded($relPath, $excludedFiles)) {
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

            // Ignore directories or empty paths
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
        // Standardize slashes
        $path = str_replace('\\', '/', $path);

        // Split path components and remove traversal segments
        $parts = explode('/', $path);
        $safeParts = [];

        foreach ($parts as $part) {
            // Filter out empty, current dir (.), or parent dir (..)
            if ($part === '' || $part === '.' || $part === '..') {
                continue;
            }

            // Remove null bytes and dangerous control characters
            $part = str_replace("\0", '', $part);
            $part = preg_replace('/[\x00-\x1F\x7F]/', '', $part);

            if ($part !== '') {
                $safeParts[] = $part;
            }
        }

        return implode('/', $safeParts);
    }

    private static function isExcluded(string $relPath, array $excludedFiles): bool {
        foreach ($excludedFiles as $excluded) {
            $excluded = trim($excluded);
            if (empty($excluded)) continue;
            if ($relPath === $excluded || str_starts_with($relPath, $excluded . '/')) {
                return true;
            }
        }
        return false;
    }

    public static function deleteDirectory(string $dir): void {
        if (!file_exists($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
