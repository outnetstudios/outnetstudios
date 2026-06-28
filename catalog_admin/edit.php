<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';

catalogRequireLogin();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$navbarBackUrl = 'index.php';

$repo = new CatalogRepository();
$userId = (int)catalogGetUserId();
$id = (int)($_GET['id'] ?? 0);
$catalog = $repo->findById($id);

if (!$catalog || (int)$catalog['user_id'] !== $userId) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = in_array($_POST['status'] ?? 'draft', ['draft','published','archived']) ? $_POST['status'] : 'draft';
    $currency = $_POST['currency'] === 'USD' ? 'USD' : 'NIO';

    if ($name === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        if ($slug === '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
        }
        $coverImage = $catalog['cover_image'] ?? null;
        $newCover = uploadImage($_FILES['cover_image'] ?? []);
        if ($newCover) { deleteImage($catalog['cover_image'] ?? null); $coverImage = $newCover; }
        $backCoverImage = $catalog['back_cover_image'] ?? null;
        $newBack = uploadImage($_FILES['back_cover_image'] ?? []);
        if ($newBack) { deleteImage($catalog['back_cover_image'] ?? null); $backCoverImage = $newBack; }
        $repo->update($id, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'status' => $status,
            'currency' => $currency,
            'cover_image' => $coverImage,
            'back_cover_image' => $backCoverImage,
            'public_pdf_download' => !empty($_POST['public_pdf_download']) ? 1 : 0,
        ]);
        header('Location: index.php');
        exit;
    }
}
?>
<?php $pageTitle = 'Editar catálogo'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<?php require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<div class="max-w-4xl mx-auto p-margin-mobile md:p-margin-desktop">
<div class="glass-panel rounded-3xl p-md md:p-xl">
<?php if ($error !== ''): ?>
<div class="bg-error-container/20 border border-error/30 rounded-xl px-md py-sm mb-md">
<p class="font-body-sm text-body-sm text-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>
<form method="post" class="space-y-lg" enctype="multipart/form-data">
<div>
<label class="form-label" for="name">Nombre</label>
<input type="text" id="name" name="name" required class="form-input" value="<?= htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div>
<label class="form-label" for="slug">Slug (opcional)</label>
<input type="text" id="slug" name="slug" class="form-input" value="<?= htmlspecialchars($catalog['slug'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div>
<label class="form-label" for="description">Descripción</label>
<textarea id="description" name="description" rows="3" class="form-input"><?= htmlspecialchars($catalog['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
</div>
<div>
<label class="form-label" for="cover_image">Imagen de portada</label>
<input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input file:bg-surface-variant/30 file:border-0 file:rounded-full file:px-sm file:py-1 file:text-label-caps file:text-primary file:cursor-pointer">
<?php if (!empty($catalog['cover_image'])): ?>
<div class="flex items-center gap-sm mt-xs">
<img src="<?= htmlspecialchars(imageUrl($catalog['cover_image']), ENT_QUOTES, 'UTF-8') ?>" class="w-14 h-14 rounded-lg object-cover border border-outline-variant/30">
<span class="text-body-sm text-on-surface-variant/60">Imagen actual</span>
</div>
<?php endif; ?>
</div>
<div>
<label class="form-label" for="back_cover_image">Imagen de contraportada</label>
<input type="file" id="back_cover_image" name="back_cover_image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input file:bg-surface-variant/30 file:border-0 file:rounded-full file:px-sm file:py-1 file:text-label-caps file:text-primary file:cursor-pointer">
<?php if (!empty($catalog['back_cover_image'])): ?>
<div class="flex items-center gap-sm mt-xs">
<img src="<?= htmlspecialchars(imageUrl($catalog['back_cover_image']), ENT_QUOTES, 'UTF-8') ?>" class="w-14 h-14 rounded-lg object-cover border border-outline-variant/30">
<span class="text-body-sm text-on-surface-variant/60">Imagen actual</span>
</div>
<?php endif; ?>
</div>
<div>
<label class="form-label" for="currency">Moneda</label>
<select id="currency" name="currency" class="form-input">
<option value="NIO" <?= ($catalog['currency'] ?? 'NIO') === 'NIO' ? 'selected' : '' ?>>Córdobas (C$)</option>
<option value="USD" <?= ($catalog['currency'] ?? 'NIO') === 'USD' ? 'selected' : '' ?>>Dólares estadounidenses ($)</option>
</select>
</div>
<div>
<label class="form-label" for="status">Estado</label>
<select id="status" name="status" class="form-input">
<option value="draft" <?= $catalog['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
<option value="published" <?= $catalog['status'] === 'published' ? 'selected' : '' ?>>Publicado</option>
<option value="archived" <?= $catalog['status'] === 'archived' ? 'selected' : '' ?>>Archivado</option>
</select>
</div>
<div class="flex items-center justify-between flex-wrap gap-sm pt-md border-t border-outline-variant/20">
<div class="flex items-center gap-2">
    <span class="material-symbols-outlined text-primary text-[18px]">file_download</span>
    <span class="font-body-sm text-body-sm text-on-surface-variant">Permitir descarga de PDF en vista pública</span>
</div>
<label class="relative inline-flex items-center cursor-pointer">
    <input type="checkbox" name="public_pdf_download" value="1" <?= !empty($catalog['public_pdf_download']) ? 'checked' : '' ?> class="sr-only peer">
    <div class="w-11 h-6 bg-surface-variant/30 rounded-full peer peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
</label>
</div>
<div class="flex gap-sm form-btn-row">
<button class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow" type="submit">Guardar cambios</button>
<a href="index.php" class="px-lg py-sm rounded-full border border-outline-variant font-title-sm text-title-sm text-on-surface-variant hover:bg-surface-variant/50 hover:border-error/30 hover:text-error transition-all no-underline">Cancelar</a>
</div>
</form>
</div>
</div>
</main>
</body>
</html>
