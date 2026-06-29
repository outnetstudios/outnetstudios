<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::getConnection();
    $pdo->exec("ALTER TABLE catalog_users ADD COLUMN password_encrypted TEXT NULL AFTER password");
    echo "[OK] Columna password_encrypted agregada a catalog_users." . PHP_EOL;
} catch (PDOException $e) {
    if ($e->getCode() === '42S21') {
        echo "[OK] Columna password_encrypted ya existe en catalog_users." . PHP_EOL;
    } else {
        die('[ERROR] ' . $e->getMessage() . PHP_EOL);
    }
}
