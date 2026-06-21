<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::getConnection();
    $pdo->exec("ALTER TABLE catalogs ADD COLUMN public_pdf_download TINYINT(1) NOT NULL DEFAULT 1 AFTER currency");
    echo "[OK] Columna public_pdf_download agregada a catalogs." . PHP_EOL;
} catch (PDOException $e) {
    if ($e->getCode() === '42S21') {
        echo "[OK] Columna public_pdf_download ya existe." . PHP_EOL;
    } else {
        die('[ERROR] ' . $e->getMessage() . PHP_EOL);
    }
}
