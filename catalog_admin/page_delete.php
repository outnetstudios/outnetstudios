<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';

catalogRequireLogin();
$userId = (int)catalogGetUserId();
$id = (int)($_GET['id'] ?? 0);

$pageRepo = new CatalogPageRepository();
$page = $pageRepo->findById($id);

if (!$page || (int)$page['user_id'] !== $userId) {
    header('Location: index.php');
    exit;
}

$catalogId = (int)$page['catalog_id'];
deleteImage($page['background_image'] ?? null);
$pageRepo->delete($id);
header('Location: pages.php?catalog_id=' . $catalogId);
exit;
