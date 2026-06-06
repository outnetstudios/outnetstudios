<?php
require_once __DIR__ . '/../Database.php';

class CatalogRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function allByUser(int $userId): array
    {
        $query = 'SELECT * FROM catalogs WHERE user_id = :user_id ORDER BY created_at DESC';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id)
    {
        $query = 'SELECT * FROM catalogs WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $query = 'INSERT INTO catalogs (user_id, name, slug, description, cover_image, back_cover_image, status, created_at) VALUES (:user_id, :name, :slug, :description, :cover_image, :back_cover_image, :status, NOW())';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':cover_image' => $data['cover_image'] ?? null,
            ':back_cover_image' => $data['back_cover_image'] ?? null,
            ':status' => $data['status'] ?? 'draft',
        ]);
        return (int)$this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $query = 'UPDATE catalogs SET name = :name, slug = :slug, description = :description, cover_image = :cover_image, back_cover_image = :back_cover_image, status = :status, updated_at = NOW() WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':cover_image' => $data['cover_image'] ?? null,
            ':back_cover_image' => $data['back_cover_image'] ?? null,
            ':status' => $data['status'] ?? 'draft',
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $query = 'DELETE FROM catalogs WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
}
