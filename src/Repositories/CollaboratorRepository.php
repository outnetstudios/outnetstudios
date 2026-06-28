<?php
require_once __DIR__ . '/../Database.php';

class CollaboratorRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function findByCatalog(int $catalogId): array
    {
        $query = 'SELECT cc.*, u.name AS user_name, u.email AS user_email
                  FROM catalog_collaborators cc
                  JOIN catalog_users u ON cc.user_id = u.id
                  WHERE cc.catalog_id = :catalog_id
                  ORDER BY u.name';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        return $stmt->fetchAll();
    }

    public function findByUserAndCatalog(int $userId, int $catalogId)
    {
        $query = 'SELECT * FROM catalog_collaborators WHERE user_id = :user_id AND catalog_id = :catalog_id LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':user_id' => $userId, ':catalog_id' => $catalogId]);
        return $stmt->fetch();
    }

    public function create(int $catalogId, int $userId, array $permissions): bool
    {
        $query = 'INSERT INTO catalog_collaborators (catalog_id, user_id, permissions) VALUES (:catalog_id, :user_id, :permissions)';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([
            ':catalog_id' => $catalogId,
            ':user_id' => $userId,
            ':permissions' => json_encode($permissions),
        ]);
    }

    public function updatePermissions(int $id, array $permissions): bool
    {
        $query = 'UPDATE catalog_collaborators SET permissions = :permissions WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([
            ':permissions' => json_encode($permissions),
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $query = 'DELETE FROM catalog_collaborators WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    public function deleteByUserAndCatalog(int $userId, int $catalogId): bool
    {
        $query = 'DELETE FROM catalog_collaborators WHERE user_id = :user_id AND catalog_id = :catalog_id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':user_id' => $userId, ':catalog_id' => $catalogId]);
    }

    public function deleteByUser(int $userId): bool
    {
        $query = 'DELETE FROM catalog_collaborators WHERE user_id = :user_id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':user_id' => $userId]);
    }

    public function findCatalogsByUser(int $userId): array
    {
        $query = 'SELECT c.*, cc.permissions, cc.id AS collab_id
                  FROM catalog_collaborators cc
                  JOIN catalogs c ON cc.catalog_id = c.id
                  WHERE cc.user_id = :user_id
                  ORDER BY c.created_at DESC';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findMostRecentByUser(int $userId)
    {
        $query = 'SELECT cc.* FROM catalog_collaborators cc WHERE cc.user_id = :user_id ORDER BY cc.id DESC LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }
}