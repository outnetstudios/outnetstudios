<?php
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
require_once __DIR__ . '/../includes/upload_helper.php';

const PRODUCTS_PER_CATEGORY_PAGE = 8;

/**
 * Build expanded page list where category pages auto-flow products across pages.
 * Each category page renders up to PRODUCTS_PER_CATEGORY_PAGE products;
 * overflow creates additional virtual pages.
 */
function buildExpandedPages(array $pages, array $products, array $categories): array
{
    $expanded = [];
    $productsPerPage = PRODUCTS_PER_CATEGORY_PAGE;

    // Active products only
    $active = array_values(array_filter($products, fn($p) => $p['status'] === 'active'));

    // Build pool per category
    $pool = [];
    foreach ($active as $p) {
        $cid = (int)$p['category_id'];
        if (!isset($pool[$cid])) $pool[$cid] = [];
        $pool[$cid][] = $p;
    }

    foreach ($pages as $page) {
        if ($page['page_type'] !== 'category') {
            $page['_assigned_products'] = null;
            $expanded[] = $page;
            continue;
        }

        $content = $page['content_json'] ? json_decode($page['content_json'], true) : [];
        $catId = (int)($content['category_id'] ?? 0);

        // Collect products for this page — either from a specific category or all
        $candidates = [];
        if ($catId > 0) {
            if (isset($pool[$catId]) && !empty($pool[$catId])) {
                $candidates = $pool[$catId];
                $pool[$catId] = []; // consumed
            }
        } else {
            // All categories, ordered by category sort_order
            $sortedCats = $categories;
            usort($sortedCats, fn($a, $b) => (int)$a['sort_order'] - (int)$b['sort_order']);
            foreach ($sortedCats as $c) {
                $cid = (int)$c['id'];
                if (isset($pool[$cid]) && !empty($pool[$cid])) {
                    $candidates = array_merge($candidates, $pool[$cid]);
                    $pool[$cid] = [];
                }
            }
            // Also include uncategorized
            if (isset($pool[0]) && !empty($pool[0])) {
                $candidates = array_merge($candidates, $pool[0]);
                $pool[0] = [];
            }
        }

        if (empty($candidates)) {
            $page['_assigned_products'] = [];
            $expanded[] = $page;
            continue;
        }

        $chunks = array_chunk($candidates, $productsPerPage);
        foreach ($chunks as $i => $chunk) {
            $p = $page;
            $p['_assigned_products'] = $chunk;
            if ($i > 0) {
                $base = $page['title'] ?? 'Categoría';
                $p['title'] = $base . ' (' . ($i + 1) . ')';
            }
            $expanded[] = $p;
        }
    }

    return $expanded;
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

function renderPageContent(array $page, array $categories): string
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
            $html = renderIndex($page);
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

function renderIndex(array $page): string
{
    $title = htmlspecialchars($page['title'] ?? 'Índice', ENT_QUOTES, 'UTF-8');
    $pageRepo = new CatalogPageRepository();
    $allPages = $pageRepo->allByCatalog((int)$page['catalog_id']);
    $items = '';
    foreach ($allPages as $i => $p) {
        if ($p['page_type'] === 'index') continue;
        $label = $GLOBALS['pageTypeLabels'][$p['page_type']] ?? $p['page_type'];
        $pageTitle = $p['title'] ?: $label;
        $items .= '<li><span class="index-num">' . ($i + 1) . '.</span> ' . htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    return '<div class="preview-page preview-index page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="page-content">
            <h2 class="preview-title-md">' . $title . '</h2>
            <ol class="preview-index-list">' . $items . '</ol>
        </div>
    </div>';
}

function renderCategoryPage(array $page, array $content, array $categories): string
{
    $title = htmlspecialchars($page['title'] ?? 'Categoría', ENT_QUOTES, 'UTF-8');
    $assigned = $page['_assigned_products'] ?? [];
    $catId = (int)($content['category_id'] ?? 0);

    // Determine which category header to show
    $catMap = [];
    foreach ($categories as $c) {
        $catMap[(int)$c['id']] = $c;
    }

    $showCatId = $catId;
    if ($showCatId <= 0 && !empty($assigned)) {
        $showCatId = (int)$assigned[0]['category_id'];
    }

    $grid = '';

    // Category header
    if ($showCatId > 0 && isset($catMap[$showCatId])) {
        $cat = $catMap[$showCatId];
        $catName = htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8');
        $catDesc = !empty($cat['description']) ? htmlspecialchars($cat['description'], ENT_QUOTES, 'UTF-8') : '';
        $catImage = !empty($cat['image']) ? $cat['image'] : null;

        $grid .= '<div class="preview-category-section">';
        $grid .= '<div class="preview-category-header">';
        if ($catImage) {
            $grid .= '<img src="' . htmlspecialchars(imageUrl($catImage), ENT_QUOTES, 'UTF-8') . '" class="preview-cat-thumb" alt="">';
        }
        $grid .= '<div><h3 class="preview-cat-title">' . $catName . '</h3>';
        if ($catDesc) $grid .= '<p class="preview-cat-desc">' . $catDesc . '</p>';
        $grid .= '</div></div>';

        $grid .= '<div class="preview-products-grid-4">';
        foreach ($assigned as $prod) {
            $pName = htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8');
            $pPrice = $prod['price'] !== null ? number_format((float)$prod['price'], 2) : '';
            $pSku = htmlspecialchars($prod['sku'] ?? '', ENT_QUOTES, 'UTF-8');
            $pDesc = htmlspecialchars(mb_substr($prod['description'] ?? '', 0, 80), ENT_QUOTES, 'UTF-8');
            $imgSrc = !empty($prod['main_image']) ? imageUrl($prod['main_image']) : '';
            $imgTag = $imgSrc ? '<img src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" class="preview-product-img" alt="' . $pName . '">' : '<div class="preview-product-img preview-product-img-placeholder"><span class="material-symbols-outlined">inventory_2</span></div>';

            $grid .= '<div class="preview-product-card">
                ' . $imgTag . '
                <div class="preview-product-info">
                    <h4>' . $pName . '</h4>
                    ' . ($pSku ? '<span class="preview-sku">' . $pSku . '</span>' : '') . '
                    <p>' . $pDesc . '</p>
                    ' . ($pPrice ? '<div class="preview-price">$' . $pPrice . '</div>' : '') . '
                </div>
            </div>';
        }
        $grid .= '</div>';
        $grid .= '</div>';
    }

    if (empty($grid)) {
        $grid = '<p style="text-align:center; color:#888; padding:2rem;">No hay productos activos.</p>';
    }

    return '<div class="preview-page preview-category page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="page-content">
            <h2 class="preview-title-md">' . $title . '</h2>
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
    $imgTag = $imgSrc ? '<img src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" class="preview-prod-img" alt="' . $pName . '">' : '<div class="preview-prod-img preview-prod-img-placeholder"><span class="material-symbols-outlined">inventory_2</span></div>';
    return '<div class="preview-page preview-product page-with-bg"' . pageBgStyle($page) . '>' . pageBgImg($page) . '
        <div class="page-content">
            <div class="preview-product-detail">
                ' . $imgTag . '
                <div class="preview-prod-info">
                    <h2>' . $pName . '</h2>
                    ' . ($pSku ? '<span class="preview-sku">SKU: ' . $pSku . '</span>' : '') . '
                    ' . ($pPrice ? '<div class="preview-price-lg">$' . $pPrice . '</div>' : '') . '
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
