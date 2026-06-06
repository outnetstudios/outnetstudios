<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
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

if (!$catalog || (int)$catalog['user_id'] !== $userId) {
    header('Location: index.php');
    exit;
}

deleteImage($catalog['cover_image'] ?? null);
deleteImage($catalog['back_cover_image'] ?? null);

$productRepo = new ProductRepository();
foreach ($productRepo->allByCatalog($id) as $p) {
    deleteImage($p['main_image'] ?? null);
    if (!empty($p['gallery'])) {
        $gallery = json_decode($p['gallery'], true);
        if (is_array($gallery)) foreach ($gallery as $g) deleteImage($g);
    }
}

$pageRepo = new CatalogPageRepository();
foreach ($pageRepo->allByCatalog($id) as $pg) {
    deleteImage($pg['background_image'] ?? null);
}

$categoryRepo = new CategoryRepository();
foreach ($categoryRepo->allByCatalog($id) as $cat) {
    deleteImage($cat['image'] ?? null);
}

$repo->delete($id);
header('Location: index.php');
exit;
