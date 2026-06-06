<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';

catalogRequireLogin();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$userId = (int)catalogGetUserId();
$id = (int)($_GET['id'] ?? 0);

$pageRepo = new CatalogPageRepository();
$page = $pageRepo->findById($id);

if (!$page || (int)$page['user_id'] !== $userId) {
    header('Location: index.php');
    exit;
}

$catalogId = (int)$page['catalog_id'];
$content = $page['content_json'] ? json_decode($page['content_json'], true) : [];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');

    $newContent = $content ?: [];
    if ($page['page_type'] === 'banner') {
        $newContent['text'] = trim($_POST['banner_text'] ?? '');
        $newContent['subtitle'] = trim($_POST['banner_subtitle'] ?? '');
    } elseif ($page['page_type'] === 'category') {
        $newContent['category_id'] = (int)($_POST['category_id'] ?? 0);
    } elseif ($page['page_type'] === 'product') {
        $newContent['product_id'] = (int)($_POST['product_id'] ?? 0);
    }

    $newBg = uploadImage($_FILES['background_image'] ?? []);
    if ($newBg) { deleteImage($page['background_image'] ?? null); }
    $bgImage = $newBg ?: ($page['background_image'] ?? null);
    $pageRepo->update($id, [
        'title' => $title,
        'content' => $newContent,
        'background_image' => $bgImage,
    ]);
    header('Location: pages.php?catalog_id=' . $catalogId);
    exit;
}
?>
<?php $pageTitle = 'Editar página'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<?php $navbarBackUrl = 'pages.php?catalog_id=' . $catalogId; require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
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
<label class="form-label">Tipo</label>
<input type="text" disabled value="<?= htmlspecialchars($page['page_type'], ENT_QUOTES, 'UTF-8') ?>" class="form-input opacity-60 cursor-not-allowed">
</div>

<div>
<label class="form-label" for="title">Título</label>
<input type="text" id="title" name="title" class="form-input" value="<?= htmlspecialchars($page['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<?php if ($page['page_type'] === 'banner'): ?>
<div class="space-y-md">
<div>
<label class="form-label" for="banner_text">Texto del banner</label>
<input type="text" id="banner_text" name="banner_text" class="form-input" value="<?= htmlspecialchars($content['text'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>
<div>
<label class="form-label" for="banner_subtitle">Subtítulo</label>
<input type="text" id="banner_subtitle" name="banner_subtitle" class="form-input" value="<?= htmlspecialchars($content['subtitle'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>
</div>
<?php elseif ($page['page_type'] === 'category'): ?>
<div>
<label class="form-label" for="category_id">Categoría</label>
<select id="category_id" name="category_id" class="form-input">
<option value="0">Sin categoría</option>
<?php
$catRepo = new \CategoryRepository();
$cats = $catRepo->allByCatalog((int)$page['catalog_id']);
foreach ($cats as $cat): ?>
<option value="<?= $cat['id'] ?>" <?= (int)($content['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
</select>
</div>
<?php elseif ($page['page_type'] === 'product'): ?>
<div>
<label class="form-label" for="product_id">Producto</label>
<select id="product_id" name="product_id" class="form-input">
<option value="0">Sin producto</option>
<?php
$prodRepo = new \ProductRepository();
$prods = $prodRepo->allByCatalog((int)$page['catalog_id']);
foreach ($prods as $p): ?>
<option value="<?= $p['id'] ?>" <?= (int)($content['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
</select>
</div>
<?php endif; ?>

<div>
<label class="form-label" for="background_image">Imagen de fondo</label>
<input type="file" id="background_image" name="background_image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input file:bg-surface-variant/30 file:border-0 file:rounded-full file:px-sm file:py-1 file:text-label-caps file:text-primary file:cursor-pointer">
<?php if (!empty($page['background_image'])): ?>
<div class="flex items-center gap-sm mt-xs">
<img src="<?= htmlspecialchars(imageUrl($page['background_image']), ENT_QUOTES, 'UTF-8') ?>" class="w-14 h-14 rounded-lg object-cover border border-outline-variant/30">
<span class="text-body-sm text-on-surface-variant/60">Imagen actual</span>
</div>
<?php endif; ?>
</div>

<div class="flex gap-sm pt-md">
<button class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow" type="submit">Guardar cambios</button>
<a href="pages.php?catalog_id=<?= $catalogId ?>" class="px-lg py-sm rounded-full border border-outline-variant font-title-sm text-title-sm text-on-surface-variant hover:bg-surface-variant/50 hover:border-error/30 hover:text-error transition-all no-underline">Cancelar</a>
</div>
</form>
</div>
</div>
</section>
</main>
</body>
</html>
