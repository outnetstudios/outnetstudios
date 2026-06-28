<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/includes/upload_helper.php';

$names = ['Restaurante', 'Menu de restaurante'];

$pdo = Database::getConnection();

// Build query: find catalogs where name matches any of the given patterns
$conditions = [];
$params = [];
foreach ($names as $n) {
    $conditions[] = 'name LIKE ?';
    $params[] = "%$n%";
}
$sql = 'SELECT id, name, cover_image, back_cover_image FROM catalogs WHERE ' . implode(' OR ', $conditions);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$catalogs = $stmt->fetchAll();

if (empty($catalogs)) {
    echo "No catalogs found matching the given names.\n";
    exit;
}

$totalImages = 0;

foreach ($catalogs as $catalog) {
    echo "--- Processing: {$catalog['name']} (ID: {$catalog['id']}) ---\n";

    // Collect all image paths for this catalog
    $images = [];

    if ($catalog['cover_image']) $images[] = $catalog['cover_image'];
    if ($catalog['back_cover_image']) $images[] = $catalog['back_cover_image'];

    // Category images
    $catStmt = $pdo->prepare('SELECT image FROM catalog_categories WHERE catalog_id = ?');
    $catStmt->execute([$catalog['id']]);
    foreach ($catStmt->fetchAll() as $row) {
        if ($row['image']) $images[] = $row['image'];
    }

    // Product images
    $prodStmt = $pdo->prepare('SELECT main_image, gallery FROM catalog_products WHERE catalog_id = ?');
    $prodStmt->execute([$catalog['id']]);
    foreach ($prodStmt->fetchAll() as $row) {
        if ($row['main_image']) $images[] = $row['main_image'];
        if ($row['gallery']) {
            $gallery = json_decode($row['gallery'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $g) {
                    if (is_string($g)) $images[] = $g;
                }
            }
        }
    }

    // Page images
    $pageStmt = $pdo->prepare('SELECT background_image FROM catalog_pages WHERE catalog_id = ?');
    $pageStmt->execute([$catalog['id']]);
    foreach ($pageStmt->fetchAll() as $row) {
        if ($row['background_image']) $images[] = $row['background_image'];
    }

    // Delete all image files + DB assets
    $unique = array_unique(array_filter($images));
    foreach ($unique as $imgPath) {
        $full = __DIR__ . '/' . $imgPath;
        if (file_exists($full)) {
            @unlink($full);
        }
        try {
            $pdo->prepare('DELETE FROM catalog_assets WHERE path = ?')->execute([$imgPath]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
    $totalImages += count($unique);
    echo "  Images deleted: " . count($unique) . "\n";

    // Delete related records
    $pdo->prepare('DELETE FROM catalog_codes WHERE catalog_id = ?')->execute([$catalog['id']]);
    echo "  catalog_codes deleted\n";

    $pdo->prepare('DELETE FROM catalog_collaborators WHERE catalog_id = ?')->execute([$catalog['id']]);
    echo "  catalog_collaborators deleted\n";

    $pdo->prepare('DELETE FROM catalog_pages WHERE catalog_id = ?')->execute([$catalog['id']]);
    echo "  catalog_pages deleted\n";

    $pdo->prepare('DELETE FROM catalog_products WHERE catalog_id = ?')->execute([$catalog['id']]);
    echo "  catalog_products deleted\n";

    $pdo->prepare('DELETE FROM catalog_categories WHERE catalog_id = ?')->execute([$catalog['id']]);
    echo "  catalog_categories deleted\n";

    $pdo->prepare('DELETE FROM catalogs WHERE id = ?')->execute([$catalog['id']]);
    echo "  Catalog deleted\n\n";
}

echo "Done. Total catalogs deleted: " . count($catalogs) . ", total images deleted: $totalImages\n";
