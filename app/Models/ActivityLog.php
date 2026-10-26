<?php
require_once __DIR__ . '/../../config/database.php';

class ActivityLog {
    public static function log(?int $userId, string $action, ?int $projectId = null): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, project_id) VALUES (?, ?, ?)");
            return $stmt->execute([$userId, $action, $projectId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getRecent(int $limit = 20): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT al.*, u.username, u.full_name, p.project_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN projects p ON al.project_id = p.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
