<?php
require_once __DIR__ . '/../../config/database.php';

class ProjectVersion {
    public ?int $id = null;
    public int $project_id = 0;
    public int $version_number = 1;
    public string $storage_reference = '';
    public int $total_size = 0;
    public int $file_count = 0;
    public string $backup_mode = 'manual'; // manual or smart
    public ?string $uploaded_at = null;

    public static function findById(int $id): ?self {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM project_versions WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        return $data ? self::fromArray($data) : null;
    }

    public static function getByProject(int $projectId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM project_versions WHERE project_id = ? ORDER BY version_number DESC");
        $stmt->execute([$projectId]);
        $versions = [];
        while ($row = $stmt->fetch()) {
            $versions[] = self::fromArray($row);
        }
        return $versions;
    }

    public static function getNextVersionNumber(int $projectId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COALESCE(MAX(version_number), 0) + 1 FROM project_versions WHERE project_id = ?");
        $stmt->execute([$projectId]);
        return (int)$stmt->fetchColumn();
    }

    public function save(): bool {
        $db = Database::getConnection();
        if ($this->id === null) {
            $stmt = $db->prepare("INSERT INTO project_versions (project_id, version_number, storage_reference, total_size, file_count, backup_mode) VALUES (?, ?, ?, ?, ?, ?)");
            $res = $stmt->execute([$this->project_id, $this->version_number, $this->storage_reference, $this->total_size, $this->file_count, $this->backup_mode]);
            if ($res) {
                $this->id = (int)$db->lastInsertId();
            }
            return $res;
        } else {
            $stmt = $db->prepare("UPDATE project_versions SET storage_reference = ?, total_size = ?, file_count = ?, backup_mode = ? WHERE id = ?");
            return $stmt->execute([$this->storage_reference, $this->total_size, $this->file_count, $this->backup_mode, $this->id]);
        }
    }

    private static function fromArray(array $data): self {
        $pv = new self();
        $pv->id = (int)$data['id'];
        $pv->project_id = (int)$data['project_id'];
        $pv->version_number = (int)$data['version_number'];
        $pv->storage_reference = $data['storage_reference'];
        $pv->total_size = (int)$data['total_size'];
        $pv->file_count = (int)$data['file_count'];
        $pv->backup_mode = $data['backup_mode'];
        $pv->uploaded_at = $data['uploaded_at'];
        return $pv;
    }
}
