<?php
require_once __DIR__ . '/../Database.php';

class UserRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function findByEmail(string $email)
    {
        $query = 'SELECT * FROM admin_users WHERE email = :email LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch();
    }

    public function findById(int $id)
    {
        $query = 'SELECT * FROM admin_users WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($query);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $query = 'UPDATE admin_users SET password = :password WHERE id = :id';
        $stmt = $this->connection->prepare($query);
        return $stmt->execute([':password' => $hashedPassword, ':id' => $id]);
    }
}
