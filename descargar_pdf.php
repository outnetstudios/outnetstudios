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
if (!$catalog || empty($catalog['public_pdf_download'])) {
    http_response_code(403);
    echo 'Descarga de PDF no disponible para este catálogo.';
    exit;
}

$pageRepo = new CatalogPageRepository();
$categoryRepo = new CategoryRepository();
$productRepo = new ProductRepository();

$pages = $pageRepo->allByCatalog($catalogId);
$categories = $categoryRepo->allByCatalog($catalogId);
$products = $productRepo->allByCatalog($catalogId);
$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');
$currencySymbol = currencySymbol($catalog['currency'] ?? null);
$cover = null;
$backCover = null;
foreach ($pages as $p) {
    if ($p['page_type'] === 'cover') $cover = $p;
    if ($p['page_type'] === 'back_cover') $backCover = $p;
}
$coverImage = $cover['background_image'] ?? $catalog['cover_image'] ?? null;
$backCoverImage = $backCover['background_image'] ?? $catalog['back_cover_image'] ?? null;
$GLOBALS['currencySymbol'] = $currencySymbol;

$expandedPages = buildExpandedPages($pages, $products, $categories);
foreach ($expandedPages as &$ep) { $ep['_all_products'] = $products; }
unset($ep);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $catalogName ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
@page { size: Letter; margin: 0; }
@media print { html, body { width: 215.9mm; height: 279.4mm; }
    .page-with-bg { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .print-page { height: 279.4mm; min-height: 279.4mm; overflow: hidden; page-break-after: always; }
    body { overflow: visible; }
}
*,*::before,*::after{box-sizing:border-box}
body{margin:0;font-family:'Inter',sans-serif;color:#1a1a2e}
.print-page{width:215.9mm;height:279.4mm;overflow:hidden;display:flex;flex-direction:column;page-break-after:always;position:relative}
.print-page .page-content{padding:22mm 18mm;flex:1;display:flex;flex-direction:column}
.print-cover,.print-back-cover{justify-content:center;align-items:center;text-align:center}
.print-cover .page-content,.print-back-cover .page-content{justify-content:center;align-items:center}
.print-title-lg{font-size:36pt;font-weight:800;margin:0 0 8pt;letter-spacing:-0.03em}
.print-title-md{font-size:18pt;font-weight:700;margin:0 0 8pt}
.print-index-list{list-style:none;padding:0;margin:0}
.print-index-list li{padding:6pt 0;border-bottom:1px solid rgba(0,0,0,0.06);font-size:11pt}
.index-num{font-weight:700;margin-right:6pt}
.print-cat-thumb{width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid rgba(0,0,0,0.08)}
.print-cat-title{font-size:13pt;font-weight:700;margin:0}
.print-category-content{display:flex;flex-direction:column;gap:6pt;flex:1}
.print-category-section{display:flex;flex-direction:column;gap:8pt}
.print-category-header{display:flex;align-items:center;gap:6pt;padding-top:4pt}
.print-products-grid-4{display:grid;grid-template-columns:repeat(4,1fr);column-gap:4pt;row-gap:14pt}
.print-product-card{display:flex;flex-direction:column;gap:3pt;padding:4pt;border-radius:6px;background:rgba(0,0,0,0.02);border:1px solid rgba(0,0,0,0.08)}
.print-product-img{width:100%;aspect-ratio:1;border-radius:4px;object-fit:cover;background:#f5f5f5}
.print-product-info h4{margin:0 0 2pt;font-size:9pt;line-height:1.3}
.print-sku{font-size:7pt;color:#999}
.print-price{font-weight:700;font-size:10pt;margin-top:auto}
.print-banner{justify-content:center;align-items:center;text-align:center;background:linear-gradient(135deg,#f8f9ff,#eef1ff)}
.print-banner-text{font-size:28pt;font-weight:700;margin:0 0 8pt}
.print-product-detail{display:flex;gap:18pt;align-items:flex-start}
.print-prod-img{width:160px;height:160px;border-radius:10px;object-fit:cover;background:#f5f5f5;flex-shrink:0}
.print-prod-info h2{margin:0 0 6pt;font-size:16pt}
.print-price-lg{font-size:14pt;font-weight:700;margin:6pt 0}
.print-stock{font-size:9pt;color:#666}
.print-page-num{position:absolute;bottom:14pt;left:50%;transform:translateX(-50%);font-size:8pt;color:#999;font-weight:600}
.print-footer{text-align:center;flex-shrink:0;position:relative;height:0;overflow:visible}
.print-page-num-el{position:absolute;left:50%;bottom:14pt;transform:translateX(-50%);display:inline-flex;align-items:center;justify-content:center;width:16pt;height:16pt;border-radius:50%;background:rgba(255,255,255,0.9);color:#444;font-size:7pt;font-weight:600}
</style>
</head>
<body>
<?php
$pageNumCounter = 0;
foreach ($expandedPages as $i => $page):
    $content = renderPageContent($page, $categories, $expandedPages);
    $isNum = !in_array($page['page_type'], ['cover','back_cover','index'], true);
    if ($isNum) $pageNumCounter++;
?>
<div class="print-page page-with-bg"<?php if (!empty($page['background_image'])): ?> style="background:url(<?= htmlspecialchars(imageUrl($page['background_image']), ENT_QUOTES, 'UTF-8') ?>) center/cover no-repeat"<?php endif; ?>>
    <?= $content ?>
    <?php if ($isNum): ?><div class="print-footer"><span class="print-page-num-el"><?= $pageNumCounter ?></span></div><?php endif; ?>
</div>
<?php endforeach; ?>
</body>
</html>
