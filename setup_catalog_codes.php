<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::getConnection();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS catalog_codes (
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

    function genCode(PDO $pdo): string {
        $chars = 'BCDFGHJKLMNPQRSTVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $check = $pdo->prepare('SELECT COUNT(*) FROM catalog_codes WHERE code = ?');
            $check->execute([$code]);
        } while ($check->fetchColumn() > 0);
        return $code;
    }

    $catalogs = $pdo->query('SELECT id FROM catalogs ORDER BY id');
    $insert = $pdo->prepare('INSERT IGNORE INTO catalog_codes (catalog_id, code, show_prices) VALUES (?, ?, ?)');
    $count = 0;

    while ($cat = $catalogs->fetch()) {
        $cid = (int)$cat['id'];
        $exists = $pdo->prepare('SELECT COUNT(*) FROM catalog_codes WHERE catalog_id = ?');
        $exists->execute([$cid]);
        if ($exists->fetchColumn() > 0) {
            echo "[SKIP] Catálogo $cid ya tiene códigos." . PHP_EOL;
            continue;
        }
        $insert->execute([$cid, genCode($pdo), 1]);
        $insert->execute([$cid, genCode($pdo), 0]);
        $count++;
        echo "[OK] Catálogo $cid: códigos generados." . PHP_EOL;
    }

    echo "[OK] Proceso completado. $count catálogos actualizados." . PHP_EOL;
} catch (PDOException $e) {
    die('[ERROR] ' . $e->getMessage() . PHP_EOL);
}