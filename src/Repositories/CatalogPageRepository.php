<?php
require_once __DIR__ . '/../Database.php';

class CatalogPageRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function allByCatalog(int $catalogId): array
    {
        $query = 'SELECT * FROM catalog_pages WHERE catalog_id = :catalog_id ORDER BY sort_order ASC, created_at ASC';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id)
    {
        $query = 'SELECT * FROM catalog_pages WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $query = 'INSERT INTO catalog_pages (catalog_id, user_id, page_type, title, content_json, background_image, sort_order, created_at) VALUES (:catalog_id, :user_id, :page_type, :title, :content_json, :background_image, :sort_order, NOW())';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([
            ':catalog_id' => $data['catalog_id'],
            ':user_id' => $data['user_id'],
            ':page_type' => $data['page_type'],
            ':title' => $data['title'] ?? null,
            ':content_json' => isset($data['content']) ? json_encode($data['content']) : null,
            ':background_image' => $data['background_image'] ?? null,
            ':sort_order' => $data['sort_order'] ?? 0,
        ]);
        return (int)$this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach (['page_type', 'title', 'background_image', 'sort_order'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }
        if (array_key_exists('content', $data)) {
            $fields[] = 'content_json = :content_json';
            $params[':content_json'] = json_encode($data['content']);
        }
        if (empty($fields)) return false;
        $fields[] = 'updated_at = NOW()';
        $query = 'UPDATE catalog_pages SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $query = 'DELETE FROM catalog_pages WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    public function getMaxSortOrder(int $catalogId): int
    {
        $query = 'SELECT COALESCE(MAX(sort_order), -1) AS max_order FROM catalog_pages WHERE catalog_id = :catalog_id';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        $row = $stmt->fetch();
        return (int)($row['max_order'] ?? -1);
    }

    public function countByCatalog(int $catalogId): int
    {
        $query = 'SELECT COUNT(*) AS cnt FROM catalog_pages WHERE catalog_id = :catalog_id';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }
}
