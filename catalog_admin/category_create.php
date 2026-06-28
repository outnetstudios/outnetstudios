<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';

catalogRequireLogin();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$userId = (int)catalogGetUserId();
$catalogId = (int)($_GET['catalog_id'] ?? 0);

$catRepo = new CatalogRepository();
$catalog = $catRepo->findById($catalogId);
if (!$catalog || (int)$catalog['user_id'] !== $userId) {
    header('Location: index.php');
    exit;
}

$categoryRepo = new CategoryRepository();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = in_array($_POST['status'] ?? 'active', ['active','inactive']) ? $_POST['status'] : 'active';

    if ($name === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        $image = uploadImage($_FILES['image'] ?? []);
        $sortOrder = $categoryRepo->getMaxSortOrder($catalogId) + 1;
        $categoryRepo->create([
            'catalog_id' => $catalogId,
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'sort_order' => $sortOrder,
            'status' => $status,
            'image' => $image,
        ]);
        header('Location: categories.php?catalog_id=' . $catalogId);
        exit;
    }
}
?>
<?php $pageTitle = 'Nueva categoría'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<?php $navbarBackUrl = 'categories.php?catalog_id=' . $catalogId; require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
<div class="max-w-4xl mx-auto">


<div class="glass-panel rounded-3xl p-md md:p-xl">
<?php if ($error !== ''): ?>
<div class="bg-error-container/20 border border-error/30 rounded-xl px-md py-sm mb-md">
<p class="font-body-sm text-body-sm text-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>

<form method="post" class="space-y-lg" enctype="multipart/form-data">
<div>
<label class="form-label" for="name">Nombre</label>
<input type="text" id="name" name="name" required class="form-input" placeholder="Ej: Electrónica">
</div>
<div>
<label class="form-label" for="description">Descripción (opcional)</label>
<textarea id="description" name="description" rows="3" class="form-input" placeholder="Descripción breve de la categoría"></textarea>
</div>
<div>
<label class="form-label" for="image">Imagen de categoría (opcional)</label>
<input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input file:bg-surface-variant/30 file:border-0 file:rounded-full file:px-sm file:py-1 file:text-label-caps file:text-primary file:cursor-pointer">
</div>
<div>
<label class="form-label" for="status">Estado</label>
<select id="status" name="status" class="form-input">
<option value="active">Activo</option>
<option value="inactive">Inactivo</option>
</select>
</div>
<div class="flex gap-sm pt-md form-btn-row">
<button class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow" type="submit">Crear categoría</button>
<a href="categories.php?catalog_id=<?= $catalogId ?>" class="px-lg py-sm rounded-full border border-outline-variant font-title-sm text-title-sm text-on-surface-variant hover:bg-surface-variant/50 hover:border-error/30 hover:text-error transition-all no-underline">Cancelar</a>
</div>
</form>
</div>
</div>
</section>
</main>
</body>
</html>
