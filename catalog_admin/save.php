<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';

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

if ($slug === '') {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $slug = trim($slug, '-');
}

$coverImage = uploadImage($_FILES['cover_image'] ?? []);
$backCoverImage = uploadImage($_FILES['back_cover_image'] ?? []);

$repo = new CatalogRepository();
$id = $repo->create([
    'user_id' => $userId,
    'name' => $name,
    'slug' => $slug,
    'description' => $description,
    'cover_image' => $coverImage,
    'back_cover_image' => $backCoverImage,
    'status' => $status,
]);

if ($id) {
    header('Location: index.php');
    exit;
}

echo "Error al crear catálogo";