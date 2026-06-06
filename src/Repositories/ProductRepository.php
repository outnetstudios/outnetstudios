<?php
require_once __DIR__ . '/../Database.php';

class ProductRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function allByCatalog(int $catalogId): array
    {
        $query = 'SELECT * FROM catalog_products WHERE catalog_id = :catalog_id ORDER BY sort_order ASC, created_at DESC';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        return $stmt->fetchAll();
    }

    public function allByCategory(int $categoryId): array
    {
        $query = 'SELECT * FROM catalog_products WHERE category_id = :category_id ORDER BY sort_order ASC, created_at DESC';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':category_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id)
    {
        $query = 'SELECT * FROM catalog_products WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $query = 'INSERT INTO catalog_products (catalog_id, category_id, user_id, name, sku, description, price, stock, main_image, gallery, status, sort_order, created_at) VALUES (:catalog_id, :category_id, :user_id, :name, :sku, :description, :price, :stock, :main_image, :gallery, :status, :sort_order, NOW())';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([
            ':catalog_id' => $data['catalog_id'],
            ':category_id' => $data['category_id'] ?? 0,
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':sku' => $data['sku'] ?? null,
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'] ?? null,
            ':stock' => $data['stock'] ?? null,
            ':main_image' => $data['main_image'] ?? null,
            ':gallery' => isset($data['gallery']) ? json_encode($data['gallery']) : null,
            ':status' => $data['status'] ?? 'active',
            ':sort_order' => $data['sort_order'] ?? 0,
        ]);
        return (int)$this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $query = 'UPDATE catalog_products SET category_id = :category_id, name = :name, sku = :sku, description = :description, price = :price, stock = :stock, main_image = :main_image, gallery = :gallery, status = :status, sort_order = :sort_order, updated_at = NOW() WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([
            ':category_id' => $data['category_id'] ?? 0,
            ':name' => $data['name'],
            ':sku' => $data['sku'] ?? null,
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'] ?? null,
            ':stock' => $data['stock'] ?? null,
            ':main_image' => $data['main_image'] ?? null,
            ':gallery' => isset($data['gallery']) ? json_encode($data['gallery']) : null,
            ':status' => $data['status'] ?? 'active',
            ':sort_order' => $data['sort_order'] ?? 0,
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $query = 'DELETE FROM catalog_products WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    public function getMaxSortOrder(int $catalogId): int
    {
        $query = 'SELECT COALESCE(MAX(sort_order), -1) AS max_order FROM catalog_products WHERE catalog_id = :catalog_id';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        $row = $stmt->fetch();
        return (int)($row['max_order'] ?? -1);
    }

    public function countByCategory(int $categoryId): int
    {
        $query = 'SELECT COUNT(*) AS cnt FROM catalog_products WHERE category_id = :category_id';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':category_id' => $categoryId]);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }
}
