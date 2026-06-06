<?php
require_once __DIR__ . '/../Database.php';

class CategoryRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function allByCatalog(int $catalogId): array
    {
        $query = 'SELECT * FROM catalog_categories WHERE catalog_id = :catalog_id ORDER BY sort_order ASC, created_at DESC';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id)
    {
        $query = 'SELECT * FROM catalog_categories WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $query = 'INSERT INTO catalog_categories (catalog_id, user_id, name, description, image, sort_order, status, created_at) VALUES (:catalog_id, :user_id, :name, :description, :image, :sort_order, :status, NOW())';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([
            ':catalog_id' => $data['catalog_id'],
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':image' => $data['image'] ?? null,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':status' => $data['status'] ?? 'active',
        ]);
        return (int)$this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $query = 'UPDATE catalog_categories SET name = :name, description = :description, image = :image, sort_order = :sort_order, status = :status, updated_at = NOW() WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':image' => $data['image'] ?? null,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':status' => $data['status'] ?? 'active',
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $query = 'DELETE FROM catalog_categories WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    public function getMaxSortOrder(int $catalogId): int
    {
        $query = 'SELECT COALESCE(MAX(sort_order), -1) AS max_order FROM catalog_categories WHERE catalog_id = :catalog_id';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        return (int)$stmt->fetchColumn();
    }
}
