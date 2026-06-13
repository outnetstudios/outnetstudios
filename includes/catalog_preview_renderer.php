<?php
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
require_once __DIR__ . '/../includes/upload_helper.php';

/**
 * Calculate how many products from $candidate fully fit on one Letter page (828px).
 * Uses iterative fit: tries 12, counts distinct categories in that range, calculates
 * available rows, reduces if needed, and re-evaluates (categories may shrink).
 */
function fitCount(array $candidate): int
{
    $pageH = 1056;
    $pad = 88;                // padding 44×2
    $firstCat = 52;           // header(40) + section gap(12)
    $extraCat = 98;           // content gap(8) + divider-margin(8+14+8) + content gap(8) + header(40) + section gap(12)
    $rowH = 229;              // card ~209 + row-gap 20 (4 rows = 16 prods for 1 cat)
    $rowGap = 20;             // CSS row-gap in px

    $availTotal = $pageH - $pad; // 968 before first cat overhead

    // Build ordered map of category → product list from $candidate
    $byCat = [];
    $catOrder = [];
    foreach ($candidate as $i => $p) {
        $cid = (int)$p['category_id'];
        if (!isset($byCat[$cid])) { $byCat[$cid] = []; $catOrder[] = $cid; }
        $byCat[$cid][] = $p;
    }

    $total = count($candidate);
    // Determine the fit by trying n = total down to 0
    for ($n = $total; $n >= 0; $n--) {
        $floorN = $n;
        // Walk through categories sequentially and see how many rows this n consumes
        $rem = $floorN;
        $nc = 0;
        $totalRows = 0;
        foreach ($catOrder as $cid) {
            $cnt = count($byCat[$cid]);
            if ($rem <= 0) break;
            $take = min($cnt, $rem);
            $nc++;
            $totalRows += (int)ceil($take / 4);
            $rem -= $take;
        }
        $overhead = $firstCat + max(0, $nc - 1) * $extraCat;
        $rowsAvail = max(1, intdiv($availTotal - $overhead + $rowGap, $rowH));
        if ($totalRows <= $rowsAvail) return $floorN;
    }
    return 0;
}

/**
 * Build expanded page list: category pages consume products sequentially from a flat
 * pool (sorted by category → product). `fitCount()` determines exactly how many products
 * fit per page so nothing overflows or gets clipped. Non-category pages pass through.
 */
function buildExpandedPages(array $pages, array $products, array $categories): array
{
    $expanded = [];

    $pool = array_values(array_filter($products, fn($p) => $p['status'] === 'active'));

    $catOrder = [];
    foreach ($categories as $i => $c) $catOrder[(int)$c['id']] = $i;
    usort($pool, function ($a, $b) use ($catOrder) {
        $ao = $catOrder[(int)$a['category_id']] ?? 999;
        $bo = $catOrder[(int)$b['category_id']] ?? 999;
        if ($ao !== $bo) return $ao - $bo;
        return (int)$a['sort_order'] - (int)$b['sort_order'];
    });

    $idx = 0;
    $total = count($pool);

    foreach ($pages as $page) {
        if ($page['page_type'] !== 'category') {
            $page['_assigned_products'] = [];
            $expanded[] = $page;
            continue;
        }

        if ($idx >= $total) {
            $page['_assigned_products'] = [];
            $expanded[] = $page;
            continue;
        }

        $remaining = array_slice($pool, $idx);
        $take = fitCount($remaining);
        $chunk = array_slice($pool, $idx, $take);
        $idx += count($chunk);
        $page['_assigned_products'] = $chunk;
        $expanded[] = $page;
    }

    return $expanded;
}

function currencySymbol(?string $currency): string {
    return $currency === 'USD' ? '$' : 'C$';
}

$pageTypeLabels = [
    'cover' => 'Portada',
    'index' => 'Índice',
    'category' => 'Categoría',
    'banner' => 'Banner',
    'product' => 'Producto',
    'back_cover' => 'Contraportada',
    'custom' => 'Personalizada',
];

function pageBgStyle(array $page): string
{
    if (!empty($page['background_image'])) {
        $src = imageUrl($page['background_image']);
        return ' style="background:url(' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . ') center/cover no-repeat;background-position:center center;"';
    }
    return '';
}

function pageBgImg(array $page): string
{
    return '';
}

function renderPageContent(array $page, array $categories, array $allExpandedPages = []): string
{
    $content = $page['content_json'] ? json_decode($page['content_json'], true) : [];
    $html = '';
    // _all_products is passed for product page lookups
    $allProducts = $page['_all_products'] ?? [];

    switch ($page['page_type']) {
        case 'cover':
            $html = renderCover($page, $content);
            break;
        case 'index':
            $html = renderIndex($page, $categories, $allExpandedPages);
            break;
        case 'category':
            $html = renderCategoryPage($page, $content, $categories);
            break;
        case 'banner':
            $html = renderBanner($page, $content);
            break;
        case 'product':
            $html = renderProductPage($page, $content, $allProducts);
            break;
        case 'back_cover':
            $html = renderBackCover($page, $content);
            break;
        default:
            $html = renderCustom($page, $content);
    }

    return $html;
}

function renderCover(array $page, array $content): string
{
    $title = htmlspecialchars($page['title'] ?? 'Portada', ENT_QUOTES, 'UTF-8');
    return '<div class="preview-page preview-cover page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="preview-cover-content page-content">
            <h1 class="preview-title-lg">' . $title . '</h1>
        </div>
    </div>';
}

function renderIndex(array $page, array $categories, array $allExpandedPages = []): string
{
    $title = htmlspecialchars($page['title'] ?? 'Índice', ENT_QUOTES, 'UTF-8');

    if (empty($allExpandedPages)) {
        return '<div class="preview-page preview-index page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
            <div class="page-content"><h2 class="preview-title-md">' . $title . '</h2>
            <p style="color:#888;padding:1rem 0;">Sin páginas.</p></div>
        </div>';
    }

    $catMap = [];
    foreach ($categories as $c) {
        $catMap[(int)$c['id']] = $c['name'];
    }

    $items = '';
    $num = 0;
    foreach ($allExpandedPages as $p) {
        if (in_array($p['page_type'], ['index', 'cover', 'back_cover'], true)) continue;
        $num++;

        if ($p['page_type'] === 'category') {
            $assigned = $p['_assigned_products'] ?? [];
            $names = [];
            $seen = [];
            foreach ($assigned as $prod) {
                $cid = (int)$prod['category_id'];
                if (isset($seen[$cid])) continue;
                $seen[$cid] = true;
                $names[] = htmlspecialchars($catMap[$cid] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8');
            }
            $entryText = !empty($names) ? implode(', ', $names) : 'Categoría';
        } else {
            $entryText = htmlspecialchars($p['title'] ?: ($GLOBALS['pageTypeLabels'][$p['page_type']] ?? $p['page_type']), ENT_QUOTES, 'UTF-8');
        }

        $items .= '<li><span class="index-num">' . $num . '.</span> ' . $entryText . '</li>';
    }

    return '<div class="preview-page preview-index page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="page-content">
            <h2 class="preview-title-md">' . $title . '</h2>
            <ul class="preview-index-list">' . $items . '</ul>
        </div>
    </div>';
}

function renderCategoryPage(array $page, array $content, array $categories): string
{
    $assigned = $page['_assigned_products'] ?? [];

    $catMap = [];
    foreach ($categories as $c) {
        $catMap[(int)$c['id']] = $c;
    }

    // Group assigned products by category
    $grouped = [];
    foreach ($assigned as $prod) {
        $cid = (int)$prod['category_id'];
        if (!isset($grouped[$cid])) $grouped[$cid] = [];
        $grouped[$cid][] = $prod;
    }

    $grid = '';
    $sectionIndex = 0;
    foreach ($grouped as $cid => $prods) {
        $catName = 'Sin categoría';
        $catDesc = '';
        $catImage = null;
        if ($cid > 0 && isset($catMap[$cid])) {
            $catName = htmlspecialchars($catMap[$cid]['name'], ENT_QUOTES, 'UTF-8');
            $catDesc = !empty($catMap[$cid]['description']) ? htmlspecialchars($catMap[$cid]['description'], ENT_QUOTES, 'UTF-8') : '';
            $catImage = !empty($catMap[$cid]['image']) ? $catMap[$cid]['image'] : null;
        }

        if ($sectionIndex > 0) {
            $grid .= '<div class="preview-category-divider"><span class="preview-divider-line"></span><span class="preview-divider-text">' . $catName . '</span><span class="preview-divider-line"></span></div>';
        }

        $grid .= '<div class="preview-category-section">';
        $grid .= '<div class="preview-category-header">';
        if ($catImage) {
            $grid .= '<img src="' . htmlspecialchars(imageUrl($catImage), ENT_QUOTES, 'UTF-8') . '" class="preview-cat-thumb" alt="">';
        }
        $grid .= '<div><h3 class="preview-cat-title">' . $catName . '</h3>';
        if ($catDesc) $grid .= '<p class="preview-cat-desc">' . $catDesc . '</p>';
        $grid .= '</div></div>';

        $grid .= '<div class="preview-products-grid-4">';
        foreach ($prods as $prod) {
            $pName = htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8');
            $pPrice = $prod['price'] !== null ? number_format((float)$prod['price'], 2) : '';
            $pSku = htmlspecialchars($prod['sku'] ?? '', ENT_QUOTES, 'UTF-8');
            $imgSrc = !empty($prod['main_image']) ? imageUrl($prod['main_image']) : '';
            $imgTag = $imgSrc ? '<img src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" class="preview-product-img" alt="' . $pName . '">' : '<div class="preview-product-img preview-product-img-placeholder"><span class="material-symbols-outlined">image</span></div>';

            $grid .= '<div class="preview-product-card">
                ' . $imgTag . '
                <div class="preview-product-info">
                    <h4>' . $pName . '</h4>
                    ' . ($pSku ? '<span class="preview-sku">' . $pSku . '</span>' : '') . '
                    ' . ($pPrice ? '<div class="preview-price">' . $GLOBALS['currencySymbol'] . $pPrice . '</div>' : '') . '
                </div>
            </div>';
        }
        $grid .= '</div>';
        $grid .= '</div>';
        $sectionIndex++;
    }

    if (empty($grid)) {
        $grid = '<p style="text-align:center; color:#888; padding:2rem;">No hay productos activos.</p>';
    }

    return '<div class="preview-page preview-category page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="page-content">
            <div class="preview-category-content">' . $grid . '</div>
        </div>
    </div>';
}

function renderBanner(array $page, array $content): string
{
    $title = htmlspecialchars($page['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $text = htmlspecialchars($content['text'] ?? '', ENT_QUOTES, 'UTF-8');
    $subtitle = htmlspecialchars($content['subtitle'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<div class="preview-page preview-banner page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="preview-banner-content page-content">
            ' . ($text ? '<h2 class="preview-banner-text">' . $text . '</h2>' : '') . '
            ' . ($subtitle ? '<p class="preview-banner-sub">' . $subtitle . '</p>' : '') . '
            ' . ($title ? '<p class="preview-banner-title">' . $title . '</p>' : '') . '
        </div>
    </div>';
}

function renderProductPage(array $page, array $content, array $products): string
{
    $title = htmlspecialchars($page['title'] ?? 'Producto', ENT_QUOTES, 'UTF-8');
    $prodId = (int)($content['product_id'] ?? 0);
    $prod = null;
    foreach ($products as $p) {
        if ((int)$p['id'] === $prodId) { $prod = $p; break; }
    }
    if (!$prod) {
        return '<div class="preview-page"><p style="text-align:center;padding:2rem;">Producto no encontrado.</p></div>';
    }
    $pName = htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8');
    $pPrice = $prod['price'] !== null ? number_format((float)$prod['price'], 2) : '';
    $pSku = htmlspecialchars($prod['sku'] ?? '', ENT_QUOTES, 'UTF-8');
    $pDesc = htmlspecialchars($prod['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $pStock = $prod['stock'] !== null ? (int)$prod['stock'] : null;
    $imgSrc = !empty($prod['main_image']) ? imageUrl($prod['main_image']) : '';
    $imgTag = $imgSrc ? '<img src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" class="preview-prod-img" alt="' . $pName . '">' : '<div class="preview-prod-img preview-prod-img-placeholder"><span class="material-symbols-outlined">image</span></div>';
    return '<div class="preview-page preview-product page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="page-content">
            <div class="preview-product-detail">
                ' . $imgTag . '
                <div class="preview-prod-info">
                    <h2>' . $pName . '</h2>
                    ' . ($pSku ? '<span class="preview-sku">SKU: ' . $pSku . '</span>' : '') . '
                    ' . ($pPrice ? '<div class="preview-price-lg">' . $GLOBALS['currencySymbol'] . $pPrice . '</div>' : '') . '
                    ' . ($pStock !== null ? '<span class="preview-stock">Stock: ' . $pStock . '</span>' : '') . '
                    <p>' . nl2br($pDesc) . '</p>
                </div>
            </div>
        </div>
    </div>';
}

function renderBackCover(array $page, array $content): string
{
    $title = htmlspecialchars($page['title'] ?? 'Contraportada', ENT_QUOTES, 'UTF-8');
    return '<div class="preview-page preview-back-cover page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="preview-cover-content page-content">
            <h2 class="preview-title-md">' . $title . '</h2>
        </div>
    </div>';
}

function renderCustom(array $page, array $content): string
{
    $title = htmlspecialchars($page['title'] ?? 'Página', ENT_QUOTES, 'UTF-8');
    $text = htmlspecialchars($content['text'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<div class="preview-page preview-custom page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="page-content">
            <h2 class="preview-title-md">' . $title . '</h2>
            ' . ($text ? '<p>' . nl2br($text) . '</p>' : '<p style="color:#888;">Contenido vacío</p>') . '
        </div>
    </div>';
}
