<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogPageRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';

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
$categories = $categoryRepo->allByCatalog($catalogId);
$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');

if (isset($_GET['move'], $_GET['move_id'])) {
    $moveId = (int)$_GET['move_id'];
    $movePage = $pageRepo->findById($moveId);
    if ($movePage && (int)$movePage['catalog_id'] === $catalogId) {
        $dir = $_GET['move'] === 'up' ? -1 : 1;
        $currentOrder = (int)$movePage['sort_order'];
        $newOrder = $currentOrder + $dir;
        $all = $pageRepo->allByCatalog($catalogId);
        foreach ($all as $p) {
            if ((int)$p['sort_order'] === $newOrder && (int)$p['id'] !== $moveId) {
                $pageRepo->update((int)$p['id'], ['sort_order' => $currentOrder] + $p);
                break;
            }
        }
        $pageRepo->update($moveId, ['sort_order' => $newOrder] + $movePage);
    }
    header('Location: pages.php?catalog_id=' . $catalogId);
    exit;
}

$pages = $pageRepo->allByCatalog($catalogId);

$pageTypeLabels = [
    'cover' => 'Portada',
    'index' => 'Índice',
    'category' => 'Categoría',
    'banner' => 'Banner',
    'product' => 'Producto',
    'back_cover' => 'Contraportada',
    'custom' => 'Personalizada',
];
?>
<?php $pageTitle = 'Páginas - ' . $catalogName; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<style>
.page-row { display: flex; align-items: center; gap: 1rem; padding: 0.75rem 1rem; border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; margin-bottom: 0.5rem; background: rgba(255,255,255,0.03); transition: all 0.2s; }
.page-row:hover { background: rgba(255,255,255,0.06); transform: translateY(-1px); }
</style>
<?php $navbarBackUrl = 'index.php'; require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
<div class="max-w-7xl mx-auto">
<header class="mb-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
<div>
<h1 class="font-display-lg-mobile text-display-lg-mobile text-on-surface">Páginas</h1>
</div>
<div class="flex items-center gap-xs">
<a href="page_create.php?catalog_id=<?= $catalogId ?>" class="primary-gradient text-white px-sm py-xs rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow no-underline">+ Nueva página</a>
<div class="glass-panel px-sm py-xs rounded-xl flex items-center gap-xs">
<span class="material-symbols-outlined text-[18px] text-primary">description</span>
<div class="flex items-center gap-1">
<span class="text-label-caps font-label-caps text-on-surface-variant">TOTAL</span>
<span class="text-title-sm font-title-sm text-on-surface"><?= count($pages) ?></span>
</div>
</div>
</div>
</header>

<?php if (empty($pages)): ?>
<div class="glass-panel rounded-3xl p-xl text-center">
<span class="material-symbols-outlined text-6xl text-on-surface-variant/30 mb-md">book</span>
<p class="font-title-sm text-title-sm text-on-surface mb-xs">Aún no hay páginas</p>
<p class="font-body-sm text-body-sm text-on-surface-variant/70 mb-md">Agrega portada, categorías, índice y contraportada para armar el catálogo.</p>
<a href="page_create.php?catalog_id=<?= $catalogId ?>" class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm inline-flex active:scale-95 transition-transform primary-glow no-underline">+ Crear primera página</a>
</div>
<?php else: ?>
<div class="glass-panel rounded-3xl overflow-hidden mb-xl">
<div class="p-md border-b border-outline-variant/10 flex flex-wrap gap-md justify-between items-center bg-surface-container-highest/20">
<h2 class="font-title-sm text-title-sm text-primary flex items-center gap-xs"><span class="material-symbols-outlined">list_alt</span>Orden de páginas</h2>
<div class="flex gap-xs">
<a href="preview.php?catalog_id=<?= $catalogId ?>" class="px-md py-xs rounded-full border border-outline-variant text-label-caps font-label-caps hover:bg-surface-variant/30 transition-all flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">visibility</span> VISTA PREVIA</a>
</div>
</div>
<div class="p-md space-y-2">
<?php foreach ($pages as $i => $page):
    $type = $page['page_type'];
    $label = $pageTypeLabels[$type] ?? $type;
    $title = $page['title'] ?: $label;
    $typeIcons = ['cover'=>'book', 'index'=>'format_list_bulleted', 'category'=>'folder', 'banner'=>'campaign', 'product'=>'inventory_2', 'back_cover'=>'book_5', 'custom'=>'description'];
    $icon = $typeIcons[$type] ?? 'description';
?>
<div class="page-row">
<div class="w-8 h-8 rounded-full bg-surface-container-high flex items-center justify-center flex-shrink-0">
<span class="material-symbols-outlined text-primary text-[18px]"><?= $icon ?></span>
</div>
<div class="font-title-sm text-title-sm text-on-surface-variant/40 font-bold min-w-[1.8rem]"><?= $i + 1 ?></div>
<div class="flex-1 min-w-0">
<div class="font-title-sm text-title-sm text-on-surface font-semibold truncate"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
<div class="font-label-caps text-label-caps text-on-surface-variant/60"><?= $label ?></div>
</div>
<div class="flex flex-col gap-0.5 flex-shrink-0">
<?php if ($i > 0): ?>
<a href="?catalog_id=<?= $catalogId ?>&move=up&move_id=<?= $page['id'] ?>" class="text-primary hover:text-primary-fixed-dim transition-colors" title="Subir"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></a>
<?php else: ?>
<span class="text-on-surface-variant/20"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></span>
<?php endif; ?>
<?php if ($i < count($pages) - 1): ?>
<a href="?catalog_id=<?= $catalogId ?>&move=down&move_id=<?= $page['id'] ?>" class="text-primary hover:text-primary-fixed-dim transition-colors" title="Bajar"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></a>
<?php else: ?>
<span class="text-on-surface-variant/20"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></span>
<?php endif; ?>
</div>
<div class="flex gap-1 flex-shrink-0">
<a href="page_edit.php?id=<?= $page['id'] ?>" class="p-2 hover:bg-primary/10 rounded-full text-on-surface-variant hover:text-primary transition-all" title="Editar"><span class="material-symbols-outlined">edit</span></a>
<a href="page_delete.php?id=<?= $page['id'] ?>" class="p-2 hover:bg-error/10 rounded-full text-on-surface-variant hover:text-error transition-all" title="Eliminar" onclick="return confirm('¿Eliminar esta página?')"><span class="material-symbols-outlined">delete</span></a>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="glass-panel p-md rounded-2xl mb-xl">
<p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1 flex-wrap">
<span class="material-symbols-outlined text-primary text-[18px]">stack</span>
<strong>Vista previa del orden:</strong>
<?php foreach ($pages as $i => $page):
    $t = $page['title'] ?: ($pageTypeLabels[$page['page_type']] ?? $page['page_type']);
    echo '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-surface-container-high/50 text-body-sm font-body-sm text-on-surface-variant mx-1">' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</span>';
    if ($i < count($pages) - 1) echo '<span class="material-symbols-outlined text-[14px] text-on-surface-variant/30">arrow_forward</span>';
endforeach; ?>
</p>
</div>

<?php endif; ?>
</div>
</section>
</main>
</body>
</html>
