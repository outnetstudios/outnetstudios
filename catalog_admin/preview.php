<?php
$isPublicView = $isPublicView ?? false;
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
require_once __DIR__ . '/../includes/catalog_preview_renderer.php';

if (!$isPublicView) {
    require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
    catalogRequireLogin();
    $userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
    $userId = (int)catalogGetUserId();
    $catalogId = (int)($_GET['catalog_id'] ?? 0);
} else {
    $catalogId = (int)($_GET['id'] ?? 0);
}

$catRepo = new CatalogRepository();
$catalog = $catRepo->findById($catalogId);
if (!$isPublicView) {
    if (!$catalog || (int)$catalog['user_id'] !== $userId) {
        header('Location: index.php');
        exit;
    }
} else {
    if (!$catalog) {
        http_response_code(404);
        echo 'Catálogo no encontrado.';
        exit;
    }
    if ($catalog['status'] === 'draft') {
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Catálogo en mantenimiento</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f1320;color:#dee2f4;font-family:"Inter",sans-serif;text-align:center;padding:2rem}.msg{max-width:480px}.msg h1{font-size:1.5rem;margin:0 0 0.75rem;color:#a5b4fc}.msg p{font-size:0.95rem;color:#8892b0;line-height:1.5;margin:0}</style></head><body><div class="msg"><span class="material-symbols-outlined" style="font-size:3rem;color:#fbbf24;margin-bottom:1rem">construction</span><h1>Catálogo en mantenimiento</h1><p>Ponte en contacto con tu proveedor para más información.</p></div></body></html>';
        exit;
    }
    if ($catalog['status'] === 'archived') {
        http_response_code(410);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Catálogo inactivo</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f1320;color:#dee2f4;font-family:"Inter",sans-serif;text-align:center;padding:2rem}.msg{max-width:480px}.msg h1{font-size:1.5rem;margin:0 0 0.75rem;color:#f87171}.msg p{font-size:0.95rem;color:#8892b0;line-height:1.5;margin:0}</style></head><body><div class="msg"><span class="material-symbols-outlined" style="font-size:3rem;color:#f87171;margin-bottom:1rem">block</span><h1>Catálogo inactivo</h1><p>Ponte en contacto con tu proveedor para más información.</p></div></body></html>';
        exit;
    }
}

$pageRepo = new CatalogPageRepository();
$categoryRepo = new CategoryRepository();
$productRepo = new ProductRepository();

$pages = $pageRepo->allByCatalog($catalogId);
$categories = $categoryRepo->allByCatalog($catalogId);
$products = $productRepo->allByCatalog($catalogId);

if ($isPublicView) {
    $activeCatIds = [];
    foreach ($categories as $c) { if ($c['status'] === 'active') $activeCatIds[] = (int)$c['id']; }
    $categories = array_values(array_filter($categories, fn($c) => $c['status'] === 'active'));
    $products = array_values(array_filter($products, fn($p) => (int)$p['category_id'] === 0 || in_array((int)$p['category_id'], $activeCatIds)));
}

$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');
$GLOBALS['currencySymbol'] = currencySymbol($catalog['currency'] ?? null);
$showPrices = empty($_GET['no_prices']) && ($GLOBALS['showPrices'] ?? true);
$GLOBALS['showPrices'] = $showPrices;
$publicUrlWithPrices = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/ver_catalogo.php?id=' . $catalogId;
$publicUrlNoPrices = $publicUrlWithPrices . '&no_prices=1';

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
$pageNum = null;
if ($currentPage && !in_array($currentPage['page_type'], ['cover', 'back_cover', 'index'], true)) {
    $pageNum = 0;
    for ($i = 0; $i < $pageIndex; $i++) {
        if (!in_array($expandedPages[$i]['page_type'], ['cover', 'back_cover', 'index'], true)) $pageNum++;
    }
}
$allowPdf = !empty($catalog['public_pdf_download']);
$pageTitle = 'Vista previa - ' . $catalogName;
if (!$isPublicView):
    require_once __DIR__ . '/../templates/partials/admin_head.php';
else:
    ?><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"><title><?= $catalogName ?> — Catálogo</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"><?php endif; ?>
    <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: #0f1320; color: #dee2f4; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; overflow-x: hidden; }
    .preview-sheet { width: 816px; height: 1056px; background: #ffffff; color: #1a1a2e; border-radius: 4px; box-shadow: 0 4px 24px rgba(0,0,0,0.6); overflow: hidden; display: flex; flex-direction: column; transform-origin: top left; position: relative; flex-shrink: 0; }
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
    .preview-category-content { display: flex; flex-direction: column; gap: 0.5rem; overflow: hidden; flex: 1; }
    .preview-category-section { display: flex; flex-direction: column; gap: 0.75rem; }
    .preview-category-header { display: flex; align-items: center; gap: 0.5rem; padding-top: 0.375rem; }
    .preview-cat-thumb { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(0,0,0,0.08); }
    .preview-cat-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
    .preview-cat-desc { font-size: 0.8rem; color: #888; margin: 0.15rem 0 0; }
    .preview-category-divider { display: flex; align-items: center; gap: 0.75rem; margin: 0.5rem 0; }
    .preview-divider-line { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, rgba(0,0,0,0.12), transparent); }
    .preview-divider-text { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #999; white-space: nowrap; }
    .preview-products-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); column-gap: 0.25rem; row-gap: 1.25rem; }
    .preview-product-card { display: flex; flex-direction: column; gap: 0.25rem; padding: 0.375rem; border-radius: 8px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.08); overflow: hidden; max-width: 150px; justify-self: center; }
    .preview-product-img { width: 138px; height: 138px; border-radius: 6px; object-fit: cover; background: #f5f5f5; flex-shrink: 0; align-self: center; }
    .preview-product-img-placeholder { display: flex; align-items: center; justify-content: center; width: 138px; height: 138px; flex-shrink: 0; align-self: center; }
    .preview-product-img-placeholder span { font-size: 2rem; color: #ccc; }
    .preview-product-info { flex: 1; display: flex; flex-direction: column; min-height: 0; }
    .preview-product-info h4 { margin: 0 0 0.125rem; font-size: 0.85rem; line-height: 1.3; }
    .preview-product-info p { margin: 0.125rem 0; font-size: 0.72rem; color: #666; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .preview-sku { font-size: 0.65rem; color: #999; }
    .preview-price { font-weight: 700; font-size: 0.9rem; margin-top: auto; font-family: system-ui, -apple-system, sans-serif; }
    .preview-banner-content { max-width: 600px; margin: 0 auto; }
    .preview-banner-text { font-size: clamp(1.5rem, 3vw, 2.5rem); font-weight: 700; margin: 0 0 0.5rem; }
    .preview-banner-sub { font-size: 1.1rem; margin: 0; }
    .preview-product-detail { display: flex; gap: 2rem; align-items: flex-start; }
    .preview-prod-img { width: 180px; height: 180px; border-radius: 12px; object-fit: cover; background: #f5f5f5; flex-shrink: 0; }
    .preview-prod-img-placeholder { display: flex; align-items: center; justify-content: center; }
    .preview-prod-img-placeholder span { font-size: 3rem; color: #ccc; }
    .preview-prod-info h2 { margin: 0 0 0.5rem; font-size: 1.4rem; }
    .preview-price-lg { font-size: 1.3rem; font-weight: 700; margin: 0.5rem 0; font-family: system-ui, -apple-system, sans-serif; }
    .preview-stock { font-size: 0.85rem; color: #666; }
    .page-footer { text-align: center; background: transparent; flex-shrink: 0; position: relative; height: 0; overflow: visible; }
    .page-number { position: absolute; left: 50%; bottom: 1rem; transform: translateX(-50%); display: inline-flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: rgba(255,255,255,0.9); color: #444; font-size: 0.65rem; font-weight: 600; line-height: 1; }
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
    .preview-header-wrapper { position: sticky; top: 0; z-index: 50; background: rgba(15,19,32,0.95); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255,255,255,0.06); }
    .preview-header { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0.5rem 1rem; gap: 0.5rem; }
    .preview-header-title { font-size: 1rem; font-weight: 700; color: #a5b4fc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
    .preview-header-actions { display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0; }
    .preview-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.6rem; border-radius: 999px; border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #b0b8d4; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; transition: all 0.15s; text-decoration: none; white-space: nowrap; }
    .preview-btn:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .preview-btn-icon { font-size: 1rem !important; }
    .preview-btn-label { display: none; }
    .preview-zoom-label { font-size: 0.7rem; font-weight: 600; color: rgba(255,255,255,0.4); min-width: 2.2rem; text-align: center; }
    .preview-footer { position: fixed; bottom: 0; left: 0; right: 0; z-index: 50; display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0px)); background: rgba(15,19,32,0.85); backdrop-filter: blur(16px); border-top: 1px solid rgba(255,255,255,0.06); gap: 0.25rem; will-change: transform; }
    .preview-footer-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.75rem; border-radius: 999px; border: 1px solid rgba(255,255,255,0.12); color: #b0b8d4; font-size: 0.75rem; font-weight: 500; text-decoration: none; transition: all 0.15s; white-space: nowrap; flex-shrink: 0; }
    .preview-footer-btn:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .preview-footer-btn:active { transform: scale(0.96); }
    .preview-footer-btn.disabled { opacity: 0.25; pointer-events: none; }
    .preview-page-numbers { display: flex; align-items: center; gap: 0.2rem; overflow-x: auto; flex: 1 1 auto; min-width: 0; justify-content: center; scrollbar-width: none; -ms-overflow-style: none; }
    .preview-page-numbers::-webkit-scrollbar { display: none; }
    .preview-page-num { min-width: 1.75rem; height: 1.75rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600; text-decoration: none; color: rgba(255,255,255,0.35); transition: all 0.15s; flex-shrink: 0; }
    .preview-page-num:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .preview-page-num.active { background: rgba(165,180,252,0.15); color: #a5b4fc; font-weight: 700; }
    #previewContainer { flex: 1; overflow: auto; display: flex; align-items: center; justify-content: center; }
    #previewContainer.with-pages { padding: 1.5rem 1rem 5rem; padding-top: 1.5rem; padding-bottom: 5rem; }

    @media (min-width: 640px) {
        .preview-header { padding: 0.5rem 1.5rem; }
        .preview-header-title { max-width: none; font-size: 1.15rem; }
        .preview-btn-label { display: inline; }
        .preview-btn { padding: 0.4rem 0.9rem; font-size: 0.72rem; gap: 0.35rem; }
        #previewContainer.with-pages { padding: 2rem 1.5rem 5rem; padding-bottom: 5rem; }
        #previewContainer.zoom-pad-top { padding-top: 2rem; align-items: flex-start; }
        .preview-footer { padding: 0.5rem 1.5rem; }
    }
    @media (max-width: 767px) {
        body { overflow-y: auto; -webkit-overflow-scrolling: touch; }
        #previewContainer { overflow: visible; align-items: center; min-height: 0; }
        #previewContainer.with-pages { padding-left: 0.5rem; padding-right: 0.5rem; padding-top: 0.25rem; padding-bottom: 4rem; }
        .preview-header { padding: 0.35rem 0.75rem; }
        .preview-footer { position: sticky; bottom: 0; padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0px) + 20px); }
    }
    @media (max-width: 480px) {
        .preview-footer { padding: 0.3rem 0.4rem; padding-bottom: calc(0.3rem + env(safe-area-inset-bottom, 0px) + 20px); gap: 0.15rem; }
        .preview-footer-btn { padding: 0.25rem 0.4rem; font-size: 0.65rem; }
        .preview-page-num { min-width: 1.4rem; height: 1.4rem; font-size: 0.6rem; }
    }


    .flex-1 { flex: 1; }
    .overflow-auto { overflow: auto; }
    .flex { display: flex; }
    .items-start { align-items: flex-start; }
    .justify-center { justify-content: center; }
    @media print { .page-with-bg { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
    </style>
</head>
<body>

<header class="preview-header-wrapper">
<div class="preview-header">
    <span class="preview-header-title"><?= $catalogName ?></span>
    <div class="preview-header-actions">
        <button onclick="abrirModalCompartir()" class="preview-btn preview-btn-always" title="Compartir"><span class="material-symbols-outlined preview-btn-icon">share</span><span class="preview-btn-label">Compartir</span></button>
        <button onclick="zoomOut()" class="preview-btn preview-btn-always" title="Alejar"><span class="material-symbols-outlined preview-btn-icon">zoom_out</span><span class="preview-btn-label">Alejar</span></button>
        <span class="preview-zoom-label" id="zoomLevel">100%</span>
        <button onclick="zoomIn()" class="preview-btn preview-btn-always" title="Acercar"><span class="material-symbols-outlined preview-btn-icon">zoom_in</span><span class="preview-btn-label">Acercar</span></button>
        <button onclick="zoomFit()" class="preview-btn preview-btn-always" title="Ajustar al ancho"><span class="material-symbols-outlined preview-btn-icon">fit_width</span><span class="preview-btn-label">Ajustar</span></button>
<?php if ($isPublicView): ?>
        <?php if ($allowPdf): ?>
        <a href="descargar_pdf.php?id=<?= $catalogId ?><?= $showPrices ? '' : '&no_prices=1' ?>" target="_blank" class="preview-btn preview-btn-always" title="Descargar PDF"><span class="material-symbols-outlined preview-btn-icon">picture_as_pdf</span><span class="preview-btn-label">PDF</span></a>
        <?php endif; ?>
<?php else: ?>
        <a href="export_pdf.php?catalog_id=<?= $catalogId ?>" target="_blank" class="preview-btn preview-btn-always" title="Exportar PDF"><span class="material-symbols-outlined preview-btn-icon">picture_as_pdf</span><span class="preview-btn-label">PDF</span></a>
        <a href="pages.php?catalog_id=<?= $catalogId ?>" class="preview-btn" title="Editar páginas"><span class="material-symbols-outlined preview-btn-icon">edit_note</span><span class="preview-btn-label">Editar</span></a>
        <a href="index.php" class="preview-btn" title="Volver a catálogos"><span class="material-symbols-outlined preview-btn-icon">arrow_back</span><span class="preview-btn-label">Catálogos</span></a>
<?php endif; ?>
    </div>
</div>
</header>

<div class="flex-1 overflow-auto flex items-start justify-center <?= empty($pages) ? '' : 'with-pages' ?>" id="previewContainer">
<?php if (empty($pages)): ?>
<div class="preview-empty">
    <p>Este catálogo está vacío.</p>
<?php if (!$isPublicView): ?>
    <a href="page_create.php?catalog_id=<?= $catalogId ?>" class="text-primary hover:text-primary-fixed-dim transition-colors">Añadir primera página</a>
<?php endif; ?>
</div>
<?php else: ?>
<div class="preview-sheet" id="previewSheet" style="flex-shrink:0;">
<?= $pageContent ?>
<?php if ($pageNum !== null): ?><div class="page-footer"><span class="page-number"><?= $pageNum ?></span></div><?php endif; ?>
</div>
<?php endif; ?>
</div>

<?php if (!empty($pages)): ?>
<nav class="preview-footer">
    <div>
        <?php if ($pageIndex > 1): ?>
        <a class="preview-footer-btn" href="?<?= $isPublicView ? 'id' : 'catalog_id' ?>=<?= $catalogId ?>&page=<?= $pageIndex - 1 ?><?= $showPrices ? '' : '&no_prices=1' ?>" id="prevPage"><span class="material-symbols-outlined" style="font-size:1.1rem">chevron_left</span> Anterior</a>
        <?php else: ?>
        <span class="preview-footer-btn disabled"><span class="material-symbols-outlined" style="font-size:1.1rem">chevron_left</span> Anterior</span>
        <?php endif; ?>
    </div>
    <div class="preview-page-numbers">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?= $isPublicView ? 'id' : 'catalog_id' ?>=<?= $catalogId ?>&page=<?= $i ?><?= $showPrices ? '' : '&no_prices=1' ?>" class="preview-page-num <?= $i === $pageIndex ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <div>
        <?php if ($pageIndex < $totalPages): ?>
        <a class="preview-footer-btn" href="?<?= $isPublicView ? 'id' : 'catalog_id' ?>=<?= $catalogId ?>&page=<?= $pageIndex + 1 ?><?= $showPrices ? '' : '&no_prices=1' ?>" id="nextPage">Siguiente <span class="material-symbols-outlined" style="font-size:1.1rem">chevron_right</span></a>
        <?php else: ?>
        <span class="preview-footer-btn disabled">Siguiente <span class="material-symbols-outlined" style="font-size:1.1rem">chevron_right</span></span>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>

<style>
.toast-share{position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:200;padding:0.6rem 1.2rem;border-radius:999px;background:rgba(0,200,150,0.9);color:#fff;font-size:0.85rem;font-weight:600;opacity:0;transition:opacity 0.3s;pointer-events:none}.toast-share.show{opacity:1}
.modal-overlay{position:fixed;inset:0;z-index:300;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:opacity .25s,visibility .25s}.modal-overlay.open{opacity:1;visibility:visible}.modal-box{background:#1e2133;border-radius:12px;padding:1.5rem;max-width:360px;width:90%;display:flex;flex-direction:column;gap:0.75rem;transform:scale(0.92);transition:transform .25s}.modal-overlay.open .modal-box{transform:scale(1)}.modal-box h3{margin:0;font-size:1rem;font-weight:600;color:#e8ecf6}.modal-btn{display:flex;align-items:center;gap:0.75rem;width:100%;padding:0.75rem 1rem;border:none;border-radius:8px;background:#2a2e42;color:#dee2f4;font-size:0.9rem;font-weight:500;cursor:pointer;transition:background .15s;text-align:left}.modal-btn:hover{background:#353a52}.modal-btn span{font-size:1.25rem}.modal-btn-close{background:transparent;color:#888;justify-content:center;font-size:0.8rem;padding:0.5rem}.modal-btn-close:hover{background:#2a2e42;color:#dee2f4}
</style>
<div class="toast-share" id="toastShare"></div>
<div class="modal-overlay" id="modalCompartir" onclick="if(event.target===this)cerrarModalCompartir()">
    <div class="modal-box">
        <h3>Compartir catálogo</h3>
        <button class="modal-btn" onclick="copiarEnlaceConPrecios()"><span class="material-symbols-outlined">attach_money</span>Compartir URL con precios</button>
        <button class="modal-btn" onclick="copiarEnlaceSinPrecios()"><span class="material-symbols-outlined">money_off</span>Compartir URL sin precios</button>
        <button class="modal-btn modal-btn-close" onclick="cerrarModalCompartir()">Cancelar</button>
    </div>
</div>
<script>
function mostrarToast(msg) {
    var t = document.getElementById('toastShare');
    t.textContent = msg; t.classList.add('show');
    setTimeout(function(){ t.classList.remove('show'); }, 2000);
}
function abrirModalCompartir() {
    document.getElementById('modalCompartir').classList.add('open');
}
function cerrarModalCompartir() {
    document.getElementById('modalCompartir').classList.remove('open');
}
function copiarEnlace(url) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function(){ mostrarToast('¡Enlace copiado!'); cerrarModalCompartir(); });
    } else {
        var ta = document.createElement('textarea');
        ta.value = url; ta.style.position = 'fixed'; ta.style.left = '-9999px';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); mostrarToast('¡Enlace copiado!'); } catch(e) { prompt('Copia el enlace:', url); }
        document.body.removeChild(ta);
        cerrarModalCompartir();
    }
}
function copiarEnlaceConPrecios() { copiarEnlace('<?= $publicUrlWithPrices ?>'); }
function copiarEnlaceSinPrecios() { copiarEnlace('<?= $publicUrlNoPrices ?>'); }
let zoom = 1;
const zoomLabel = document.getElementById('zoomLevel');
const storageKey = '<?= $isPublicView ? 'public' : 'catalog' ?>_zoom_<?= $catalogId ?>';

function loadZoom() {
    try { const v = parseFloat(localStorage.getItem(storageKey)); return isNaN(v) ? null : v; } catch(e) { return null; }
}
function saveZoom() {
    try { localStorage.setItem(storageKey, zoom); } catch(e) {}
}

function applyZoom() {
    const sheet = document.getElementById('previewSheet');
    if (!sheet) return;
    const pct = Math.round(zoom * 100);
    sheet.style.zoom = zoom;
    if (zoomLabel) zoomLabel.textContent = pct + '%';
    const container = document.getElementById('previewContainer');
    if (container) container.classList.toggle('zoom-pad-top', zoom > 0.5);
}

function zoomIn() { zoom = Math.min(Math.round((zoom + 0.05) * 20) / 20, 3); applyZoom(); saveZoom(); }
function zoomOut() { zoom = Math.max(Math.round((zoom - 0.05) * 20) / 20, 0.05); applyZoom(); saveZoom(); }

function zoomFit() {
    const container = document.getElementById('previewContainer');
    const sheet = document.getElementById('previewSheet');
    if (!container || !sheet) { zoom = 1; applyZoom(); saveZoom(); return; }
    const cw = container.clientWidth - 48;
    zoom = Math.min(Math.round((cw / 816) * 20) / 20, 1);
    applyZoom();
    saveZoom();
}

const saved = loadZoom();
if (saved !== null && saved > 0) { zoom = saved; applyZoom(); }
else { zoom = 0.5; applyZoom(); saveZoom(); }

window.addEventListener('resize', function() {
    const s = loadZoom();
    if (s !== null) { zoom = s; applyZoom(); }
    else { zoom = 0.5; applyZoom(); saveZoom(); }
});

// Preserve scroll position across page navigation
if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
(function() {
    const key = '<?= $isPublicView ? 'public' : 'preview' ?>_scroll_' + <?= $catalogId ?>;
    function getScroller() {
        var c = document.getElementById('previewContainer');
        if (!c) return null;
        return window.innerWidth < 768 ? document.documentElement : c;
    }
    window.saveScroll = function() {
        var el = getScroller();
        if (!el) return;
        var st = el === document.documentElement ? window.scrollY || window.pageYOffset || document.documentElement.scrollTop : el.scrollTop;
        if (st < 100) { try { sessionStorage.removeItem(key); } catch(e) {} return; }
        var sh = el === document.documentElement ? document.documentElement.scrollHeight : el.scrollHeight;
        var ch = el === document.documentElement ? window.innerHeight : el.clientHeight;
        var ratio = sh > ch ? st / (sh - ch) : 0;
        try { sessionStorage.setItem(key, ratio); } catch(e) {}
    };
    document.querySelectorAll('.preview-page-num, #prevPage, #nextPage').forEach(function(el) {
        el.addEventListener('click', window.saveScroll);
    });
    window.addEventListener('load', function() {
        var el = getScroller();
        if (!el) return;
        try { var r = parseFloat(sessionStorage.getItem(key)); if (!isNaN(r) && r > 0) { setTimeout(function() { var sh = el === document.documentElement ? document.documentElement.scrollHeight : el.scrollHeight; var ch = el === document.documentElement ? window.innerHeight : el.clientHeight; if (el === document.documentElement) { window.scrollTo(0, r * (sh - ch)); } else { el.scrollTop = r * (sh - ch); } }, 50); } } catch(e) {}
    });
})();
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
        if (typeof window.saveScroll === 'function') window.saveScroll();
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
