<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::getConnection();

    // 1. Verificar columnas en catalogs
    echo "=== Verificando estructura de catalogs ===" . PHP_EOL;
    $cols = $pdo->query("SHOW COLUMNS FROM catalogs")->fetchAll(PDO::FETCH_COLUMN, 0);
    $required = ['id', 'user_id', 'name', 'slug', 'description', 'cover_image', 'back_cover_image', 'status', 'currency', 'public_pdf_download', 'prices_url_active', 'created_at', 'updated_at'];
    foreach ($required as $col) {
        echo in_array($col, $cols) ? "[OK] $col" : "[FALTA] $col";
        echo PHP_EOL;
    }

    // 2. Recrear catalog_codes desde cero
    echo PHP_EOL . "=== Recreando catalog_codes ===" . PHP_EOL;
    $pdo->exec("DROP TABLE IF EXISTS catalog_codes");
    $pdo->exec("
        CREATE TABLE catalog_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            catalog_id INT NOT NULL,
            code VARCHAR(16) NOT NULL UNIQUE,
            show_prices TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE,
            INDEX idx_code (code),
            INDEX idx_catalog (catalog_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabla catalog_codes creada." . PHP_EOL;

    // Verificar estructura
    $ccCols = $pdo->query("SHOW COLUMNS FROM catalog_codes")->fetchAll(PDO::FETCH_COLUMN, 0);
    $ccRequired = ['id', 'catalog_id', 'code', 'show_prices', 'created_at'];
    foreach ($ccRequired as $col) {
        echo in_array($col, $ccCols) ? "  [OK] $col" : "  [FALTA] $col";
        echo PHP_EOL;
    }

    // 3. Generar códigos para todos los catálogos
    echo PHP_EOL . "=== Generando códigos para catálogos existentes ===" . PHP_EOL;
    $catalogs = $pdo->query('SELECT id FROM catalogs ORDER BY id');
    $insert = $pdo->prepare('INSERT INTO catalog_codes (catalog_id, code, show_prices) VALUES (?, ?, ?)');
    $count = 0;

    while ($cat = $catalogs->fetch()) {
        $cid = (int)$cat['id'];
        $chars = 'BCDFGHJKLMNPQRSTVWXYZ23456789';
        $len = strlen($chars) - 1;

        // Generar código con precios
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, $len)];
            }
            $check = $pdo->prepare('SELECT COUNT(*) FROM catalog_codes WHERE code = ?');
            $check->execute([$code]);
        } while ($check->fetchColumn() > 0);
        $insert->execute([$cid, $code, 1]);

        // Generar código sin precios
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, $len)];
            }
            $check = $pdo->prepare('SELECT COUNT(*) FROM catalog_codes WHERE code = ?');
            $check->execute([$code]);
        } while ($check->fetchColumn() > 0);
        $insert->execute([$cid, $code, 0]);

        $count++;
        echo "[OK] Catálogo $cid: códigos generados." . PHP_EOL;
    }

    echo PHP_EOL . "=== Resumen ===" . PHP_EOL;
    echo "Catálogos procesados: $count" . PHP_EOL;
    $totalCodes = $pdo->query('SELECT COUNT(*) FROM catalog_codes')->fetchColumn();
    echo "Total códigos en tabla: $totalCodes" . PHP_EOL;
    echo "[OK] Proceso completado." . PHP_EOL;

} catch (PDOException $e) {
    die('[ERROR] ' . $e->getMessage() . PHP_EOL);
}