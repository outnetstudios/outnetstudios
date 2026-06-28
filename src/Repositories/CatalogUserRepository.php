<?php
require_once __DIR__ . '/../Database.php';

class CatalogUserRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function create(array $data): int
    {
        $query = 'INSERT INTO catalog_users
            (name, email, password, must_change_password, temp_password_expires_at, status, created_by)
            VALUES
            (:name, :email, :password, :must_change_password, :temp_password_expires_at, :status, :created_by)';

        $stmt = $this->connection->prepare($query);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':must_change_password' => $data['must_change_password'] ?? 1,
            ':temp_password_expires_at' => $data['temp_password_expires_at'] ?? null,
            ':status' => $data['status'] ?? 'active',
            ':created_by' => $data['created_by'] ?? null,
        ]);

        return (int)$this->connection->lastInsertId();
    }

    public function all(): array
    {
        $query = 'SELECT id, name, email, must_change_password, temp_password_expires_at, status, created_by, created_at, updated_at
            FROM catalog_users
            ORDER BY created_at DESC';

        $stmt = $this->connection->query($query);

        return $stmt ? $stmt->fetchAll() : [];
    }

    public function findByCreatedBy(int $createdBy): array
    {
        $query = 'SELECT id, name, email, status, created_at FROM catalog_users WHERE created_by = :created_by ORDER BY created_at DESC';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':created_by' => $createdBy]);
        return $stmt->fetchAll();
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT id, name, email, status, created_at FROM catalog_users WHERE id IN ($placeholders) ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll();
    }

    public function findByEmail(string $email)
    {
        $query = 'SELECT * FROM catalog_users WHERE email = :email LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch();
    }

    public function findById(int $id)
    {
        $query = 'SELECT * FROM catalog_users WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $query = 'UPDATE catalog_users SET password = :password, must_change_password = 0 WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':password' => $hashedPassword, ':id' => $id]);
    }

    public function createPasswordReset(int $userId, string $token, string $expiresAt): bool
    {
        $query = 'INSERT INTO catalog_password_resets (user_id, token, expires_at, created_at) VALUES (:user_id, :token, :expires_at, NOW())';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([
            ':user_id' => $userId,
            ':token' => $token,
            ':expires_at' => $expiresAt,
        ]);
    }

    public function findValidToken(string $token)
    {
        $query = 'SELECT * FROM catalog_password_resets WHERE token = :token AND expires_at >= NOW() LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    public function deleteToken(string $token): bool
    {
        $query = 'DELETE FROM catalog_password_resets WHERE token = :token';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':token' => $token]);
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach (['name', 'password', 'must_change_password', 'temp_password_expires_at', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $query = 'UPDATE catalog_users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }

    public function setStatus(int $id, string $status): bool
    {
        $query = 'UPDATE catalog_users SET status = :status WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $query = 'DELETE FROM catalog_users WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
}
