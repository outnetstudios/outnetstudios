<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::getConnection();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS catalog_admin_access (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_username VARCHAR(100) NOT NULL,
            catalog_id INT NOT NULL,
            catalog_name VARCHAR(255) DEFAULT NULL,
            catalog_user_id INT NOT NULL,
            catalog_user_name VARCHAR(255) DEFAULT NULL,
            action VARCHAR(50) NOT NULL DEFAULT 'access',
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_username (admin_username),
            INDEX idx_catalog_id (catalog_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    echo "[OK] Tabla catalog_admin_access creada o ya existe." . PHP_EOL;

} catch (PDOException $e) {
    die('[ERROR] ' . $e->getMessage() . PHP_EOL);
}
