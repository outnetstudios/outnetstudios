<?php
require_once __DIR__ . '/src/Database.php';

$pdo = Database::getConnection();

// Get all valid catalog IDs
$validIds = $pdo->query('SELECT id FROM catalogs')->fetchAll(PDO::FETCH_COLUMN);
$validSet = '(' . implode(',', array_map('intval', $validIds)) . ')';
$countValid = count($validIds);

echo "=== CATALOGOS ACTIVOS ($countValid) ===\n";
foreach ($validIds as $vid) {
    $c = $pdo->query("SELECT id, name, status FROM catalogs WHERE id = $vid")->fetch();
    echo "  ID $vid: {$c['name']} ({$c['status']})\n";
}

echo "\n=== DATOS HUERFANOS ===\n";

$tables = [
    'catalog_codes'        => ['id', 'catalog_id'],
    'catalog_collaborators'=> ['id', 'catalog_id'],
    'catalog_pages'        => ['id', 'catalog_id'],
    'catalog_products'     => ['id', 'catalog_id'],
    'catalog_categories'   => ['id', 'catalog_id'],
];

$totalOrphaned = 0;

foreach ($tables as $table => $cols) {
    $idCol = $cols[0];
    $fkCol = $cols[1];

    if (empty($validIds)) {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM $table");
        $cnt = (int)$stmt->fetch()['cnt'];
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM $table WHERE $fkCol NOT IN $validSet");
        $cnt = (int)$stmt->fetch()['cnt'];
    }

    if ($cnt > 0) {
        if (empty($validIds)) {
            $rows = $pdo->query("SELECT $idCol AS id, $fkCol AS fk FROM $table LIMIT 50")->fetchAll();
        } else {
            $rows = $pdo->query("SELECT $idCol AS id, $fkCol AS fk FROM $table WHERE $fkCol NOT IN $validSet LIMIT 50")->fetchAll();
        }
        echo "  $table: $cnt orphaned record(s)\n";
        foreach ($rows as $r) {
            echo "    ID {$r['id']} -> catalog_id {$r['fk']}\n";
        }
        $totalOrphaned += $cnt;
    } else {
        echo "  $table: 0 orphaned\n";
    }
}

echo "\nTotal orphaned records: $totalOrphaned\n";
