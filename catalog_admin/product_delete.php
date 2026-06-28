<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/catalog_permissions.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';

catalogRequireLogin();
$userId = (int)catalogGetUserId();
$id = (int)($_GET['id'] ?? 0);

$productRepo = new ProductRepository();
$product = $productRepo->findById($id);

if (!$product || !catalogCanEdit((int)$product['catalog_id'], $userId, PERM_EDIT_PRODUCTS)) {
    header('Location: index.php');
    exit;
}

$catalogId = (int)$product['catalog_id'];
deleteImage($product['main_image'] ?? null);
$productRepo->delete($id);
header('Location: products.php?catalog_id=' . $catalogId);
exit;
