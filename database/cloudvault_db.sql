-- CloudVault Database Schema
-- Version 1.0.0

CREATE DATABASE IF NOT EXISTS `cloudvault_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cloudvault_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `storage_quota` BIGINT UNSIGNED NOT NULL DEFAULT 10737418240, -- Default 10GB in bytes
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Projects Table
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `project_name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `project_type` VARCHAR(50) NOT NULL DEFAULT 'PHP',
  `current_version_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Project Versions Table
CREATE TABLE IF NOT EXISTS `project_versions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `version_number` INT UNSIGNED NOT NULL DEFAULT 1,
  `storage_reference` VARCHAR(255) NOT NULL,
  `total_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `file_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `backup_mode` ENUM('manual', 'smart') NOT NULL DEFAULT 'manual',
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint for projects.current_version_id safely after project_versions is created
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_current_version`
  FOREIGN KEY (`current_version_id`) REFERENCES `project_versions` (`id`)
  ON DELETE SET NULL;

-- 4. Project Files Table
CREATE TABLE IF NOT EXISTS `project_files` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_version_id` INT UNSIGNED NOT NULL,
  `relative_path` VARCHAR(500) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `checksum_sha256` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_version_id`) REFERENCES `project_versions` (`id`) ON DELETE CASCADE,
  INDEX `idx_project_version_path` (`project_version_id`, `relative_path`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Project Requirements Table
CREATE TABLE IF NOT EXISTS `project_requirements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_version_id` INT UNSIGNED NOT NULL,
  `requirement_type` VARCHAR(50) NOT NULL, -- language, framework, dependency, database, extension, runtime
  `requirement_name` VARCHAR(150) NOT NULL,
  `version_constraint` VARCHAR(50) NULL,
  `detection_source` VARCHAR(100) NULL, -- composer.json, requirements.txt, package.json, file inspection, etc.
  `confidence_status` ENUM('verified', 'inferred', 'unknown', 'missing') NOT NULL DEFAULT 'inferred',
  FOREIGN KEY (`project_version_id`) REFERENCES `project_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Database Backups Table
CREATE TABLE IF NOT EXISTS `database_backups` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_version_id` INT UNSIGNED NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `storage_reference` VARCHAR(255) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `checksum_sha256` VARCHAR(64) NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_version_id`) REFERENCES `project_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Downloads Table
CREATE TABLE IF NOT EXISTS `downloads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `project_version_id` INT UNSIGNED NOT NULL,
  `download_type` ENUM('standard', 'setup_assisted') NOT NULL DEFAULT 'standard',
  `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_version_id`) REFERENCES `project_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(255) NOT NULL,
  `project_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('default_storage_quota', '10737418240'), -- 10 GB in bytes
('max_upload_size', '536870912'), -- 500 MB limit per upload
('supported_languages', 'PHP,HTML/CSS,JavaScript,Python,Java,C++'),
('system_installed', 'false'),
('app_name', 'CloudVault'),
('trash_retention_days', '30')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
