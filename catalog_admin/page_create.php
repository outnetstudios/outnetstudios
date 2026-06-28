<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
require_once __DIR__ . '/../includes/catalog_preview_renderer.php';

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
    $productId = (int)($_POST['product_id'] ?? 0);
    $bannerText = trim($_POST['banner_text'] ?? '');
    $bannerSubtitle = trim($_POST['banner_subtitle'] ?? '');

    $content = [];
    if ($pageType === 'category') {
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

$GLOBALS['currencySymbol'] = currencySymbol($catalog['currency'] ?? null);

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

$allProducts = $productRepo->allByCatalog($catalogId);
$productsJson = htmlspecialchars(json_encode(array_map(fn($p) => [
    'id' => (int)$p['id'],
    'name' => $p['name'],
    'sku' => $p['sku'] ?? '',
    'price' => $p['price'] !== null ? number_format((float)$p['price'], 2) : '',
    'image' => !empty($p['main_image']) ? imageUrl($p['main_image']) : '',
], $allProducts)), ENT_QUOTES, 'UTF-8');
?>
<?php $pageTitle = 'Añadir página'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<style>
.product-search-wrap { display:flex; flex-direction:column; gap:0.5rem; }
.product-search-input-wrap { position:relative; display:flex; align-items:center; }
.product-search-icon { position:absolute; left:0.75rem; font-size:1.15rem; color:rgba(255,255,255,0.3); pointer-events:none; }
.product-search-input { padding-left:2.5rem !important; }
.product-list { display:grid; grid-template-columns:1fr; gap:0.5rem; max-height:360px; overflow-y:auto; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:0.5rem; background:rgba(255,255,255,0.02); }
.product-card { display:flex; align-items:center; gap:0.65rem; padding:0.55rem 0.65rem; border-radius:12px; border:2px solid transparent; background:rgba(255,255,255,0.03); cursor:pointer; transition:all 0.15s; }
.product-card:hover { background:rgba(165,180,252,0.08); border-color:rgba(165,180,252,0.2); }
.product-card.selected { background:rgba(165,180,252,0.1); border-color:#a5b4fc; }
.product-card-img { width:40px; height:40px; border-radius:8px; overflow:hidden; flex-shrink:0; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; }
.product-card-img img { width:100%; height:100%; object-fit:cover; }
.product-card-img-placeholder { font-size:1.2rem; color:rgba(255,255,255,0.15); }
.product-card-info { flex:1; min-width:0; }
.product-card-name { font-size:0.85rem; font-weight:600; color:#e0e0f0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.product-card-meta { font-size:0.72rem; color:rgba(255,255,255,0.35); }
.product-card-check { font-size:1.15rem; color:transparent; transition:color 0.15s; }
.product-card.selected .product-card-check { color:#a5b4fc; }
.product-empty { padding:2rem 1rem; text-align:center; font-size:0.85rem; color:rgba(255,255,255,0.4); }
@media (min-width: 768px) {
.product-list { grid-template-columns:repeat(2,1fr); max-height:420px; }
}
</style>
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

    <div id="field-product" style="display:none;">
<label class="form-label">Producto a mostrar</label>
<input type="hidden" id="product_id" name="product_id" value="0">
<div class="product-search-wrap">
    <div class="product-search-input-wrap">
        <span class="material-symbols-outlined product-search-icon">search</span>
        <input type="text" id="productSearch" class="form-input product-search-input" placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
    </div>
    <div class="product-list" id="productList">
        <?php if (empty($allProducts)): ?>
        <div class="product-empty">No hay productos en este catálogo. <a href="products.php?catalog_id=<?= $catalogId ?>" class="text-primary">Crea productos primero.</a></div>
        <?php else: ?>
        <?php foreach ($allProducts as $prod):
            $imgSrc = !empty($prod['main_image']) ? imageUrl($prod['main_image']) : '';
            $price = $prod['price'] !== null ? number_format((float)$prod['price'], 2) : '';
        ?>
        <div class="product-card" data-id="<?= (int)$prod['id'] ?>" data-name="<?= htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') ?>" data-sku="<?= htmlspecialchars($prod['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-price="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>">
            <div class="product-card-img">
                <?php if ($imgSrc): ?>
                <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="">
                <?php else: ?>
                <span class="material-symbols-outlined product-card-img-placeholder">image</span>
                <?php endif; ?>
            </div>
            <div class="product-card-info">
                <div class="product-card-name"><?= htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="product-card-meta"><?= htmlspecialchars($prod['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?><?= $price ? ' &middot; ' . $GLOBALS['currencySymbol'] . $price : '' ?></div>
            </div>
            <span class="material-symbols-outlined product-card-check">check_circle</span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
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

<div class="flex gap-sm pt-md form-btn-row">
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
document.getElementById('field-product').style.display = type === 'product' ? 'block' : 'none';
document.getElementById('field-banner').style.display = type === 'banner' ? 'block' : 'none';
}
toggleFields();

(function() {
    var list = document.getElementById('productList');
    var input = document.getElementById('productSearch');
    var hidden = document.getElementById('product_id');
    if (!list || !hidden) return;

    // Click to select
    list.addEventListener('click', function(e) {
        var card = e.target.closest('.product-card');
        if (!card) return;
        list.querySelectorAll('.product-card').forEach(function(c) { c.classList.remove('selected'); });
        card.classList.add('selected');
        hidden.value = card.getAttribute('data-id');
    });

    // Search filter
    if (input) {
        input.addEventListener('input', function() {
            var q = input.value.toLowerCase().trim();
            list.querySelectorAll('.product-card').forEach(function(card) {
                var name = (card.getAttribute('data-name') || '').toLowerCase();
                var sku = (card.getAttribute('data-sku') || '').toLowerCase();
                card.style.display = name.indexOf(q) !== -1 || sku.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
})();
</script>
</body>
</html>
