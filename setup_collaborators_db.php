<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::getConnection();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS catalog_collaborators (
            id INT AUTO_INCREMENT PRIMARY KEY,
            catalog_id INT NOT NULL,
            user_id INT NOT NULL,
            permissions JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_collab (catalog_id, user_id),
            INDEX idx_catalog (catalog_id),
            INDEX idx_user (user_id),
            FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES catalog_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabla catalog_collaborators creada." . PHP_EOL;
} catch (PDOException $e) {
    if ($e->getCode() === '42S01') {
        echo "[OK] Tabla catalog_collaborators ya existe." . PHP_EOL;
    } else {
        die('[ERROR] ' . $e->getMessage() . PHP_EOL);
    }
}