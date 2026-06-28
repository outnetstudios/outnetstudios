<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/catalog_permissions.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CollaboratorRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';

catalogRequireLogin();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$navbarBackUrl = 'index.php';

$repo = new CatalogRepository();
$userId = (int)catalogGetUserId();
$id = (int)($_GET['id'] ?? 0);
$catalog = $repo->findById($id);

if (!$catalog || !catalogCanEdit($id, $userId, PERM_EDIT_CATALOG)) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['regenerate_codes'])) {
        $repo->regenerateCatalogCodes($id);
        $success = 'Códigos públicos regenerados. Las URLs anteriores han dejado de funcionar.';
    } elseif (!empty($_POST['add_collaborator'])) {
        $collabRepo = new CollaboratorRepository();
        $email = trim($_POST['collab_email'] ?? '');
        if ($email === '') {
            $error = 'Ingresa un email de usuario.';
        } else {
            $userRepo = new \CatalogUserRepository();
            $user = $userRepo->findByEmail($email);
            if (!$user) {
                $error = 'No se encontró un usuario con ese email.';
            } elseif ((int)$user['id'] === $userId) {
                $error = 'No puedes agregarte a ti mismo como colaborador.';
            } else {
                $existing = $collabRepo->findByUserAndCatalog((int)$user['id'], $id);
                if ($existing) {
                    $error = 'Ese usuario ya es colaborador de este catálogo.';
                } else {
                    $perms = [
                        PERM_EDIT_CATALOG => !empty($_POST['perm_edit_catalog']),
                        PERM_EDIT_PAGES => !empty($_POST['perm_edit_pages']),
                        PERM_EDIT_PRODUCTS => !empty($_POST['perm_edit_products']),
                        PERM_EDIT_CATEGORIES => !empty($_POST['perm_edit_categories']),
                    ];
                    $collabRepo->create($id, (int)$user['id'], $perms);
                    $success = 'Colaborador agregado.';
                }
            }
        }
    } elseif (!empty($_POST['remove_collaborator'])) {
        $collabRepo = new CollaboratorRepository();
        $collabId = (int)($_POST['collab_id'] ?? 0);
        $collabRepo->delete($collabId);
        $success = 'Colaborador eliminado.';
    } elseif (!empty($_POST['update_collab_perms'])) {
        $collabRepo = new CollaboratorRepository();
        $collabId = (int)($_POST['collab_id'] ?? 0);
        $perms = [
            PERM_EDIT_CATALOG => !empty($_POST['perm_edit_catalog']),
            PERM_EDIT_PAGES => !empty($_POST['perm_edit_pages']),
            PERM_EDIT_PRODUCTS => !empty($_POST['perm_edit_products']),
            PERM_EDIT_CATEGORIES => !empty($_POST['perm_edit_categories']),
        ];
        $collabRepo->updatePermissions($collabId, $perms);
        $success = 'Permisos actualizados.';
    } else {
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
                'prices_url_active' => !empty($_POST['prices_url_active']) ? 1 : 0,
            ]);
            header('Location: index.php');
            exit;
        }
    }
}

// Load codes for display
$codes = $repo->findCodesByCatalog($id);
$publicCodePrices = '';
$publicCodeNoPrices = '';
foreach ($codes as $cc) {
    if ((int)$cc['show_prices'] === 1) $publicCodePrices = $cc['code'];
    else $publicCodeNoPrices = $cc['code'];
}

// Load collaborators
$collabRepo = new CollaboratorRepository();
$collaborators = $collabRepo->findByCatalog($id);
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
<?php if ($success !== ''): ?>
<div class="bg-tertiary/20 border border-tertiary/30 rounded-xl px-md py-sm mb-md">
<p class="font-body-sm text-body-sm text-tertiary"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
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
<div class="flex flex-col gap-sm pt-md border-t border-outline-variant/20">
<div class="flex items-center justify-between flex-wrap gap-sm">
<div class="flex items-center gap-2">
    <span class="material-symbols-outlined text-primary text-[18px]">public</span>
    <span class="font-body-sm text-body-sm text-on-surface-variant">Códigos públicos</span>
</div>
</div>
<div class="flex flex-col gap-1">
    <span class="font-body-xs text-body-xs text-on-surface-variant/60">URL con precios: <code class="text-primary text-[0.7rem]">ver_catalogo.php?c=<?= htmlspecialchars($publicCodePrices, ENT_QUOTES, 'UTF-8') ?></code></span>
    <span class="font-body-xs text-body-xs text-on-surface-variant/60">URL sin precios: <code class="text-primary text-[0.7rem]">ver_catalogo.php?c=<?= htmlspecialchars($publicCodeNoPrices, ENT_QUOTES, 'UTF-8') ?></code></span>
</div>
<div class="flex items-center justify-between flex-wrap gap-sm pt-sm">
<div class="flex items-center gap-2">
    <span class="material-symbols-outlined text-[18px] <?= !empty($catalog['prices_url_active']) ? 'text-primary' : 'text-on-surface-variant/40' ?>">attach_money</span>
    <span class="font-body-sm text-body-sm text-on-surface-variant">URL con precios activa</span>
</div>
<label class="relative inline-flex items-center cursor-pointer">
    <input type="checkbox" name="prices_url_active" value="1" <?= !empty($catalog['prices_url_active']) ? 'checked' : '' ?> class="sr-only peer">
    <div class="w-11 h-6 bg-surface-variant/30 rounded-full peer peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
</label>
</div>
<button type="submit" name="regenerate_codes" value="1" class="self-start px-md py-1 rounded-full border border-outline-variant font-label-caps text-label-caps text-on-surface-variant hover:bg-warning/10 hover:text-warning hover:border-warning/30 transition-all">Regenerar códigos</button>
</div>
<div class="flex gap-sm form-btn-row">
<button class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow" type="submit">Guardar cambios</button>
<a href="index.php" class="px-lg py-sm rounded-full border border-outline-variant font-title-sm text-title-sm text-on-surface-variant hover:bg-surface-variant/50 hover:border-error/30 hover:text-error transition-all no-underline">Cancelar</a>
</div>
</form>

<?php if (catalogIsOwner($id, $userId)): ?>
<div class="mt-xl pt-xl border-t border-outline-variant/20">
<h3 class="font-title-sm text-title-sm text-on-surface mb-md flex items-center gap-2">
    <span class="material-symbols-outlined text-primary text-[18px]">group</span>
    Colaboradores
</h3>

<?php if ($success): ?>
<div class="bg-success-container/20 border border-success/30 rounded-xl px-md py-sm mb-md">
<p class="font-body-sm text-body-sm text-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="bg-error-container/20 border border-error/30 rounded-xl px-md py-sm mb-md">
<p class="font-body-sm text-body-sm text-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>

<?php if (count($collaborators) > 0): ?>
<div class="flex flex-col gap-sm mb-md">
<?php foreach ($collaborators as $collab): ?>
<div class="flex flex-col md:flex-row md:items-center justify-between gap-sm p-sm rounded-xl bg-surface-variant/10 border border-outline-variant/20">
    <div class="flex items-center gap-2 min-w-0">
        <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px]">person</span>
        <span class="font-body-sm text-body-sm text-on-surface truncate"><?= htmlspecialchars($collab['user_email'] ?? "Usuario #{$collab['user_id']}", ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <form method="POST" class="flex flex-wrap items-center gap-x-md gap-y-1">
        <input type="hidden" name="collab_id" value="<?= (int)$collab['id'] ?>">
        <?php $perms = is_string($collab['permissions']) ? json_decode($collab['permissions'], true) : ($collab['permissions'] ?? []); ?>
        <label class="flex items-center gap-1 cursor-pointer">
            <input type="checkbox" name="perm_edit_catalog" value="1" <?= !empty($perms[PERM_EDIT_CATALOG]) ? 'checked' : '' ?> class="accent-primary">
            <span class="font-body-xs text-body-xs text-on-surface-variant">Catálogo</span>
        </label>
        <label class="flex items-center gap-1 cursor-pointer">
            <input type="checkbox" name="perm_edit_pages" value="1" <?= !empty($perms[PERM_EDIT_PAGES]) ? 'checked' : '' ?> class="accent-primary">
            <span class="font-body-xs text-body-xs text-on-surface-variant">Páginas</span>
        </label>
        <label class="flex items-center gap-1 cursor-pointer">
            <input type="checkbox" name="perm_edit_products" value="1" <?= !empty($perms[PERM_EDIT_PRODUCTS]) ? 'checked' : '' ?> class="accent-primary">
            <span class="font-body-xs text-body-xs text-on-surface-variant">Productos</span>
        </label>
        <label class="flex items-center gap-1 cursor-pointer">
            <input type="checkbox" name="perm_edit_categories" value="1" <?= !empty($perms[PERM_EDIT_CATEGORIES]) ? 'checked' : '' ?> class="accent-primary">
            <span class="font-body-xs text-body-xs text-on-surface-variant">Categorías</span>
        </label>
        <div class="flex gap-1">
            <button type="submit" name="update_collab_perms" value="1" class="px-sm py-0.5 rounded-full border border-outline-variant font-label-caps text-label-caps text-on-surface-variant hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-all text-[0.65rem]">Actualizar</button>
            <button type="submit" name="remove_collaborator" value="1" class="px-sm py-0.5 rounded-full border border-outline-variant font-label-caps text-label-caps text-on-surface-variant hover:bg-error/10 hover:text-error hover:border-error/30 transition-all text-[0.65rem]" onclick="return confirm('¿Eliminar este colaborador?')">Quitar</button>
        </div>
    </form>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" class="flex flex-col gap-sm p-sm rounded-xl bg-surface-variant/10 border border-outline-variant/20">
<h4 class="font-label-caps text-label-caps text-on-surface-variant">Agregar colaborador</h4>
<div class="flex flex-col md:flex-row gap-sm">
    <input type="email" name="collab_email" placeholder="Email del usuario" required class="form-input flex-1">
    <div class="flex flex-wrap items-center gap-x-md gap-y-1">
        <label class="flex items-center gap-1 cursor-pointer">
            <input type="checkbox" name="perm_edit_catalog" value="1" checked class="accent-primary">
            <span class="font-body-xs text-body-xs text-on-surface-variant">Catálogo</span>
        </label>
        <label class="flex items-center gap-1 cursor-pointer">
            <input type="checkbox" name="perm_edit_pages" value="1" checked class="accent-primary">
            <span class="font-body-xs text-body-xs text-on-surface-variant">Páginas</span>
        </label>
        <label class="flex items-center gap-1 cursor-pointer">
            <input type="checkbox" name="perm_edit_products" value="1" checked class="accent-primary">
            <span class="font-body-xs text-body-xs text-on-surface-variant">Productos</span>
        </label>
        <label class="flex items-center gap-1 cursor-pointer">
            <input type="checkbox" name="perm_edit_categories" value="1" checked class="accent-primary">
            <span class="font-body-xs text-body-xs text-on-surface-variant">Categorías</span>
        </label>
    </div>
    <button type="submit" name="add_collaborator" value="1" class="primary-gradient text-white px-md py-sm rounded-full font-label-caps text-label-caps active:scale-95 transition-transform primary-glow whitespace-nowrap">Agregar</button>
</div>
</form>
</div>
<?php endif; ?>
</div>
</body>
</html>
