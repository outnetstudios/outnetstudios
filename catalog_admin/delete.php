<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/catalog_permissions.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';

catalogRequireLogin();
$repo = new CatalogRepository();
$userId = (int)catalogGetUserId();
$id = (int)($_GET['id'] ?? 0);
$catalog = $repo->findById($id);

if (!$catalog || !catalogIsOwner($id, $userId)) {
    header('Location: index.php');
    exit;
}

$pdo = $repo->getConnection();

// Delete catalog images
deleteImage($catalog['cover_image'] ?? null);
deleteImage($catalog['back_cover_image'] ?? null);

// Delete product images + records
$productRepo = new ProductRepository();
foreach ($productRepo->allByCatalog($id) as $p) {
    deleteImage($p['main_image'] ?? null);
    if (!empty($p['gallery'])) {
        $gallery = json_decode($p['gallery'], true);
        if (is_array($gallery)) foreach ($gallery as $g) deleteImage($g);
    }
}
$pdo->prepare('DELETE FROM catalog_products WHERE catalog_id = ?')->execute([$id]);

// Delete page images + records
$pageRepo = new CatalogPageRepository();
foreach ($pageRepo->allByCatalog($id) as $pg) {
    deleteImage($pg['background_image'] ?? null);
}
$pdo->prepare('DELETE FROM catalog_pages WHERE catalog_id = ?')->execute([$id]);

// Delete category images + records
$categoryRepo = new CategoryRepository();
foreach ($categoryRepo->allByCatalog($id) as $cat) {
    deleteImage($cat['image'] ?? null);
}
$pdo->prepare('DELETE FROM catalog_categories WHERE catalog_id = ?')->execute([$id]);

// Delete codes and collaborators
$pdo->prepare('DELETE FROM catalog_codes WHERE catalog_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM catalog_collaborators WHERE catalog_id = ?')->execute([$id]);

// Delete catalog
$repo->delete($id);
header('Location: index.php');
exit;
