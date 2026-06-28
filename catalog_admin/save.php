<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CollaboratorRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';
require_once __DIR__ . '/../includes/catalog_preview_renderer.php';

catalogRequireLogin();
$userId = (int)catalogGetUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = in_array($_POST['status'] ?? 'draft', ['draft','published']) ? $_POST['status'] : 'draft';
$currency = $_POST['currency'] === 'USD' ? 'USD' : 'NIO';

if ($slug === '') {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $slug = trim($slug, '-');
}

$coverImage = uploadImage($_FILES['cover_image'] ?? []);
$backCoverImage = uploadImage($_FILES['back_cover_image'] ?? []);

$repo = new CatalogRepository();
$collabRepo = new CollaboratorRepository();

// Determine the effective owner: if the creator is a "pure collaborator"
// (no owned catalogs), assign the catalog to the admin who added them.
$ownedCatalogs = $repo->allByUser($userId);
$collabEntries = $collabRepo->findCatalogsByUser($userId);
$assignUserId = $userId;
$isPureCollaborator = count($ownedCatalogs) === 0 && count($collabEntries) > 0;

if ($isPureCollaborator && count($collabEntries) > 0) {
    // Find the admin who owns the catalog of the most recent collaborator entry
    $collab = $collabRepo->findMostRecentByUser($userId);
    if ($collab) {
        $catalog = $repo->findById((int)$collab['catalog_id']);
        if ($catalog) {
            $assignUserId = (int)$catalog['user_id'];
        }
    }
}

$id = $repo->create([
    'user_id' => $assignUserId,
    'name' => $name,
    'slug' => $slug,
    'description' => $description,
    'cover_image' => $coverImage,
    'back_cover_image' => $backCoverImage,
    'status' => $status,
    'currency' => $currency,
]);

if ($id) {
    $repo->createCatalogCode($id, generatePublicCode(), true);
    $repo->createCatalogCode($id, generatePublicCode(), false);

    // If the catalog was created for an admin, add the creator as collaborator
    if ($isPureCollaborator && $assignUserId !== $userId) {
        $collabRepo->create($id, $userId, [
            PERM_EDIT_CATALOG => true,
            PERM_EDIT_PAGES => true,
            PERM_EDIT_PRODUCTS => true,
            PERM_EDIT_CATEGORIES => true,
        ]);
    }

    header('Location: index.php');
    exit;
}

echo "Error al crear catálogo";