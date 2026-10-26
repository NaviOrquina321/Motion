<?php
/**
 * ProjectAnalyzer Service
 * Analyzes uploaded project directories safely without executing code.
 * Detects languages, frameworks, dependency manifests, database files, and secret patterns.
 */

class ProjectAnalyzer {
    public static function analyze(string $projectDir): array {
        $files = self::scanDirRecursive($projectDir);

        $analysis = [
            'detected_language' => 'Unknown',
            'framework' => 'Not Detected',
            'database_backup' => null,
            'requirements' => [],
            'secrets' => [],
            'file_count' => count($files),
            'total_size' => 0
        ];

        $extensionCounts = [];
        $hasComposer = false;
        $hasRequirementsTxt = false;
        $hasPackageJson = false;
        $hasPom = false;
        $hasCMake = false;

        foreach ($files as $file) {
            $relativePath = str_replace($projectDir . '/', '', $file);
            $size = filesize($file);
            $analysis['total_size'] += $size;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $filename = basename($file);

            if ($ext) {
                $extensionCounts[$ext] = ($extensionCounts[$ext] ?? 0) + 1;
            }

            // Secret Detection
            self::checkSecrets($file, $relativePath, $analysis['secrets']);

            // Database Backup Detection
            if ($ext === 'sql') {
                $analysis['database_backup'] = [
                    'path' => $relativePath,
                    'size' => $size,
                    'name' => $filename
                ];
                $analysis['requirements'][] = [
                    'type' => 'database',
                    'name' => 'MySQL Database Backup (' . $filename . ')',
                    'version' => null,
                    'source' => $relativePath,
                    'status' => 'verified'
                ];
            }

            // Dependency Manifests Checks
            if ($filename === 'composer.json') {
                $hasComposer = true;
                self::analyzeComposerJson($file, $analysis);
            } elseif ($filename === 'requirements.txt') {
                $hasRequirementsTxt = true;
                self::analyzeRequirementsTxt($file, $analysis);
            } elseif ($filename === 'package.json') {
                $hasPackageJson = true;
                self::analyzePackageJson($file, $analysis);
            } elseif ($filename === 'pom.xml') {
                $hasPom = true;
                $analysis['requirements'][] = [
                    'type' => 'dependency',
                    'name' => 'Maven Build Manager',
                    'version' => null,
                    'source' => $relativePath,
                    'status' => 'verified'
                ];
            } elseif ($filename === 'CMakeLists.txt') {
                $hasCMake = true;
                $analysis['requirements'][] = [
                    'type' => 'dependency',
                    'name' => 'CMake Build System',
                    'version' => null,
                    'source' => $relativePath,
                    'status' => 'verified'
                ];
            }
        }

        // Language determination based on extensions & manifests
        if ($hasComposer || ($extensionCounts['php'] ?? 0) > 0) {
            $analysis['detected_language'] = 'PHP';
            $analysis['requirements'][] = [
                'type' => 'language',
                'name' => 'PHP Runtime',
                'version' => '>= 8.0',
                'source' => 'File Inspection',
                'status' => 'inferred'
            ];
            $analysis['requirements'][] = [
                'type' => 'runtime',
                'name' => 'Apache/Nginx Web Server',
                'version' => null,
                'source' => 'File Inspection',
                'status' => 'inferred'
            ];
        } elseif ($hasPackageJson || ($extensionCounts['js'] ?? 0) + ($extensionCounts['ts'] ?? 0) > 0) {
            $analysis['detected_language'] = 'JavaScript / Node.js';
            $analysis['requirements'][] = [
                'type' => 'language',
                'name' => 'Node.js Engine',
                'version' => '>= 16.0',
                'source' => 'File Inspection',
                'status' => 'inferred'
            ];
        } elseif ($hasRequirementsTxt || ($extensionCounts['py'] ?? 0) > 0) {
            $analysis['detected_language'] = 'Python';
            $analysis['requirements'][] = [
                'type' => 'language',
                'name' => 'Python Interpreter',
                'version' => '>= 3.8',
                'source' => 'File Inspection',
                'status' => 'inferred'
            ];
        } elseif ($hasPom || ($extensionCounts['java'] ?? 0) > 0) {
            $analysis['detected_language'] = 'Java';
            $analysis['requirements'][] = [
                'type' => 'language',
                'name' => 'Java Development Kit (JDK)',
                'version' => '>= 11',
                'source' => 'File Inspection',
                'status' => 'inferred'
            ];
        } elseif ($hasCMake || ($extensionCounts['cpp'] ?? 0) + ($extensionCounts['c'] ?? 0) + ($extensionCounts['hpp'] ?? 0) + ($extensionCounts['h'] ?? 0) > 0) {
            $analysis['detected_language'] = 'C++';
            $analysis['requirements'][] = [
                'type' => 'language',
                'name' => 'C++ Compiler (GCC/Clang/MSVC)',
                'version' => 'C++17 standard',
                'source' => 'File Inspection',
                'status' => 'inferred'
            ];
        } elseif (($extensionCounts['html'] ?? 0) + ($extensionCounts['css'] ?? 0) > 0) {
            $analysis['detected_language'] = 'HTML/CSS';
        }

        return $analysis;
    }

    private static function checkSecrets(string $filePath, string $relativePath, array &$secrets) {
        $filename = basename($filePath);

        // 1. Sensitive filenames
        if (in_array(strtolower($filename), ['.env', 'id_rsa', 'id_ed25519', 'credentials.json', 'secrets.json', 'config.key'])) {
            $secrets[] = [
                'file' => $relativePath,
                'reason' => 'Sensitive configuration or key file name (' . $filename . ')'
            ];
            return;
        }

        // 2. Scan file content (skip binary / large files)
        if (filesize($filePath) > 500000) { // skip > 500KB
            return;
        }

        $content = @file_get_contents($filePath);
        if (!$content) return;

        // Check patterns (API keys, private keys, passwords)
        $patterns = [
            'PRIVATE KEY' => '/-----BEGIN (?:RSA|OPENSSH|EC|PGP) PRIVATE KEY-----/',
            'AWS Access Key' => '/AKIA[0-9A-Z]{16}/',
            'Generic API Key' => '/(?:api[_-]?key|secret[_-]?key)\s*[:=]\s*[\'"][A-Za-z0-9_\-]{16,}[\'"]/i',
            'Database Password' => '/(?:db_pass|database_password|mysql_password)\s*[:=]\s*[\'"][^\'"]+[\'"]/i'
        ];

        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $content)) {
                $secrets[] = [
                    'file' => $relativePath,
                    'reason' => 'Contains potential ' . $label
                ];
                break;
            }
        }
    }

    private static function analyzeComposerJson(string $filePath, array &$analysis) {
        $content = @file_get_contents($filePath);
        if (!$content) return;

        $json = json_decode($content, true);
        if (!$json) return;

        if (isset($json['require'])) {
            foreach ($json['require'] as $pkg => $ver) {
                if ($pkg === 'php') {
                    $analysis['requirements'][] = [
                        'type' => 'language',
                        'name' => 'PHP Runtime',
                        'version' => $ver,
                        'source' => 'composer.json',
                        'status' => 'verified'
                    ];
                } elseif (strpos($pkg, 'laravel/') === 0) {
                    $analysis['framework'] = 'Laravel (' . $ver . ')';
                } elseif (strpos($pkg, 'symfony/') === 0) {
                    $analysis['framework'] = 'Symfony (' . $ver . ')';
                } elseif (strpos($pkg, 'ext-') === 0) {
                    $analysis['requirements'][] = [
                        'type' => 'extension',
                        'name' => 'PHP Extension: ' . substr($pkg, 4),
                        'version' => $ver,
                        'source' => 'composer.json',
                        'status' => 'verified'
                    ];
                } else {
                    $analysis['requirements'][] = [
                        'type' => 'dependency',
                        'name' => $pkg,
                        'version' => $ver,
                        'source' => 'composer.json',
                        'status' => 'verified'
                    ];
                }
            }
        }
    }

    private static function analyzeRequirementsTxt(string $filePath, array &$analysis) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;

            if (preg_match('/^([a-zA-Z0-9_\-\.]+)\s*([=><~]=?\s*.*)?$/', $line, $matches)) {
                $pkg = $matches[1];
                $ver = $matches[2] ?? 'any';

                if (in_array(strtolower($pkg), ['django', 'flask', 'fastapi', 'pyramid'])) {
                    $analysis['framework'] = ucfirst($pkg) . ' (' . $ver . ')';
                }

                $analysis['requirements'][] = [
                    'type' => 'dependency',
                    'name' => $pkg,
                    'version' => $ver,
                    'source' => 'requirements.txt',
                    'status' => 'verified'
                ];
            }
        }
    }

    private static function analyzePackageJson(string $filePath, array &$analysis) {
        $content = @file_get_contents($filePath);
        if (!$content) return;

        $json = json_decode($content, true);
        if (!$json) return;

        if (isset($json['dependencies'])) {
            foreach ($json['dependencies'] as $pkg => $ver) {
                if (in_array($pkg, ['react', 'vue', 'express', 'next', 'nuxt', 'angular'])) {
                    $analysis['framework'] = ucfirst($pkg) . ' (' . $ver . ')';
                }
                $analysis['requirements'][] = [
                    'type' => 'dependency',
                    'name' => $pkg,
                    'version' => $ver,
                    'source' => 'package.json',
                    'status' => 'verified'
                ];
            }
        }
    }

    private static function scanDirRecursive(string $dir): array {
        $results = [];
        $files = @scandir($dir);
        if (!$files) return [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $results = array_merge($results, self::scanDirRecursive($path));
            } else {
                $results[] = $path;
            }
        }
        return $results;
    }
}
