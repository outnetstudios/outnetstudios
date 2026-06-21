<?php
require_once __DIR__ . '/../Database.php';

class CatalogRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function all(): array
    {
        $query = 'SELECT c.*, u.name AS creator_name, u.email AS creator_email,
                    (SELECT COUNT(*) FROM catalog_pages WHERE catalog_id = c.id) AS page_count,
                    (SELECT COUNT(*) FROM catalog_products WHERE catalog_id = c.id) AS product_count
                  FROM catalogs c
                  LEFT JOIN catalog_users u ON c.user_id = u.id
                  ORDER BY c.created_at DESC';
        $stmt = $this->connection->query($query);
        return $stmt ? $stmt->fetchAll() : [];
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
        $query = 'INSERT INTO catalogs (user_id, name, slug, description, cover_image, back_cover_image, status, currency, created_at) VALUES (:user_id, :name, :slug, :description, :cover_image, :back_cover_image, :status, :currency, NOW())';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':cover_image' => $data['cover_image'] ?? null,
            ':back_cover_image' => $data['back_cover_image'] ?? null,
            ':status' => $data['status'] ?? 'draft',
            ':currency' => $data['currency'] ?? 'NIO',
        ]);
        return (int)$this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = ['name', 'slug', 'description', 'cover_image', 'back_cover_image', 'status', 'currency', 'public_pdf_download'];
        $sets = [];
        $params = [':id' => $id];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $sets[] = "$f = :$f";
                $params[":$f"] = $data[$f];
            }
        }
        if (empty($sets)) return false;
        $sets[] = 'updated_at = NOW()';
        $query = 'UPDATE catalogs SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $query = 'DELETE FROM catalogs WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
}
