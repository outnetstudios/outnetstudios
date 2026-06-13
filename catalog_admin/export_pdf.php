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
<style>
@page { size: Letter; margin: 0; }
@media print { html, body { width: 215.9mm; height: 279.4mm; }
    .page-with-bg { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .print-page { height: 279.4mm; min-height: 279.4mm; overflow: hidden; }
}
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif; background: #fff; color: #1a1a2e; }
.print-page { page-break-after: always; width: 215.9mm; min-height: 279.4mm; overflow: hidden; display: flex; flex-direction: column; background: #ffffff; }
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
.preview-category-content { display: flex; flex-direction: column; gap: 0.75rem; overflow: hidden; flex: 1; }
.preview-cat-name { font-size: 0.95rem; color: #888; margin-top: -0.5rem; margin-bottom: 1rem; }
.preview-category-section { display: flex; flex-direction: column; gap: 0.5rem; }
.preview-category-header { display: flex; align-items: center; gap: 0.5rem; }
.preview-cat-thumb { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(0,0,0,0.08); }
.preview-cat-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
.preview-cat-desc { font-size: 0.8rem; color: #888; margin: 0.15rem 0 0; }
.preview-category-divider { display: flex; align-items: center; gap: 0.75rem; margin: 0.5rem 0; }
.preview-divider-line { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, rgba(0,0,0,0.12), transparent); }
.preview-divider-text { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #999; white-space: nowrap; }
.preview-products-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.25rem; }
.preview-product-card { display: flex; flex-direction: column; gap: 0.25rem; padding: 0.375rem; border-radius: 8px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.08); height: 159px; overflow: hidden; }
.preview-product-img { width: 100%; height: 86px; border-radius: 6px; object-fit: contain; background: #f5f5f5; }
.preview-product-img-placeholder { display: flex; align-items: center; justify-content: center; background: #f0f0f0; height: 86px; }
.preview-product-img-placeholder span { font-size: 2rem; color: #ccc; }
.preview-product-info h4 { margin: 0 0 0.125rem; font-size: 0.85rem; line-height: 1.3; }
.preview-product-info p { margin: 0.125rem 0; font-size: 0.72rem; color: #666; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
.preview-sku { font-size: 0.65rem; color: #999; }
.preview-price { font-weight: 700; font-size: 0.9rem; margin-top: 0.125rem; }
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
.print-content { padding-top: 160px; }
@media print { body { background: #fff; color: #000; } .no-print { display: none !important; } .print-content { padding-top: 0 !important; } }
</style>
</head>
<body>
<div class="no-print" style="position:fixed;top:0;left:0;right:0;z-index:9999;background:#0f1320;color:#dee2f4;padding:1.5rem;text-align:center;font-family:'Inter',sans-serif;">
<div style="max-width:600px;margin:0 auto;">
<div style="font-size:2rem;margin-bottom:0.5rem;">📄</div>
<h2 style="margin:0 0 0.3rem;font-size:1.2rem;">Generando PDF: <?= $catalogName ?></h2>
<p style="margin:0;font-size:0.85rem;color:rgba(255,255,255,0.6);">Se abrirá el cuadro de diálogo para guardar el PDF. Selecciona "Guardar como PDF" y haz clic en guardar.</p>
<p style="margin:0.5rem 0 0;font-size:0.8rem;color:rgba(255,255,255,0.45);">⚠️ Importante: en el diálogo de impresión, activa la opción <strong>"Imprimir imágenes y colores de fondo"</strong> (Chrome) o equivalente para que las imágenes de portada/fondos se vean en el PDF.</p>
<button onclick="window.print()" style="margin-top:1rem;padding:0.5rem 1.5rem;border-radius:999px;border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.1);color:#fff;cursor:pointer;font-size:0.9rem;">Abrir diálogo de PDF</button>
<a href="preview.php?catalog_id=<?= $catalogId ?>" style="display:inline-block;margin-top:1rem;margin-left:0.5rem;padding:0.5rem 1.5rem;border-radius:999px;border:1px solid rgba(255,255,255,0.3);color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.9rem;">Volver</a>
</div>
</div>
<div class="print-content">
<?php if (empty($expandedPages)): ?>
<div class="print-page preview-empty">Este catálogo no tiene páginas.</div>
<?php else: ?>
<?php foreach ($expandedPages as $p): ?>
<div class="print-page<?= !empty($p['background_image']) ? ' print-page-bleed' : '' ?>"><?= renderPageContent($p, $categories, $expandedPages) ?></div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<script>
window.addEventListener('load', function() {
    setTimeout(function(){ window.print(); }, 1000);
});
</script>
</body>
</html>
