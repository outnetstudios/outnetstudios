<?php
require_once __DIR__ . '/../Database.php';

class CatalogRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function getConnection(): PDO
    {
        return $this->connection;
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

    public function allAccessibleByUser(int $userId): array
    {
        try {
            $query = "SELECT c.*, 'owner' AS _access_level FROM catalogs c WHERE c.user_id = :user_id
                      UNION
                      SELECT c.*, 'collaborator' AS _access_level
                      FROM catalog_collaborators cc
                      JOIN catalogs c ON cc.catalog_id = c.id
                      WHERE cc.user_id = :user_id2
                      ORDER BY created_at DESC";
            $stmt = $this->connection->prepare($query);
            $stmt->execute([':user_id' => $userId, ':user_id2' => $userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            $query = 'SELECT c.*, \'owner\' AS _access_level FROM catalogs c WHERE c.user_id = :user_id ORDER BY created_at DESC';
            $stmt = $this->connection->prepare($query);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll();
        }
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
        $fields = ['name', 'slug', 'description', 'cover_image', 'back_cover_image', 'status', 'currency', 'public_pdf_download', 'prices_url_active'];
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

    public function findCatalogByCode(string $code)
    {
        $query = 'SELECT c.*, cc.show_prices, cc.code
                  FROM catalog_codes cc
                  JOIN catalogs c ON cc.catalog_id = c.id
                  WHERE cc.code = :code LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':code' => $code]);
        return $stmt->fetch();
    }

    public function findCodesByCatalog(int $catalogId): array
    {
        $query = 'SELECT code, show_prices FROM catalog_codes WHERE catalog_id = :catalog_id';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':catalog_id' => $catalogId]);
        return $stmt->fetchAll();
    }

    public function createCatalogCode(int $catalogId, string $code, bool $showPrices): bool
    {
        $query = 'INSERT IGNORE INTO catalog_codes (catalog_id, code, show_prices) VALUES (:catalog_id, :code, :show_prices)';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([
            ':catalog_id' => $catalogId,
            ':code' => $code,
            ':show_prices' => $showPrices ? 1 : 0,
        ]);
    }

    public function regenerateCatalogCodes(int $catalogId): void
    {
        require_once __DIR__ . '/../../includes/catalog_preview_renderer.php';
        $this->connection->prepare('DELETE FROM catalog_codes WHERE catalog_id = ?')->execute([$catalogId]);
        $this->createCatalogCode($catalogId, generatePublicCode(), true);
        $this->createCatalogCode($catalogId, generatePublicCode(), false);
    }
}
