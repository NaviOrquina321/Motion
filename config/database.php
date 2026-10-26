<?php
/**
 * CloudVault Configuration & Database Connection Helper
 * InfinityFree & Shared Hosting Compatible
 */

// InfinityFree MySQL Database Credentials
// Replace these values with your InfinityFree MySQL details from cPanel:
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1'); // e.g. 'sql123.infinityfree.com'
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'cloudvault_db'); // e.g. 'if0_12345678_cloudvault'
define('DB_USER', getenv('DB_USER') ?: 'root'); // e.g. 'if0_12345678'
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PROJECTS_STORAGE_PATH', STORAGE_PATH . '/projects');
define('TEMP_STORAGE_PATH', STORAGE_PATH . '/temp');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Local testing fallback to SQLite if MySQL service is not available locally
                $sqlitePath = STORAGE_PATH . '/cloudvault.sqlite';
                self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                self::initSqliteSchema(self::$instance);
            }
        }
        return self::$instance;
    }

    private static function initSqliteSchema(PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user',
                storage_quota INTEGER NOT NULL DEFAULT 10737418240,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                project_name TEXT NOT NULL,
                description TEXT,
                project_type TEXT DEFAULT 'PHP',
                current_version_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL
            );

            CREATE TABLE IF NOT EXISTS project_versions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL,
                version_number INTEGER NOT NULL DEFAULT 1,
                storage_reference TEXT NOT NULL,
                total_size INTEGER NOT NULL DEFAULT 0,
                file_count INTEGER NOT NULL DEFAULT 0,
                backup_mode TEXT DEFAULT 'manual',
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS project_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_version_id INTEGER NOT NULL,
                relative_path TEXT NOT NULL,
                file_size INTEGER DEFAULT 0,
                checksum_sha256 TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS project_requirements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_version_id INTEGER NOT NULL,
                requirement_type TEXT NOT NULL,
                requirement_name TEXT NOT NULL,
                version_constraint TEXT,
                detection_source TEXT,
                confidence_status TEXT DEFAULT 'inferred'
            );

            CREATE TABLE IF NOT EXISTS database_backups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_version_id INTEGER NOT NULL,
                original_filename TEXT NOT NULL,
                storage_reference TEXT NOT NULL,
                file_size INTEGER DEFAULT 0,
                checksum_sha256 TEXT NOT NULL,
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS downloads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                project_version_id INTEGER NOT NULL,
                download_type TEXT DEFAULT 'standard',
                downloaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT NOT NULL,
                project_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS system_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            INSERT OR IGNORE INTO system_settings (setting_key, setting_value) VALUES
            ('default_storage_quota', '10737418240'),
            ('max_upload_size', '536870912'),
            ('supported_languages', 'PHP,HTML/CSS,JavaScript,Python,Java,C++'),
            ('system_installed', 'false'),
            ('app_name', 'CloudVault'),
            ('trash_retention_days', '30');
        ");
    }
}
