<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CollaboratorRepository.php';
catalogRequireLogin();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$navbarBackUrl = 'index.php';

// Pure collaborators cannot create catalogs
$repo = new CatalogRepository();
$collabRepo = new CollaboratorRepository();
$userId = (int)catalogGetUserId();
try {
    $owned = $repo->allByUser($userId);
    $collabEntries = $collabRepo->findCatalogsByUser($userId);
    if (count($owned) === 0 && count($collabEntries) > 0) {
        header('Location: index.php');
        exit;
    }
} catch (\Throwable $e) {
    // If tables don't exist, allow creation
}
?>
<?php $pageTitle = 'Nuevo catálogo'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<?php require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<div class="max-w-4xl mx-auto p-margin-mobile md:p-margin-desktop">
<div class="glass-panel rounded-3xl p-md md:p-xl">
<form method="POST" action="save.php" class="space-y-lg" enctype="multipart/form-data">
<div>
<label class="form-label" for="name">Nombre del catálogo</label>
<input type="text" id="name" name="name" required class="form-input" placeholder="Ej: Catálogo Verano 2026">
</div>
<div>
<label class="form-label" for="slug">Slug (opcional)</label>
<input type="text" id="slug" name="slug" class="form-input" placeholder="Se genera automáticamente si se deja vacío">
</div>
<div>
<label class="form-label" for="description">Descripción</label>
<textarea id="description" name="description" rows="3" class="form-input" placeholder="Breve descripción del catálogo"></textarea>
</div>
<div>
<label class="form-label" for="cover_image">Imagen de portada (opcional)</label>
<input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input file:bg-surface-variant/30 file:border-0 file:rounded-full file:px-sm file:py-1 file:text-label-caps file:text-primary file:cursor-pointer">
</div>
<div>
<label class="form-label" for="back_cover_image">Imagen de contraportada (opcional)</label>
<input type="file" id="back_cover_image" name="back_cover_image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input file:bg-surface-variant/30 file:border-0 file:rounded-full file:px-sm file:py-1 file:text-label-caps file:text-primary file:cursor-pointer">
</div>
<div>
<label class="form-label" for="currency">Moneda</label>
<select id="currency" name="currency" class="form-input">
<option value="NIO">Córdobas (C$)</option>
<option value="USD">Dólares ($)</option>
</select>
</div>
<div>
<label class="form-label" for="status">Estado</label>
<select id="status" name="status" class="form-input">
<option value="draft">Borrador</option>
<option value="published">Publicado</option>
</select>
</div>
<div class="flex gap-sm pt-md form-btn-row">
<button class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow" type="submit">Crear catálogo</button>
<a href="index.php" class="px-lg py-sm rounded-full border border-outline-variant font-title-sm text-title-sm text-on-surface-variant hover:bg-surface-variant/50 hover:border-error/30 hover:text-error transition-all no-underline">Cancelar</a>
</div>
</form>
</div>
</div>
</main>
</body>
</html>
