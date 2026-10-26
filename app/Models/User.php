<?php
require_once __DIR__ . '/../../config/database.php';

class User {
    public ?int $id = null;
    public string $full_name = '';
    public string $username = '';
    public string $email = '';
    public string $password_hash = '';
    public string $role = 'user';
    public int $storage_quota = 10737418240; // 10 GB
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function findById(int $id): ?self {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        return $data ? self::fromArray($data) : null;
    }

    public static function findByEmailOrUsername(string $identifier): ?self {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$identifier, $identifier]);
        $data = $stmt->fetch();
        return $data ? self::fromArray($data) : null;
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool {
        $db = Database::getConnection();
        $query = "SELECT COUNT(*) FROM users WHERE email = ?";
        $params = [$email];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function usernameExists(string $username, ?int $excludeId = null): bool {
        $db = Database::getConnection();
        $query = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function countAll(): int {
        $db = Database::getConnection();
        return (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public static function getAll(int $limit = 50, int $offset = 0): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = [];
        while ($row = $stmt->fetch()) {
            $users[] = self::fromArray($row);
        }
        return $users;
    }

    public function getUsedStorage(): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(pv.total_size), 0)
            FROM projects p
            JOIN project_versions pv ON p.id = pv.project_id
            WHERE p.user_id = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([$this->id]);
        return (int)$stmt->fetchColumn();
    }

    public function save(): bool {
        $db = Database::getConnection();
        if ($this->id === null) {
            $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password_hash, role, storage_quota) VALUES (?, ?, ?, ?, ?, ?)");
            $res = $stmt->execute([$this->full_name, $this->username, $this->email, $this->password_hash, $this->role, $this->storage_quota]);
            if ($res) {
                $this->id = (int)$db->lastInsertId();
            }
            return $res;
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, password_hash = ?, role = ?, storage_quota = ? WHERE id = ?");
            return $stmt->execute([$this->full_name, $this->username, $this->email, $this->password_hash, $this->role, $this->storage_quota, $this->id]);
        }
    }

    private static function fromArray(array $data): self {
        $user = new self();
        $user->id = (int)$data['id'];
        $user->full_name = $data['full_name'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->password_hash = $data['password_hash'];
        $user->role = $data['role'];
        $user->storage_quota = (int)$data['storage_quota'];
        $user->created_at = $data['created_at'];
        $user->updated_at = $data['updated_at'];
        return $user;
    }
}
