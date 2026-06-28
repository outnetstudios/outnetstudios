<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/catalog_permissions.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';

catalogRequireLogin();
$userId = (int)catalogGetUserId();
$id = (int)($_GET['id'] ?? 0);

$categoryRepo = new CategoryRepository();
$category = $categoryRepo->findById($id);

if (!$category || !catalogCanEdit((int)$category['catalog_id'], $userId, PERM_EDIT_CATEGORIES)) {
    header('Location: index.php');
    exit;
}

$catalogId = (int)$category['catalog_id'];
deleteImage($category['image'] ?? null);
$categoryRepo->delete($id);
header('Location: categories.php?catalog_id=' . $catalogId);
exit;
