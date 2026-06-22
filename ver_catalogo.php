<?php
require_once __DIR__ . '/src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/src/Repositories/CategoryRepository.php';
require_once __DIR__ . '/src/Repositories/ProductRepository.php';
require_once __DIR__ . '/includes/catalog_preview_renderer.php';
require_once __DIR__ . '/includes/upload_helper.php';

$catalogId = (int)($_GET['id'] ?? 0);
if ($catalogId <= 0) { http_response_code(404); echo 'Catálogo no encontrado.'; exit; }

$catRepo = new CatalogRepository();
$catalog = $catRepo->findById($catalogId);
if (!$catalog) { http_response_code(404); echo 'Catálogo no encontrado.'; exit; }

$pageRepo = new CatalogPageRepository();
$categoryRepo = new CategoryRepository();
$productRepo = new ProductRepository();

$pages = $pageRepo->allByCatalog($catalogId);
$categories = $categoryRepo->allByCatalog($catalogId);
$products = $productRepo->allByCatalog($catalogId);
$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');
$GLOBALS['currencySymbol'] = currencySymbol($catalog['currency'] ?? null);

$expandedPages = buildExpandedPages($pages, $products, $categories);
foreach ($expandedPages as &$ep) { $ep['_all_products'] = $products; }
unset($ep);

$totalPages = count($expandedPages);
$pageIndex = max(1, min((int)($_GET['page'] ?? 1), max(1, $totalPages)));
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
$publicUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/ver_catalogo.php?id=' . $catalogId;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $catalogName ?> — Catálogo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: #0f1320; color: #dee2f4; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; }
    .public-header { position: sticky; top: 0; z-index: 50; background: rgba(15,19,32,0.85); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255,255,255,0.06); }
    .public-header-inner { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0.5rem 1rem; gap: 0.5rem; }
    .public-header-title { font-size: 1rem; font-weight: 700; color: #a5b4fc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
    .public-header-actions { display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0; }
    .public-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.6rem; border-radius: 999px; border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #b0b8d4; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; transition: all 0.15s; text-decoration: none; white-space: nowrap; }
    .public-btn:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .public-btn-icon { font-size: 1rem !important; }
    .public-btn-label { display: none; }
    @media (min-width: 640px) {
        .public-header-inner { padding: 0.5rem 1.5rem; }
        .public-header-title { max-width: none; font-size: 1.15rem; }
        .public-btn-label { display: inline; }
        .public-btn { padding: 0.4rem 0.9rem; font-size: 0.72rem; gap: 0.35rem; }
    }
    .preview-sheet { width: 816px; height: 1056px; background: #ffffff; color: #1a1a2e; border-radius: 4px; box-shadow: 0 4px 24px rgba(0,0,0,0.6); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease; transform-origin: center center; position: relative; flex-shrink: 0; }
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
    .page-footer { text-align: center; background: transparent; flex-shrink: 0; position: relative; height: 0; overflow: visible; }
    .page-number { position: absolute; left: 50%; bottom: 1rem; transform: translateX(-50%); display: inline-flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: rgba(255,255,255,0.9); color: #444; font-size: 0.65rem; font-weight: 600; line-height: 1; }
    .preview-empty { text-align: center; padding: 4rem 2rem; }
    .preview-empty p { font-size: 1.2rem; margin-bottom: 1rem; }
    .preview-cover { text-align: center; justify-content: center; }
    .preview-banner { text-align: center; justify-content: center; background: linear-gradient(135deg, #f8f9ff, #eef1ff); }
    .preview-back-cover { text-align: center; justify-content: center; }
    .preview-cover .page-content,
    .preview-back-cover .page-content,
    .preview-banner .page-content { display: flex; flex-direction: column; justify-content: center; align-items: center; }
    .preview-page[style*="background:"] .page-content { display: none; }
    #previewContainer { flex: 1; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    #previewContainer.with-pages { padding: 1.5rem 1rem 5rem; }
    .public-footer { position: fixed; bottom: 0; left: 0; right: 0; z-index: 50; display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0px)); background: rgba(15,19,32,0.85); backdrop-filter: blur(16px); border-top: 1px solid rgba(255,255,255,0.06); gap: 0.25rem; }
    .public-footer-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.75rem; border-radius: 999px; border: 1px solid rgba(255,255,255,0.12); color: #b0b8d4; font-size: 0.75rem; font-weight: 500; text-decoration: none; transition: all 0.15s; white-space: nowrap; flex-shrink: 0; cursor: pointer; }
    .public-footer-btn:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .public-footer-btn:active { transform: scale(0.96); }
    .public-footer-btn.disabled { opacity: 0.25; pointer-events: none; }
    .public-footer-btn.pdf-btn { color: #64c8b4; border-color: rgba(100,200,180,0.3); }
    .public-footer-btn.pdf-btn:hover { background: rgba(100,200,180,0.12); color: #7fffd4; }
    .public-page-numbers { display: flex; align-items: center; gap: 0.2rem; overflow-x: auto; flex: 1 1 auto; min-width: 0; justify-content: center; scrollbar-width: none; -ms-overflow-style: none; }
    .public-page-numbers::-webkit-scrollbar { display: none; }
    .public-page-num { min-width: 1.75rem; height: 1.75rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600; text-decoration: none; color: rgba(255,255,255,0.35); transition: all 0.15s; flex-shrink: 0; }
    .public-page-num:hover { background: rgba(255,255,255,0.06); color: #dee2f4; }
    .public-page-num.active { background: rgba(165,180,252,0.15); color: #a5b4fc; font-weight: 700; }
    .toast { position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 100; padding: 0.6rem 1.2rem; border-radius: 999px; background: rgba(0,200,150,0.9); color: #fff; font-size: 0.85rem; font-weight: 600; opacity: 0; transition: opacity 0.3s; pointer-events: none; }
    .toast.show { opacity: 1; }
    @media (max-width: 480px) {
        .public-footer { padding: 0.3rem 0.4rem; gap: 0.15rem; }
        .public-footer-btn { padding: 0.25rem 0.4rem; font-size: 0.65rem; }
        .public-page-num { min-width: 1.4rem; height: 1.4rem; font-size: 0.6rem; }
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

<div class="toast" id="toast"></div>

<header class="public-header">
<div class="public-header-inner">
    <span class="public-header-title"><?= $catalogName ?></span>
    <div class="public-header-actions">
        <button onclick="copiarEnlace()" class="public-btn" title="Copiar enlace"><span class="material-symbols-outlined public-btn-icon">share</span><span class="public-btn-label">Compartir</span></button>
        <button onclick="zoomOut()" class="public-btn" title="Alejar"><span class="material-symbols-outlined public-btn-icon">zoom_out</span><span class="public-btn-label">Alejar</span></button>
        <span class="preview-zoom-label" id="zoomLevel" style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.4);min-width:2.2rem;text-align:center">100%</span>
        <button onclick="zoomIn()" class="public-btn" title="Acercar"><span class="material-symbols-outlined public-btn-icon">zoom_in</span><span class="public-btn-label">Acercar</span></button>
        <button onclick="zoomFit()" class="public-btn" title="Ajustar"><span class="material-symbols-outlined public-btn-icon">fit_width</span><span class="public-btn-label">Ajustar</span></button>
        <?php if ($allowPdf): ?>
        <a href="descargar_pdf.php?id=<?= $catalogId ?>" target="_blank" class="public-btn" title="Descargar PDF"><span class="material-symbols-outlined public-btn-icon">picture_as_pdf</span><span class="public-btn-label">PDF</span></a>
        <?php endif; ?>
    </div>
</div>
</header>

<div class="flex-1 overflow-auto flex items-start justify-center <?= empty($pages) ? '' : 'with-pages' ?>" id="previewContainer">
<?php if (empty($pages)): ?>
<div class="preview-empty">
    <p>Este catálogo está vacío.</p>
</div>
<?php else: ?>
<div class="preview-sheet" id="previewSheet">
<?= $pageContent ?>
<?php if ($pageNum !== null): ?><div class="page-footer"><span class="page-number"><?= $pageNum ?></span></div><?php endif; ?>
</div>
<?php endif; ?>
</div>

<?php if (!empty($pages)): ?>
<nav class="public-footer">
    <div>
        <?php if ($pageIndex > 1): ?>
        <a class="public-footer-btn" href="?id=<?= $catalogId ?>&page=<?= $pageIndex - 1 ?>" id="prevPage"><span class="material-symbols-outlined" style="font-size:1.1rem">chevron_left</span> Anterior</a>
        <?php else: ?>
        <span class="public-footer-btn disabled"><span class="material-symbols-outlined" style="font-size:1.1rem">chevron_left</span> Anterior</span>
        <?php endif; ?>
    </div>
    <div class="public-page-numbers">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?id=<?= $catalogId ?>&page=<?= $i ?>" class="public-page-num <?= $i === $pageIndex ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <div>
        <?php if ($pageIndex < $totalPages): ?>
        <a class="public-footer-btn" href="?id=<?= $catalogId ?>&page=<?= $pageIndex + 1 ?>" id="nextPage">Siguiente <span class="material-symbols-outlined" style="font-size:1.1rem">chevron_right</span></a>
        <?php else: ?>
        <span class="public-footer-btn disabled">Siguiente <span class="material-symbols-outlined" style="font-size:1.1rem">chevron_right</span></span>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>

<script>
function mostrarToast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg; t.classList.add('show');
    setTimeout(function(){ t.classList.remove('show'); }, 2000);
}
function copiarEnlace() {
    var url = '<?= $publicUrl ?>';
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function(){ mostrarToast('¡Enlace copiado!'); });
    } else {
        var ta = document.createElement('textarea');
        ta.value = url; ta.style.position = 'fixed'; ta.style.left = '-9999px';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); mostrarToast('¡Enlace copiado!'); } catch(e) { prompt('Copia el enlace:', url); }
        document.body.removeChild(ta);
    }
}
let zoom = 1;
const sheet = document.getElementById('previewSheet');
const zoomLabel = document.getElementById('zoomLevel');
function saveZoom() { try { localStorage.setItem('public_zoom_<?= $catalogId ?>', zoom); } catch(e) {} }
function loadZoom() { try { const s = localStorage.getItem('public_zoom_<?= $catalogId ?>'); if (s !== null) return parseFloat(s); } catch(e) {} return null; }
function applyZoom() {
    if (!sheet) return;
    const p = Math.round(zoom * 100);
    sheet.style.transform = 'scale(' + zoom + ')';
    if (zoomLabel) zoomLabel.textContent = p + '%';
}
function zoomIn() { zoom = Math.min(Math.round((zoom + 0.05) * 20) / 20, 3); applyZoom(); saveZoom(); }
function zoomOut() { zoom = Math.max(Math.round((zoom - 0.05) * 20) / 20, 0.05); applyZoom(); saveZoom(); }
function zoomFit() {
    const c = document.getElementById('previewContainer');
    if (!c || !sheet) { zoom = 1; applyZoom(); saveZoom(); return; }
    const cw = c.clientWidth - 48;
    zoom = Math.min(Math.round((cw / 816) * 20) / 20, 1);
    applyZoom(); saveZoom();
}
const saved = loadZoom();
if (saved !== null && saved > 0) { zoom = saved; applyZoom(); }
else if (window.innerWidth < 768) { zoom = 0.45; applyZoom(); saveZoom(); }
else { zoomFit(); }
window.addEventListener('resize', function() {
    const s = loadZoom();
    if (s !== null) { zoom = s; applyZoom(); }
    else if (window.innerWidth < 768) { zoom = 0.45; applyZoom(); saveZoom(); }
    else { zoomFit(); }
});
// Touch swipe
(function(){
    var c = document.getElementById('previewContainer'); if (!c) return;
    var xS = null;
    c.addEventListener('touchstart', function(e){ if (e.touches.length === 1) xS = e.touches[0].clientX; }, {passive:true});
    c.addEventListener('touchend', function(e){
        if (xS === null) return;
        var d = xS - e.changedTouches[0].clientX; xS = null;
        if (Math.abs(d) < 50) return;
        var el = d > 0 ? document.getElementById('nextPage') : document.getElementById('prevPage');
        if (el && el.tagName === 'A') location.href = el.href;
    }, {passive:true});
})();
</script>
</body>
</html>
