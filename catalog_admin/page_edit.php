<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
require_once __DIR__ . '/../includes/catalog_preview_renderer.php';

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
$catRepo = new CatalogRepository();
$catalog = $catRepo->findById($catalogId);
if ($catalog) $GLOBALS['currencySymbol'] = currencySymbol($catalog['currency'] ?? null);
$prodRepo = new ProductRepository();
$allProducts = $prodRepo->allByCatalog($catalogId);
$selectedProductId = (int)($content['product_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');

    $newContent = $content ?: [];
    if ($page['page_type'] === 'banner') {
        $newContent['text'] = trim($_POST['banner_text'] ?? '');
        $newContent['subtitle'] = trim($_POST['banner_subtitle'] ?? '');
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
    <?php elseif ($page['page_type'] === 'product'): ?>
<div>
<label class="form-label">Producto</label>
<input type="hidden" id="product_id" name="product_id" value="<?= $selectedProductId ?>">
<div class="product-search-wrap">
    <div class="product-search-input-wrap">
        <span class="material-symbols-outlined product-search-icon">search</span>
        <input type="text" id="productSearch" class="form-input product-search-input" placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
    </div>
    <div class="product-list" id="productList">
        <?php if (empty($allProducts)): ?>
        <div class="product-empty">No hay productos en este catálogo.</div>
        <?php else: ?>
        <?php foreach ($allProducts as $prod):
            $imgSrc = !empty($prod['main_image']) ? imageUrl($prod['main_image']) : '';
            $price = $prod['price'] !== null ? number_format((float)$prod['price'], 2) : '';
            $isSelected = (int)$prod['id'] === $selectedProductId;
        ?>
        <div class="product-card<?= $isSelected ? ' selected' : '' ?>" data-id="<?= (int)$prod['id'] ?>" data-name="<?= htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') ?>" data-sku="<?= htmlspecialchars($prod['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-price="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>">
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

<div class="flex gap-sm pt-md form-btn-row">
<button class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow" type="submit">Guardar cambios</button>
<a href="pages.php?catalog_id=<?= $catalogId ?>" class="px-lg py-sm rounded-full border border-outline-variant font-title-sm text-title-sm text-on-surface-variant hover:bg-surface-variant/50 hover:border-error/30 hover:text-error transition-all no-underline">Cancelar</a>
</div>
</form>
</div>
</div>
</section>
</main>
<script>
(function() {
    var list = document.getElementById('productList');
    var input = document.getElementById('productSearch');
    var hidden = document.getElementById('product_id');
    if (!list || !hidden) return;

    list.addEventListener('click', function(e) {
        var card = e.target.closest('.product-card');
        if (!card) return;
        list.querySelectorAll('.product-card').forEach(function(c) { c.classList.remove('selected'); });
        card.classList.add('selected');
        hidden.value = card.getAttribute('data-id');
    });

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
