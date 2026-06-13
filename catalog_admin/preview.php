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
$GLOBALS['currencySymbol'] = currencySymbol($catalog['currency'] ?? null);

// Expand pages: category pages auto-flow products
$expandedPages = buildExpandedPages($pages, $products, $categories);
// Pass all products for product detail lookups
foreach ($expandedPages as &$ep) { $ep['_all_products'] = $products; }
unset($ep);

$totalPages = count($expandedPages);

$pageIndex = (int)($_GET['page'] ?? 1);
if ($pageIndex < 1) $pageIndex = 1;
if ($pageIndex > $totalPages) $pageIndex = max(1, $totalPages);

$currentPage = $expandedPages[$pageIndex - 1] ?? null;
$pageContent = '';
if ($currentPage) {
    $pageContent = renderPageContent($currentPage, $categories, $expandedPages);
}
?>
<?php $pageTitle = 'Vista previa - ' . $catalogName; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
    <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: #0f1320; color: #dee2f4; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; }
    .preview-sheet { width: 816px; height: 1056px; background: #ffffff; color: #1a1a2e; border-radius: 4px; box-shadow: 0 4px 24px rgba(0,0,0,0.6); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease; transform-origin: top center; position: relative; flex-shrink: 0; }
    .preview-sheet .preview-page { flex: 1; display: flex; flex-direction: column; }
    .page-content { padding: 2.75rem; }
    .preview-cover-content { max-width: 700px; margin: 0 auto; }
    .preview-title-lg { font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 800; margin: 0 0 0.5rem; letter-spacing: -0.03em; }
    .preview-subtitle { font-size: 1.1rem; }
    .preview-title-md { font-size: clamp(1.2rem, 2.5vw, 1.6rem); font-weight: 700; margin: 0 0 0.5rem; }
    .preview-index-list { list-style: none; padding: 0; margin: 0; }
    .preview-index-list li { padding: 0.5rem 0; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 1rem; }
    .index-num { font-weight: 700; margin-right: 0.5rem; }
    .preview-cat-name { font-size: 0.95rem; color: #888; margin-top: -0.5rem; margin-bottom: 1rem; }
    .preview-category-content { display: flex; flex-direction: column; gap: 0.75rem; overflow: hidden; flex: 1; }
    .preview-category-section { display: flex; flex-direction: column; gap: 0.5rem; }
    .preview-category-header { display: flex; align-items: center; gap: 0.5rem; }
    .preview-cat-thumb { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(0,0,0,0.08); }
    .preview-cat-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
    .preview-cat-desc { font-size: 0.8rem; color: #888; margin: 0.15rem 0 0; }
    .preview-category-divider { display: flex; align-items: center; gap: 0.75rem; margin: 0.5rem 0; }
    .preview-divider-line { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, rgba(0,0,0,0.12), transparent); }
    .preview-divider-text { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #999; white-space: nowrap; }
    .preview-products-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.25rem; }
    .preview-product-card { display: flex; flex-direction: column; gap: 0.25rem; padding: 0.375rem; border-radius: 8px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.08); overflow: hidden; }
    .preview-product-img { width: 100%; aspect-ratio: 1; border-radius: 6px; object-fit: cover; background: #f5f5f5; flex-shrink: 0; }
    .preview-product-img-placeholder { display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .preview-product-img-placeholder span { font-size: 2rem; color: #ccc; }
    .preview-product-info { flex: 1; display: flex; flex-direction: column; min-height: 0; }
    .preview-product-info h4 { margin: 0 0 0.125rem; font-size: 0.85rem; line-height: 1.3; }
    .preview-product-info p { margin: 0.125rem 0; font-size: 0.72rem; color: #666; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .preview-sku { font-size: 0.65rem; color: #999; }
    .preview-price { font-weight: 700; font-size: 0.9rem; margin-top: auto; }
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
    .preview-banner { text-align: center; justify-content: center; background: linear-gradient(135deg, #f8f9ff, #eef1ff); }
    .preview-back-cover { text-align: center; justify-content: center; }
    .preview-cover .page-content,
    .preview-back-cover .page-content,
    .preview-banner .page-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .preview-page[style*="background:"] .page-content { display: none; }
    .preview-header-wrapper { position: sticky; top: 0; z-index: 50; background: rgba(15,19,32,0.85); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255,255,255,0.06); }
    .preview-header { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0.5rem 1rem; gap: 0.5rem; }
    .preview-header-title { font-size: 1rem; font-weight: 700; color: #a5b4fc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
    .preview-header-actions { display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0; }
    .preview-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.6rem; border-radius: 999px; border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #b0b8d4; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; transition: all 0.15s; text-decoration: none; white-space: nowrap; }
    .preview-btn:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .preview-btn-icon { font-size: 1rem !important; }
    .preview-btn-label { display: none; }
    .preview-zoom-label { font-size: 0.7rem; font-weight: 600; color: rgba(255,255,255,0.4); min-width: 2.2rem; text-align: center; }
    .preview-footer { position: fixed; bottom: 0; left: 0; right: 0; z-index: 50; display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0px)); background: rgba(15,19,32,0.85); backdrop-filter: blur(16px); border-top: 1px solid rgba(255,255,255,0.06); }
    .preview-footer-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.75rem; border-radius: 999px; border: 1px solid rgba(255,255,255,0.12); color: #b0b8d4; font-size: 0.75rem; font-weight: 500; text-decoration: none; transition: all 0.15s; white-space: nowrap; }
    .preview-footer-btn:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .preview-footer-btn:active { transform: scale(0.96); }
    .preview-footer-btn.disabled { opacity: 0.25; pointer-events: none; }
    .preview-page-numbers { display: flex; align-items: center; gap: 0.2rem; }
    .preview-page-num { width: 1.75rem; height: 1.75rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600; text-decoration: none; color: rgba(255,255,255,0.35); transition: all 0.15s; }
    .preview-page-num:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .preview-page-num.active { background: rgba(165,180,252,0.15); color: #a5b4fc; font-weight: 700; }
    #previewContainer { flex: 1; overflow: auto; display: flex; align-items: flex-start; justify-content: center; }
    #previewContainer.with-pages { padding: 1.5rem 1rem 5rem; }

    @media (min-width: 640px) {
        .preview-header { padding: 0.5rem 1.5rem; }
        .preview-header-title { max-width: none; font-size: 1.15rem; }
        .preview-btn-label { display: inline; }
        .preview-btn { padding: 0.4rem 0.9rem; font-size: 0.72rem; gap: 0.35rem; }
        #previewContainer.with-pages { padding: 2rem 1.5rem 5rem; }
        .preview-footer { padding: 0.5rem 1.5rem; }
    }

    @media (max-width: 768px) {
        .preview-product-detail { flex-direction: column; }
        .preview-prod-img { width: 100%; height: 150px; }
        .preview-products-grid-4 { grid-template-columns: repeat(2, 1fr); }
        .preview-header-actions .preview-btn:not(.preview-btn-always) { display: none; }
        .preview-header-actions .preview-btn-mobile { display: inline-flex; }
    }
    @media (max-width: 480px) {
        .preview-products-grid-4 { grid-template-columns: 1fr 1fr; gap: 0.5rem; }
        .preview-product-card { padding: 0.5rem; }
        .page-content { padding: 1.5rem; }
        .preview-header-title { font-size: 0.85rem; max-width: 100px; }
    }
    @media print { .page-with-bg { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
    </style>
</head>
<body>

<header class="preview-header-wrapper">
<div class="preview-header">
    <span class="preview-header-title"><?= $catalogName ?></span>
    <div class="preview-header-actions">
        <button onclick="zoomOut()" class="preview-btn preview-btn-always" title="Alejar"><span class="material-symbols-outlined preview-btn-icon">zoom_out</span><span class="preview-btn-label">Alejar</span></button>
        <span class="preview-zoom-label" id="zoomLevel">100%</span>
        <button onclick="zoomIn()" class="preview-btn preview-btn-always" title="Acercar"><span class="material-symbols-outlined preview-btn-icon">zoom_in</span><span class="preview-btn-label">Acercar</span></button>
        <button onclick="zoomFit()" class="preview-btn preview-btn-always" title="Ajustar al ancho"><span class="material-symbols-outlined preview-btn-icon">fit_width</span><span class="preview-btn-label">Ajustar</span></button>
        <a href="export_pdf.php?catalog_id=<?= $catalogId ?>" target="_blank" class="preview-btn preview-btn-always" title="Exportar PDF"><span class="material-symbols-outlined preview-btn-icon">picture_as_pdf</span><span class="preview-btn-label">PDF</span></a>
        <a href="pages.php?catalog_id=<?= $catalogId ?>" class="preview-btn" title="Editar páginas"><span class="material-symbols-outlined preview-btn-icon">edit_note</span><span class="preview-btn-label">Editar</span></a>
        <a href="index.php" class="preview-btn" title="Volver a catálogos"><span class="material-symbols-outlined preview-btn-icon">arrow_back</span><span class="preview-btn-label">Catálogos</span></a>
    </div>
</div>
</header>

<div class="flex-1 overflow-auto flex items-start justify-center <?= empty($pages) ? '' : 'with-pages' ?>" id="previewContainer">
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
<nav class="preview-footer">
    <div>
        <?php if ($pageIndex > 1): ?>
        <a class="preview-footer-btn" href="?catalog_id=<?= $catalogId ?>&page=<?= $pageIndex - 1 ?>" id="prevPage"><span class="material-symbols-outlined" style="font-size:1.1rem">chevron_left</span> Anterior</a>
        <?php else: ?>
        <span class="preview-footer-btn disabled"><span class="material-symbols-outlined" style="font-size:1.1rem">chevron_left</span> Anterior</span>
        <?php endif; ?>
    </div>
    <div class="preview-page-numbers">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?catalog_id=<?= $catalogId ?>&page=<?= $i ?>" class="preview-page-num <?= $i === $pageIndex ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <div>
        <?php if ($pageIndex < $totalPages): ?>
        <a class="preview-footer-btn" href="?catalog_id=<?= $catalogId ?>&page=<?= $pageIndex + 1 ?>" id="nextPage">Siguiente <span class="material-symbols-outlined" style="font-size:1.1rem">chevron_right</span></a>
        <?php else: ?>
        <span class="preview-footer-btn disabled">Siguiente <span class="material-symbols-outlined" style="font-size:1.1rem">chevron_right</span></span>
        <?php endif; ?>
    </div>
</nav>
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
    try { const s = localStorage.getItem(storageKey); if (s !== null) return parseFloat(s); } catch(e) {}
    return null;
}

function applyZoom() {
    if (!sheet) return;
    const pct = Math.round(zoom * 100);
    sheet.style.transform = 'scale(' + zoom + ')';
    if (zoomLabel) zoomLabel.textContent = pct + '%';
}

function zoomIn() { zoom = Math.min(zoom + 0.10, 3); applyZoom(); saveZoom(); }
function zoomOut() { zoom = Math.max(zoom - 0.10, 0.10); applyZoom(); saveZoom(); }

function zoomFit() {
    const container = document.getElementById('previewContainer');
    if (!container || !sheet) { zoom = 1; applyZoom(); saveZoom(); return; }
    const cw = container.clientWidth - 48;
    zoom = Math.min(cw / 816, 1);
    zoom = Math.round(zoom * 100) / 100;
    applyZoom();
    saveZoom();
}

const saved = loadZoom();
if (saved !== null && saved > 0) { zoom = saved; applyZoom(); }
else { zoomFit(); }

window.addEventListener('resize', function() {
    const s = loadZoom();
    if (s === null) zoomFit();
});

// Touch swipe navigation
(function() {
    const container = document.getElementById('previewContainer');
    if (!container) return;
    let xStart = null;
    container.addEventListener('touchstart', function(e) {
        if (e.touches.length === 1) xStart = e.touches[0].clientX;
    }, { passive: true });
    container.addEventListener('touchend', function(e) {
        if (xStart === null) return;
        const xEnd = e.changedTouches[0].clientX;
        const diff = xStart - xEnd;
        xStart = null;
        if (Math.abs(diff) < 50) return;
        if (diff > 0) {
            var next = document.getElementById('nextPage');
            if (next && next.tagName === 'A') location.href = next.href;
        } else {
            var prev = document.getElementById('prevPage');
            if (prev && prev.tagName === 'A') location.href = prev.href;
        }
    }, { passive: true });
})();
</script>
</body>
</html>
