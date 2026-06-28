<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/catalog_permissions.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';

catalogRequireLogin();
$userId = (int)catalogGetUserId();

$catalogId = (int)($_POST['catalog_id'] ?? 0);
$redirect = $_POST['redirect'] ?? 'index.php';

$repo = new CatalogRepository();
$catalog = $repo->findById($catalogId);
if (!$catalog || !catalogCanEdit($catalogId, $userId, PERM_EDIT_CATALOG)) {
    header('Location: index.php');
    exit;
}

$publicPdfDownload = !empty($_POST['public_pdf_download']) ? 1 : 0;
$repo->update($catalogId, ['public_pdf_download' => $publicPdfDownload]);

header('Location: ' . $redirect);
exit;
