<?php

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS sessions (
            session_id VARCHAR(128) NOT NULL PRIMARY KEY,
            data MEDIUMBLOB NOT NULL,
            last_updated INT UNSIGNED NOT NULL,
            INDEX idx_last_updated (last_updated)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare('SELECT data FROM sessions WHERE session_id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $this->pdo->prepare('UPDATE sessions SET last_updated = ? WHERE session_id = ?')
                ->execute([time(), $id]);
            return $row['data'];
        }
        return '';
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (session_id, data, last_updated) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), last_updated = VALUES(last_updated)'
        );
        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE session_id = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $cutoff = time() - $max_lifetime;
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE last_updated < ?');
        $stmt->execute([$cutoff]);
        return $stmt->rowCount();
    }
}
