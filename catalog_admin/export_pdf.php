<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
require_once __DIR__ . '/../includes/catalog_preview_renderer.php';

catalogRequireLogin();
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
foreach ($expandedPages as &$ep) { $ep['_all_products'] = $products; }
unset($ep);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PDF - <?= $catalogName ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<style>
@page { size: Letter; margin: 0; }
@media print { html, body { width: 215.9mm; height: 279.4mm; }
    .page-with-bg { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .print-page { height: 279.4mm; min-height: 279.4mm; overflow: hidden; }
    body { overflow: visible; }
}
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif; background: #0f1320; color: #1a1a2e; overflow-x: hidden; }
.print-wrapper { display: flex; flex-direction: column; align-items: center; padding: 0.5rem; transform-origin: top center; }
.print-page { page-break-after: always; width: 215.9mm; min-height: 279.4mm; overflow: hidden; display: flex; flex-direction: column; background: #ffffff; margin: 1rem 0; border-radius: 4px; box-shadow: 0 4px 24px rgba(0,0,0,0.4); flex-shrink: 0; }
.print-page:last-child { page-break-after: auto; }
.print-page-bleed { padding: 0; }
.print-page-bleed .page-content { padding: 2cm; }
.page-content { padding: 2.75rem; }
.preview-page { flex: 1; display: flex; flex-direction: column; }
.page-with-bg { overflow: hidden; }

.preview-title-lg { font-size: 2.5rem; font-weight: 800; margin: 0 0 0.5rem; text-align: center; }
.preview-title-md { font-size: 1.4rem; font-weight: 700; margin: 0 0 0.5rem; }
.preview-subtitle { font-size: 1.1rem; color: #666; text-align: center; }
.preview-cover { text-align: center; justify-content: center; }
.preview-back-cover { text-align: center; justify-content: center; }
.preview-category { flex: 1; }
.preview-product { flex: 1; }
.preview-index { flex: 1; }
.preview-custom { flex: 1; }
.preview-cover .page-content,
.preview-back-cover .page-content,
.preview-banner .page-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.print-page-bleed .page-content { display: none; }
.preview-index-list { list-style: none; padding: 0; margin: 0; }
.preview-index-list li { padding: 0.4rem 0; border-bottom: 1px solid #eee; font-size: 0.95rem; }
.index-num { font-weight: 700; margin-right: 0.5rem; }
.preview-cover-content { max-width: 600px; margin: 0 auto; }
.preview-category-content { display: flex; flex-direction: column; gap: 0.5rem; overflow: hidden; flex: 1; }
.preview-cat-name { font-size: 0.95rem; color: #888; margin-top: -0.5rem; margin-bottom: 1rem; }
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
.preview-product-img-placeholder { display: flex; align-items: center; justify-content: center; background: #f0f0f0; width: 138px; height: 138px; flex-shrink: 0; align-self: center; }
.preview-product-img-placeholder span { font-size: 2rem; color: #ccc; }
.preview-product-info { flex: 1; display: flex; flex-direction: column; min-height: 0; }
.preview-product-info h4 { margin: 0 0 0.125rem; font-size: 0.85rem; line-height: 1.3; }
.preview-product-info p { margin: 0.125rem 0; font-size: 0.72rem; color: #666; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.preview-sku { font-size: 0.65rem; color: #999; }
.preview-price { font-weight: 700; font-size: 0.9rem; margin-top: auto; }
.preview-banner { text-align: center; justify-content: center; background: linear-gradient(135deg, #f8f9ff, #eef1ff); }
.preview-banner-content { max-width: 600px; margin: 0 auto; }
.preview-banner-text { font-size: 2rem; font-weight: 700; margin: 0 0 0.5rem; }
.preview-banner-sub { font-size: 1.1rem; color: #666; margin: 0; }
.preview-product-detail { display: flex; gap: 1.5rem; align-items: flex-start; }
.preview-prod-img { width: 180px; height: 180px; border-radius: 12px; object-fit: cover; background: #f5f5f5; flex-shrink: 0; }
.preview-prod-img-placeholder { display: flex; align-items: center; justify-content: center; background: #f0f0f0; }
.preview-prod-img-placeholder span { font-size: 3rem; color: #ccc; }
.preview-prod-info h2 { margin: 0 0 0.5rem; font-size: 1.4rem; }
.preview-price-lg { font-size: 1.3rem; font-weight: 700; margin: 0.5rem 0; }
.preview-stock { font-size: 0.85rem; color: #666; }
.preview-empty { text-align: center; padding: 4rem 2rem; font-size: 1.2rem; }
.print-content { padding-top: 180px; display: flex; flex-direction: column; align-items: center; overflow: hidden; }
@media print { body { background: #fff; color: #000; overflow: visible; } .no-print { display: none !important; } .print-content { padding-top: 0 !important; } .print-wrapper { padding: 0; transform: none !important; } .print-page { margin: 0; border-radius: 0; box-shadow: none; } }
@media (max-width: 480px) { .no-print { padding: 1rem !important; } .no-print h2 { font-size: 1rem !important; } .no-print p { font-size: 0.75rem !important; } .no-print-btn-wrap { flex-direction: column; align-items: center; gap: 0.5rem; } .no-print-btn-wrap a { margin-left: 0 !important; } }
</style>
</head>
<body>
<div class="no-print" style="position:fixed;top:0;left:0;right:0;z-index:9999;background:#0f1320;color:#dee2f4;padding:1.5rem;text-align:center;font-family:'Inter',sans-serif;">
<div style="max-width:600px;margin:0 auto;">
<div style="font-size:2rem;margin-bottom:0.5rem;">📄</div>
<h2 style="margin:0 0 0.3rem;font-size:1.2rem;">Generando PDF: <?= $catalogName ?></h2>
<p style="margin:0;font-size:0.85rem;color:rgba(255,255,255,0.6);">Se abrirá el cuadro de diálogo para guardar el PDF. Selecciona "Guardar como PDF" y haz clic en guardar.</p>
<p style="margin:0.5rem 0 0;font-size:0.8rem;color:rgba(255,255,255,0.45);">⚠️ Importante: en el diálogo de impresión, activa la opción <strong>"Imprimir imágenes y colores de fondo"</strong> (Chrome) o equivalente para que las imágenes de portada/fondos se vean en el PDF.</p>
<div id="progressSection" style="margin:0.75rem auto 0;max-width:400px;">
<div style="display:flex;justify-content:space-between;font-size:0.75rem;color:rgba(255,255,255,0.5);margin-bottom:0.25rem;">
<span id="progressLabel">Preparando PDF...</span>
<span id="progressPct">0%</span>
</div>
<div style="width:100%;height:6px;background:rgba(255,255,255,0.1);border-radius:3px;overflow:hidden;">
<div id="progressBar" style="width:0%;height:100%;background:linear-gradient(90deg,#6c8cff,#5ce1e6);border-radius:3px;transition:width 0.3s;"></div>
</div>
</div>
<div class="no-print-btn-wrap" style="display:flex;justify-content:center;gap:0.5rem;margin-top:1rem;">
<button id="printBtn" onclick="handlePrintClick()" style="padding:0.5rem 1.5rem;border-radius:999px;border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.1);color:#fff;cursor:pointer;font-size:0.9rem;white-space:nowrap;" disabled>Abrir diálogo de PDF</button>
<a href="preview.php?catalog_id=<?= $catalogId ?>" style="padding:0.5rem 1.5rem;border-radius:999px;border:1px solid rgba(255,255,255,0.3);color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.9rem;white-space:nowrap;">Volver</a>
</div>
</div>
</div>
<div class="print-content">
<?php if (empty($expandedPages)): ?>
<div class="print-wrapper"><div class="print-page preview-empty">Este catálogo no tiene páginas.</div></div>
<?php else: ?>
<div class="print-wrapper">
<?php foreach ($expandedPages as $p): ?>
<div class="print-page<?= !empty($p['background_image']) ? ' print-page-bleed' : '' ?>"><?= renderPageContent($p, $categories, $expandedPages) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<script>
var printReady = false;

function fitPrintPages() {
    var wrapper = document.querySelector('.print-wrapper');
    if (!wrapper) return;
    var parent = wrapper.parentElement;
    var avail = parent ? parent.clientWidth : window.innerWidth;
    avail = Math.max(avail, 320);
    var scale = Math.min((avail - 8) / 816, 1);
    wrapper.style.transform = scale < 1 ? 'scale(' + scale + ')' : '';
}

function handlePrintClick() {
    if (printReady) { window.print(); return; }
}

function preloadImages(callback) {
    var container = document.querySelector('.print-wrapper');
    if (!container) { callback(); return; }

    var tasks = [];

    container.querySelectorAll('img').forEach(function(img) {
        tasks.push(function(done) {
            if (img.complete && img.naturalWidth > 0) { done(); return; }
            var retries = 0;
            var maxRetries = 3;
            function tryLoad() {
                img.onload = function() { done(); };
                img.onerror = function() {
                    retries++;
                    if (retries < maxRetries) {
                        setTimeout(function() {
                            img.src = img.src;
                        }, 1000 * retries);
                    } else {
                        done();
                    }
                };
            }
            tryLoad();
        });
    });

    var seen = {};
    container.querySelectorAll('[style*="background:"], [style*="background-image"]').forEach(function(el) {
        var s = el.getAttribute('style') || '';
        var matches = s.match(/url\(['"]?([^)'"]+)['"]?\)/g);
        if (matches) matches.forEach(function(m) {
            var url = m.replace(/^url\(['"]?/, '').replace(/['"]?\)$/, '');
            if (url && !seen[url]) {
                seen[url] = true;
                tasks.push(function(done) {
                    var retries = 0;
                    var maxRetries = 3;
                    function tryLoad() {
                        var tmp = new Image();
                        tmp.onload = function() { done(); };
                        tmp.onerror = function() {
                            retries++;
                            if (retries < maxRetries) {
                                setTimeout(tryLoad, 1000 * retries);
                            } else {
                                done();
                            }
                        };
                        tmp.src = url;
                    }
                    tryLoad();
                });
            }
        });
    });

    var total = tasks.length;
    var loaded = 0;
    var progressBar = document.getElementById('progressBar');
    var progressPct = document.getElementById('progressPct');
    var progressLabel = document.getElementById('progressLabel');
    var printBtn = document.getElementById('printBtn');

    if (total === 0) {
        if (progressLabel) progressLabel.textContent = 'PDF listo';
        if (progressBar) progressBar.style.width = '100%';
        if (progressPct) progressPct.textContent = '100%';
        if (printBtn) { printBtn.disabled = false; printBtn.textContent = 'Abrir diálogo de PDF'; }
        setTimeout(callback, 300);
        return;
    }

    function oneDone() {
        loaded++;
        var pct = Math.round((loaded / total) * 100);
        if (progressBar) progressBar.style.width = pct + '%';
        if (progressPct) progressPct.textContent = pct + '%';
        if (progressLabel) progressLabel.textContent = 'Cargando ' + loaded + '/' + total;
        if (loaded >= total) {
            if (progressLabel) progressLabel.textContent = 'PDF listo';
            if (printBtn) { printBtn.disabled = false; printBtn.textContent = 'Abrir diálogo de PDF'; }
            setTimeout(callback, 500);
        }
    }

    tasks.forEach(function(task) { task(oneDone); });
}

window.addEventListener('load', function() {
    fitPrintPages();
    preloadImages(function() {
        printReady = true;
        setTimeout(function(){ window.print(); }, 300);
    });
});

window.addEventListener('resize', fitPrintPages);

// Max safety timeout: print anyway after 20 seconds even if images fail
setTimeout(function() {
    if (!printReady) {
        printReady = true;
        var printBtn = document.getElementById('printBtn');
        if (printBtn) { printBtn.disabled = false; printBtn.textContent = 'Abrir diálogo de PDF'; }
        var progressLabel = document.getElementById('progressLabel');
        if (progressLabel) progressLabel.textContent = 'PDF listo';
        var progressBar = document.getElementById('progressBar');
        if (progressBar) progressBar.style.width = '100%';
        var progressPct = document.getElementById('progressPct');
        if (progressPct) progressPct.textContent = '100%';
    }
}, 20000);
</script>
</body>
</html>
