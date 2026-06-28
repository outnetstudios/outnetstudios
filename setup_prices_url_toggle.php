<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::getConnection();
    $pdo->exec("ALTER TABLE catalogs ADD COLUMN prices_url_active TINYINT(1) NOT NULL DEFAULT 1 AFTER public_pdf_download");
    echo "[OK] Columna prices_url_active agregada a catalogs." . PHP_EOL;
} catch (PDOException $e) {
    if ($e->getCode() === '42S21') {
        echo "[OK] Columna prices_url_active ya existe." . PHP_EOL;
    } else {
        die('[ERROR] ' . $e->getMessage() . PHP_EOL);
    }
}