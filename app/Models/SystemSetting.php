<?php
require_once __DIR__ . '/../../config/database.php';

class SystemSetting {
    public static function get(string $key, $default = null) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public static function set(string $key, string $value): bool {
        $db = Database::getConnection();
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value");
        } else {
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        }
        return $stmt->execute([$key, $value]);
    }

    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
