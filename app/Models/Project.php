<?php
require_once __DIR__ . '/../../config/database.php';

class Project {
    public ?int $id = null;
    public int $user_id = 0;
    public string $project_name = '';
    public ?string $description = null;
    public string $project_type = 'PHP';
    public ?int $current_version_id = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    public static function findById(int $id, bool $includeDeleted = false): ?self {
        $db = Database::getConnection();
        $sql = "SELECT * FROM projects WHERE id = ?";
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        return $data ? self::fromArray($data) : null;
    }

    public static function getByUser(int $userId, array $filters = []): array {
        $db = Database::getConnection();
        $sql = "SELECT p.*, pv.total_size, pv.file_count, pv.backup_mode, pv.uploaded_at as last_backup_at
                FROM projects p
                LEFT JOIN project_versions pv ON p.current_version_id = pv.id
                WHERE p.user_id = ? AND p.deleted_at IS NULL";
        $params = [$userId];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.project_name LIKE ? OR p.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['language'])) {
            $sql .= " AND p.project_type = ?";
            $params[] = $filters['language'];
        }

        $sort = $filters['sort'] ?? 'created_at_desc';
        switch ($sort) {
            case 'name_asc': $sql .= " ORDER BY p.project_name ASC"; break;
            case 'name_desc': $sql .= " ORDER BY p.project_name DESC"; break;
            case 'size_desc': $sql .= " ORDER BY pv.total_size DESC"; break;
            case 'size_asc': $sql .= " ORDER BY pv.total_size ASC"; break;
            case 'created_at_asc': $sql .= " ORDER BY p.created_at ASC"; break;
            case 'created_at_desc': default: $sql .= " ORDER BY p.created_at DESC"; break;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getTrashByUser(int $userId): array {
        $db = Database::getConnection();
        $sql = "SELECT p.*, pv.total_size
                FROM projects p
                LEFT JOIN project_versions pv ON p.current_version_id = pv.id
                WHERE p.user_id = ? AND p.deleted_at IS NOT NULL
                ORDER BY p.deleted_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function countByUser(int $userId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function countAllSystem(): int {
        $db = Database::getConnection();
        return (int)$db->query("SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL")->fetchColumn();
    }

    public function save(): bool {
        $db = Database::getConnection();
        if ($this->id === null) {
            $stmt = $db->prepare("INSERT INTO projects (user_id, project_name, description, project_type, current_version_id) VALUES (?, ?, ?, ?, ?)");
            $res = $stmt->execute([$this->user_id, $this->project_name, $this->description, $this->project_type, $this->current_version_id]);
            if ($res) {
                $this->id = (int)$db->lastInsertId();
            }
            return $res;
        } else {
            $stmt = $db->prepare("UPDATE projects SET project_name = ?, description = ?, project_type = ?, current_version_id = ?, deleted_at = ? WHERE id = ?");
            return $stmt->execute([$this->project_name, $this->description, $this->project_type, $this->current_version_id, $this->deleted_at, $this->id]);
        }
    }

    public function softDelete(): bool {
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function restore(): bool {
        $this->deleted_at = null;
        return $this->save();
    }

    public function hardDelete(): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    private static function fromArray(array $data): self {
        $proj = new self();
        $proj->id = (int)$data['id'];
        $proj->user_id = (int)$data['user_id'];
        $proj->project_name = $data['project_name'];
        $proj->description = $data['description'];
        $proj->project_type = $data['project_type'];
        $proj->current_version_id = $data['current_version_id'] ? (int)$data['current_version_id'] : null;
        $proj->created_at = $data['created_at'];
        $proj->updated_at = $data['updated_at'];
        $proj->deleted_at = $data['deleted_at'];
        return $proj;
    }
}
