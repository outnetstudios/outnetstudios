<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
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

$pages = $pageRepo->allByCatalog($catalogId);
$categories = $categoryRepo->allByCatalog($catalogId);
$products = $productRepo->allByCatalog($catalogId);
$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');
$totalPages = count($pages);

$pageIndex = (int)($_GET['page'] ?? 1);
if ($pageIndex < 1) $pageIndex = 1;
if ($pageIndex > $totalPages) $pageIndex = max(1, $totalPages);

$currentPage = $pages[$pageIndex - 1] ?? null;
$pageContent = '';
if ($currentPage) {
    $pageContent = renderPageContent($currentPage, $categories, $products);
}
?>
<?php $pageTitle = 'Vista previa - ' . $catalogName; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
    <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: #0f1320; color: #dee2f4; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; }
    .preview-sheet { width: 640px; height: 828px; background: #ffffff; color: #1a1a2e; border-radius: 4px; box-shadow: 0 4px 24px rgba(0,0,0,0.6); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease; transform-origin: top center; position: relative; }
    .preview-sheet .preview-page { flex: 1; display: flex; flex-direction: column; }
    .page-content { padding: 2.5rem; }
    .preview-cover-content { max-width: 600px; margin: 0 auto; }
    .preview-title-lg { font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 800; margin: 0 0 0.5rem; letter-spacing: -0.03em; }
    .preview-subtitle { font-size: 1.1rem; }
    .preview-title-md { font-size: clamp(1.4rem, 2.5vw, 2rem); font-weight: 700; margin: 0 0 1rem; }
    .preview-index-list { list-style: none; padding: 0; margin: 0; }
    .preview-index-list li { padding: 0.5rem 0; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 1rem; }
    .index-num { font-weight: 700; margin-right: 0.5rem; }
    .preview-cat-name { font-size: 0.95rem; color: #888; margin-top: -0.5rem; margin-bottom: 1rem; }
    .preview-category-content { display: flex; flex-direction: column; gap: 1.5rem; overflow-y: auto; flex: 1; }
    .preview-category-section { display: flex; flex-direction: column; gap: 0.75rem; }
    .preview-category-header { display: flex; align-items: center; gap: 0.75rem; }
    .preview-cat-thumb { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(0,0,0,0.08); }
    .preview-cat-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
    .preview-cat-desc { font-size: 0.8rem; color: #888; margin: 0.15rem 0 0; }
    .preview-category-divider { display: flex; align-items: center; gap: 0.75rem; margin: 0.5rem 0; }
    .preview-divider-line { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, rgba(0,0,0,0.12), transparent); }
    .preview-divider-text { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #999; white-space: nowrap; }
    .preview-products-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; }
    .preview-product-card { display: flex; flex-direction: column; gap: 0.5rem; padding: 0.75rem; border-radius: 8px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.08); transition: background 0.2s; }
    .preview-product-card:hover { background: rgba(0,0,0,0.04); }
    .preview-product-img { width: 100%; aspect-ratio: 1; border-radius: 6px; object-fit: cover; background: #f5f5f5; }
    .preview-product-img-placeholder { display: flex; align-items: center; justify-content: center; }
    .preview-product-img-placeholder span { font-size: 2rem; color: #ccc; }
    .preview-product-info h4 { margin: 0 0 0.15rem; font-size: 0.85rem; line-height: 1.3; }
    .preview-product-info p { margin: 0.15rem 0; font-size: 0.72rem; color: #666; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .preview-sku { font-size: 0.65rem; color: #999; }
    .preview-price { font-weight: 700; font-size: 0.9rem; margin-top: 0.2rem; }
    .preview-banner-content { max-width: 600px; margin: 0 auto; }
    .preview-banner-text { font-size: clamp(1.5rem, 3vw, 2.5rem); font-weight: 700; margin: 0 0 0.5rem; }
    .preview-banner-sub { font-size: 1.1rem; margin: 0; }
    .preview-product-detail { display: flex; gap: 2rem; align-items: flex-start; }
    .preview-prod-img { width: 180px; height: 180px; border-radius: 12px; object-fit: cover; background: #f5f5f5; flex-shrink: 0; }
    .preview-prod-img-placeholder { display: flex; align-items: center; justify-content: center; }
    .preview-prod-img-placeholder span { font-size: 3rem; color: #ccc; }
    .preview-prod-info h2 { margin: 0 0 0.5rem; font-size: 1.4rem; }
    .preview-price-lg { font-size: 1.3rem; font-weight: 700; margin: 0.5rem 0; }
    .preview-stock { font-size: 0.85rem; color: #666; }
    .preview-empty { text-align: center; padding: 4rem 2rem; }
    .preview-empty p { font-size: 1.2rem; margin-bottom: 1rem; }
    .preview-cover { text-align: center; justify-content: center; }
    .preview-index {  }
    .preview-category {  }
    .preview-banner { text-align: center; justify-content: center; background: linear-gradient(135deg, #f8f9ff, #eef1ff); }
    .preview-product {  }
    .preview-back-cover { text-align: center; justify-content: center; }
    .preview-custom {  }
    .preview-cover .page-content,
    .preview-back-cover .page-content,
    .preview-banner .page-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    @media (max-width: 768px) { .preview-sheet { width: 100%; height: auto; min-height: auto; } .preview-product-detail { flex-direction: column; } .preview-prod-img { width: 100%; height: 150px; } .preview-products-grid-4 { grid-template-columns: repeat(2, 1fr); } }
    @media print { .page-with-bg { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
    </style>
</head>
<body>
<header class="sticky top-0 z-50 flex justify-between items-center w-full px-margin-mobile md:px-margin-desktop py-sm bg-surface-container/60 backdrop-blur-xl border-b border-on-surface/10 shadow-sm flex-shrink-0">
<div class="flex items-center gap-md">
<span class="font-display-lg-mobile text-display-lg-mobile font-extrabold text-primary tracking-tight"><?= $catalogName ?></span>
</div>
<div class="flex items-center gap-sm">
<button onclick="zoomOut()" class="px-sm py-1 rounded-full border border-outline-variant text-label-caps font-label-caps text-on-surface-variant hover:bg-surface-variant/30 transition-all flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">zoom_out</span></button>
<span id="zoomLevel" class="text-label-caps font-label-caps text-on-surface-variant/70 min-w-[3rem] text-center">100%</span>
<button onclick="zoomIn()" class="px-sm py-1 rounded-full border border-outline-variant text-label-caps font-label-caps text-on-surface-variant hover:bg-surface-variant/30 transition-all flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">zoom_in</span></button>
<button onclick="zoomFit()" class="px-sm py-1 rounded-full border border-outline-variant text-label-caps font-label-caps text-on-surface-variant hover:bg-surface-variant/30 transition-all flex items-center gap-1">Ajustar</button>
<a href="export_pdf.php?catalog_id=<?= $catalogId ?>" target="_blank" class="px-md py-1.5 rounded-full border border-outline-variant text-label-caps font-label-caps text-on-surface-variant hover:bg-surface-variant/30 transition-all flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> EXPORTAR PDF</a>
<a href="pages.php?catalog_id=<?= $catalogId ?>" class="px-md py-1.5 rounded-full border border-outline-variant text-label-caps font-label-caps text-on-surface-variant hover:bg-surface-variant/30 transition-all flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">edit_note</span> EDITAR PÁGINAS</a>
<a href="index.php" class="px-md py-1.5 rounded-full border border-outline-variant text-label-caps font-label-caps text-on-surface-variant hover:bg-surface-variant/30 transition-all flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">arrow_back</span> CATÁLOGOS</a>
</div>
</header>

<div class="flex-1 overflow-auto p-margin-mobile md:p-margin-desktop flex items-start justify-center" id="previewContainer" style="padding-bottom: 80px;">
<?php if (empty($pages)): ?>
<div class="preview-empty">
<p class="font-title-sm text-title-sm text-on-surface">Este catálogo no tiene páginas aún.</p>
<a href="page_create.php?catalog_id=<?= $catalogId ?>" class="text-primary hover:text-primary-fixed-dim transition-colors">Añadir primera página</a>
</div>
<?php else: ?>
<div class="preview-sheet" id="previewSheet">
<?= $pageContent ?>
</div>
<?php endif; ?>
</div>

<?php if (!empty($pages)): ?>
<footer class="fixed bottom-0 left-0 right-0 z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop py-sm bg-surface-container/80 backdrop-blur-xl border-t border-on-surface/10 shadow-sm" style="padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0px));">
<div class="w-[120px] flex justify-start">
<?php if ($pageIndex > 1): ?>
<a class="flex items-center gap-1 px-lg py-2 rounded-full border border-outline-variant text-on-surface hover:bg-surface-variant/30 transition-all font-body-sm text-body-sm" href="?catalog_id=<?= $catalogId ?>&page=<?= $pageIndex - 1 ?>"><span class="material-symbols-outlined text-[18px]">chevron_left</span> Anterior</a>
<?php else: ?>
<span class="flex items-center gap-1 px-lg py-2 rounded-full border border-outline-variant/20 text-on-surface-variant/30 font-body-sm text-body-sm"><span class="material-symbols-outlined text-[18px]">chevron_left</span> Anterior</span>
<?php endif; ?>
</div>
<div class="flex items-center gap-1">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
<a href="?catalog_id=<?= $catalogId ?>&page=<?= $i ?>" class="w-8 h-8 rounded-full flex items-center justify-center text-label-caps font-label-caps <?= $i === $pageIndex ? 'bg-primary/20 text-primary font-bold' : 'text-on-surface-variant/60 hover:bg-surface-variant/20 hover:text-on-surface' ?> transition-all no-underline"><?= $i ?></a>
<?php endfor; ?>
</div>
<div class="w-[120px] flex justify-end">
<?php if ($pageIndex < $totalPages): ?>
<a class="flex items-center gap-1 px-lg py-2 rounded-full border border-outline-variant text-on-surface hover:bg-surface-variant/30 transition-all font-body-sm text-body-sm" href="?catalog_id=<?= $catalogId ?>&page=<?= $pageIndex + 1 ?>">Siguiente <span class="material-symbols-outlined text-[18px]">chevron_right</span></a>
<?php else: ?>
<span class="flex items-center gap-1 px-lg py-2 rounded-full border border-outline-variant/20 text-on-surface-variant/30 font-body-sm text-body-sm">Siguiente <span class="material-symbols-outlined text-[18px]">chevron_right</span></span>
<?php endif; ?>
</div>
</footer>
<?php endif; ?>

<script>
let zoom = 1;
const sheet = document.getElementById('previewSheet');
const zoomLabel = document.getElementById('zoomLevel');
const storageKey = 'catalog_zoom_<?= $catalogId ?>';

function saveZoom() {
    try { localStorage.setItem(storageKey, zoom); } catch(e) {}
}

function loadZoom() {
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved !== null) return parseFloat(saved);
    } catch(e) {}
    return null;
}

function applyZoom() {
    if (!sheet) return;
    const pct = Math.round(zoom * 100);
    sheet.style.transform = 'scale(' + zoom + ')';
    sheet.style.transformOrigin = 'top center';
    if (zoomLabel) zoomLabel.textContent = pct + '%';
}

function zoomIn() {
    zoom = Math.min(zoom + 0.25, 3);
    applyZoom();
    saveZoom();
}

function zoomOut() {
    zoom = Math.max(zoom - 0.25, 0.25);
    applyZoom();
    saveZoom();
}

function zoomFit() {
    const container = document.getElementById('previewContainer');
    if (!container || !sheet) { zoom = 1; applyZoom(); saveZoom(); return; }
    const cw = container.clientWidth - 48;
    const sw = 640;
    zoom = Math.min(cw / sw, 1);
    zoom = Math.round(zoom * 100) / 100;
    applyZoom();
    saveZoom();
}

function zoomActual() {
    zoom = 1;
    applyZoom();
    saveZoom();
}

const saved = loadZoom();
if (saved !== null && saved > 0) {
    zoom = saved;
    applyZoom();
} else {
    zoomFit();
}

window.addEventListener('resize', function() {
    const saved = loadZoom();
    if (saved === null) zoomFit();
});
</script>
</body>
</html>
