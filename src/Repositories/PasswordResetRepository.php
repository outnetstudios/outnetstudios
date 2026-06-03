<?php
require_once __DIR__ . '/../Database.php';

class PasswordResetRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function create(int $userId, string $token, string $expiresAt): bool
    {
        $query = 'INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (:user_id, :token, :expires_at, NOW())';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([
            ':user_id' => $userId,
            ':token' => $token,
            ':expires_at' => $expiresAt,
        ]);
    }

    public function findValidToken(string $token)
    {
        $query = 'SELECT * FROM password_resets WHERE token = :token AND expires_at >= NOW() LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    public function deleteToken(string $token): bool
    {
        $query = 'DELETE FROM password_resets WHERE token = :token';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':token' => $token]);
    }
}
