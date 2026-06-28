<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/includes/upload_helper.php';

$pdo = Database::getConnection();

$validIds = $pdo->query('SELECT id FROM catalogs')->fetchAll(PDO::FETCH_COLUMN);

if (empty($validIds)) {
    echo "No catalogs exist. Skipping cleanup.\n";
    exit;
}

$validSet = '(' . implode(',', array_map('intval', $validIds)) . ')';

echo "Cleaning orphaned data...\n\n";

// Products: delete images first, then records
$stmt = $pdo->query("SELECT id, main_image, gallery FROM catalog_products WHERE catalog_id NOT IN $validSet");
$products = $stmt->fetchAll();
foreach ($products as $p) {
    deleteImage($p['main_image'] ?? null);
    if (!empty($p['gallery'])) {
        $gallery = json_decode($p['gallery'], true);
        if (is_array($gallery)) foreach ($gallery as $g) deleteImage($g);
    }
}
$pdo->exec("DELETE FROM catalog_products WHERE catalog_id NOT IN $validSet");
echo "  catalog_products: " . count($products) . " deleted\n";

// Pages: delete images, then records
$stmt = $pdo->query("SELECT id, background_image FROM catalog_pages WHERE catalog_id NOT IN $validSet");
$pages = $stmt->fetchAll();
foreach ($pages as $pg) {
    deleteImage($pg['background_image'] ?? null);
}
$pdo->exec("DELETE FROM catalog_pages WHERE catalog_id NOT IN $validSet");
echo "  catalog_pages: " . count($pages) . " deleted\n";

// Categories: delete images, then records
$stmt = $pdo->query("SELECT id, image FROM catalog_categories WHERE catalog_id NOT IN $validSet");
$categories = $stmt->fetchAll();
foreach ($categories as $cat) {
    deleteImage($cat['image'] ?? null);
}
$pdo->exec("DELETE FROM catalog_categories WHERE catalog_id NOT IN $validSet");
echo "  catalog_categories: " . count($categories) . " deleted\n";

// Codes and collaborators (no images to clean)
$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM catalog_codes WHERE catalog_id NOT IN $validSet");
$codes = (int)$stmt->fetch()['cnt'];
$pdo->exec("DELETE FROM catalog_codes WHERE catalog_id NOT IN $validSet");
echo "  catalog_codes: $codes deleted\n";

$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM catalog_collaborators WHERE catalog_id NOT IN $validSet");
$collabs = (int)$stmt->fetch()['cnt'];
$pdo->exec("DELETE FROM catalog_collaborators WHERE catalog_id NOT IN $validSet");
echo "  catalog_collaborators: $collabs deleted\n";

echo "\nDone.\n";
