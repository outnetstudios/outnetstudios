<?php
require_once __DIR__ . '/src/Database.php';
try {
    $db = Database::getConnection();
    $db->exec("ALTER TABLE catalogs ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'NIO'");
    echo "OK: columna 'currency' agregada a catalogs.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
