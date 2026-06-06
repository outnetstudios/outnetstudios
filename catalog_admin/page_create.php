<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';

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

$pageRepo = new CatalogPageRepository();
$categoryRepo = new CategoryRepository();
$productRepo = new ProductRepository();
$categories = $categoryRepo->allByCatalog($catalogId);
$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pageType = $_POST['page_type'] ?? 'custom';
    $validTypes = ['cover','index','category','banner','product','back_cover','custom'];
    if (!in_array($pageType, $validTypes)) $pageType = 'custom';

    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $bannerText = trim($_POST['banner_text'] ?? '');
    $bannerSubtitle = trim($_POST['banner_subtitle'] ?? '');

    $content = [];
    if ($pageType === 'category') {
        $content['category_id'] = $categoryId;
        if ($title === '') $title = 'Categoría';
    } elseif ($pageType === 'product') {
        $content['product_id'] = $productId;
        if ($title === '') $title = 'Producto';
    } elseif ($pageType === 'banner') {
        $content['text'] = $bannerText;
        $content['subtitle'] = $bannerSubtitle;
        if ($title === '') $title = $bannerText ?: 'Banner';
    } elseif ($pageType === 'cover') {
        if ($title === '') $title = 'Portada';
    } elseif ($pageType === 'back_cover') {
        if ($title === '') $title = 'Contraportada';
    } elseif ($pageType === 'index') {
        if ($title === '') $title = 'Índice';
    }

    $bgImage = uploadImage($_FILES['background_image'] ?? []);
    $sortOrder = $pageRepo->getMaxSortOrder($catalogId) + 1;
    $pageRepo->create([
        'catalog_id' => $catalogId,
        'user_id' => $userId,
        'page_type' => $pageType,
        'title' => $title,
        'content' => $content,
        'background_image' => $bgImage,
        'sort_order' => $sortOrder,
    ]);
    header('Location: pages.php?catalog_id=' . $catalogId);
    exit;
}

$typeLabels = [
    'cover' => 'Portada',
    'index' => 'Índice',
    'category' => 'Página de categoría (muestra productos)',
    'banner' => 'Banner promocional',
    'product' => 'Página de producto individual',
    'back_cover' => 'Contraportada',
    'custom' => 'Personalizada',
];
$typeIcons = ['cover'=>'book', 'index'=>'format_list_bulleted', 'category'=>'folder', 'banner'=>'campaign', 'product'=>'inventory_2', 'back_cover'=>'book_5', 'custom'=>'description'];
?>
<?php $pageTitle = 'Añadir página'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<?php $navbarBackUrl = 'pages.php?catalog_id=' . $catalogId; $navbarExtra = '<a href="preview.php?catalog_id=' . $catalogId . '" class="px-md py-1.5 rounded-full border border-outline-variant text-label-caps font-label-caps text-on-surface-variant hover:bg-surface-variant/30 transition-all flex items-center gap-1 no-underline"><span class="material-symbols-outlined text-[16px]">visibility</span> VISTA PREVIA</a>'; require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
<div class="max-w-4xl mx-auto">


<div class="glass-panel rounded-3xl p-md md:p-xl">
<?php if ($error !== ''): ?>
<div class="bg-error-container/20 border border-error/30 rounded-xl px-md py-sm mb-md">
<p class="font-body-sm text-body-sm text-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>

<form method="post" id="pageForm" class="space-y-lg" enctype="multipart/form-data">
<div>
<label class="form-label" for="page_type">Tipo de página</label>
<select id="page_type" name="page_type" class="form-input" onchange="toggleFields()">
<?php foreach ($typeLabels as $val => $label): ?>
<option value="<?= $val ?>"><?= $label ?></option>
<?php endforeach; ?>
</select>
</div>

<div id="field-title">
<label class="form-label" for="title">Título (opcional)</label>
<input type="text" id="title" name="title" class="form-input" placeholder="Título de la página">
</div>

<div id="field-category" style="display:none;">
<label class="form-label" for="category_id">Categoría a mostrar</label>
<select id="category_id" name="category_id" class="form-input">
<option value="0">Seleccionar categoría</option>
<?php foreach ($categories as $cat): ?>
<option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
</select>
</div>

<div id="field-product" style="display:none;">
<label class="form-label" for="product_id">Producto a mostrar</label>
<select id="product_id" name="product_id" class="form-input">
<option value="0">Seleccionar producto</option>
<?php
$allProducts = $productRepo->allByCatalog($catalogId);
foreach ($allProducts as $prod): ?>
<option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
</select>
</div>

<div id="field-banner" style="display:none;">
<div class="space-y-md">
<div>
<label class="form-label" for="banner_text">Texto del banner</label>
<input type="text" id="banner_text" name="banner_text" class="form-input" placeholder="Ej: ¡Ofertas de temporada!">
</div>
<div>
<label class="form-label" for="banner_subtitle">Subtítulo (opcional)</label>
<input type="text" id="banner_subtitle" name="banner_subtitle" class="form-input" placeholder="Ej: Hasta 50% de descuento">
</div>
</div>
</div>
<div id="field-image">
<label class="form-label" for="background_image">Imagen de fondo (opcional)</label>
<input type="file" id="background_image" name="background_image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input file:bg-surface-variant/30 file:border-0 file:rounded-full file:px-sm file:py-1 file:text-label-caps file:text-primary file:cursor-pointer">
</div>

<div class="flex gap-sm pt-md">
<button class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow" type="submit">Añadir página</button>
<a href="pages.php?catalog_id=<?= $catalogId ?>" class="px-lg py-sm rounded-full border border-outline-variant font-title-sm text-title-sm text-on-surface-variant hover:bg-surface-variant/50 hover:border-error/30 hover:text-error transition-all no-underline">Cancelar</a>
</div>
</form>
</div>
</div>
</section>
</main>

<script>
function toggleFields() {
var type = document.getElementById('page_type').value;
document.getElementById('field-category').style.display = type === 'category' ? 'block' : 'none';
document.getElementById('field-product').style.display = type === 'product' ? 'block' : 'none';
document.getElementById('field-banner').style.display = type === 'banner' ? 'block' : 'none';
}
toggleFields();
</script>
</body>
</html>
