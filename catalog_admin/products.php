<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
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

$productRepo = new ProductRepository();
$categoryRepo = new CategoryRepository();
$products = $productRepo->allByCatalog($catalogId);
$categories = $categoryRepo->allByCatalog($catalogId);
$catMap = [];
foreach ($categories as $c) {
    $catMap[(int)$c['id']] = $c['name'];
}
$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');
$currencySym = $catalog['currency'] === 'USD' ? '$' : 'C$';

if (isset($_GET['move'], $_GET['move_id'])) {
    $moveId = (int)$_GET['move_id'];
    $moveProd = $productRepo->findById($moveId);
    if ($moveProd && (int)$moveProd['catalog_id'] === $catalogId) {
        $dir = $_GET['move'] === 'up' ? -1 : 1;
        $currentOrder = (int)$moveProd['sort_order'];
        $newOrder = $currentOrder + $dir;
        $all = $productRepo->allByCatalog($catalogId);
        foreach ($all as $p) {
            if ((int)$p['sort_order'] === $newOrder && (int)$p['id'] !== $moveId) {
                $productRepo->update((int)$p['id'], ['sort_order' => $currentOrder] + $p);
                break;
            }
        }
        $productRepo->update($moveId, ['sort_order' => $newOrder] + $moveProd);
    }
    header('Location: products.php?catalog_id=' . $catalogId);
    exit;
}
$products = $productRepo->allByCatalog($catalogId);
$totalCount = count($products);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - <?= $catalogName ?></title>
<?php $pageTitle = 'Productos - ' . $catalogName; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
</head>
<body class="bg-background text-on-background min-h-screen">
<?php $navbarBackUrl = 'index.php'; require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
<div class="max-w-7xl mx-auto">
<header class="mb-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
<div>
<h1 class="font-display-lg-mobile text-display-lg-mobile text-on-surface">Productos</h1>
</div>
<div class="flex items-center gap-xs">
<a href="product_create.php?catalog_id=<?= $catalogId ?>" class="primary-gradient text-white px-sm py-xs rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow no-underline">+ Nuevo producto</a>
<div class="glass-panel px-sm py-xs rounded-xl flex items-center gap-xs">
<span class="material-symbols-outlined text-[18px] text-primary">inventory_2</span>
<div class="flex items-center gap-1">
<span class="text-label-caps font-label-caps text-on-surface-variant">TOTAL</span>
<span class="text-title-sm font-title-sm text-on-surface"><?= $totalCount ?></span>
</div>
</div>
</div>
</header>

<?php if (empty($products)): ?>
<div class="glass-panel rounded-3xl p-xl text-center">
<span class="material-symbols-outlined text-6xl text-on-surface-variant/30 mb-md">package</span>
<p class="font-title-sm text-title-sm text-on-surface mb-xs">No hay productos todavía</p>
<p class="font-body-sm text-body-sm text-on-surface-variant/70 mb-md">Agrega productos a este catálogo para empezar.</p>
<a href="product_create.php?catalog_id=<?= $catalogId ?>" class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm inline-flex active:scale-95 transition-transform primary-glow no-underline">+ Crear producto</a>
</div>
<?php else: ?>
<div class="glass-panel rounded-3xl overflow-hidden mb-xl">
<div class="p-md border-b border-outline-variant/10 flex flex-wrap gap-md justify-between items-center bg-surface-container-highest/20">
<h2 class="font-title-sm text-title-sm text-primary flex items-center gap-xs"><span class="material-symbols-outlined">list_alt</span>Inventario de Productos</h2>
<div class="flex gap-xs items-center">
<span class="material-symbols-outlined text-on-surface-variant/50">search</span>
<input id="tableSearch" class="bg-surface-variant/20 border border-outline-variant/30 rounded-full px-md py-1 text-body-sm text-on-surface placeholder-on-surface-variant/50 focus:outline-none focus:border-primary/50 w-48" placeholder="Buscar..." type="text" oninput="filterTable(this.value)">
</div>
</div>
<div class="overflow-x-auto">
<table class="w-full border-collapse">
<thead>
<tr class="text-left bg-surface-container-low/50">
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Ord.</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Nombre</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">SKU</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Categoría</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Precio</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Stock</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Estado</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-right">Acciones</th>
</tr>
</thead>
<tbody class="divide-y divide-outline-variant/5">
<?php foreach ($products as $i => $p): ?>
<tr class="hover:bg-surface-variant/10 transition-colors group">
<td class="p-md text-body-sm font-medium opacity-50"><?= $i + 1 ?></td>
<td class="p-md">
<div class="flex items-center gap-3">
<?php if (!empty($p['main_image'])): ?>
<img src="<?= htmlspecialchars(imageUrl($p['main_image']), ENT_QUOTES, 'UTF-8') ?>" class="w-14 h-14 rounded-lg object-contain border border-outline-variant/30 bg-surface-container-high">
<?php else: ?>
<div class="w-14 h-14 rounded-lg bg-surface-container-high overflow-hidden flex items-center justify-center"><span class="material-symbols-outlined text-on-surface-variant/40 text-[20px]">package</span></div>
<?php endif; ?>
<span class="font-title-sm text-on-surface font-semibold group-hover:text-primary transition-colors"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></span>
</div>
</td>
<td class="p-md text-body-sm font-mono text-outline"><?= htmlspecialchars($p['sku'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
<td class="p-md"><span class="bg-surface-container px-3 py-1 rounded-full text-[11px] font-bold text-secondary uppercase tracking-wider"><?= htmlspecialchars($catMap[(int)$p['category_id']] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8') ?></span></td>
<td class="p-md font-bold text-[#ffd966]"><?= $p['price'] !== null ? $currencySym . number_format((float)$p['price'], 2) : '-' ?></td>
<td class="p-md text-body-sm"><?= $p['stock'] !== null ? (int)$p['stock'] . ' ud.' : '-' ?></td>
<td class="p-md">
<?php $st = $p['status']; ?>
<span class="flex items-center gap-2 text-[12px] font-bold <?= $st === 'active' ? 'text-[#b7c4ff]' : 'text-outline' ?>">
<span class="w-2 h-2 rounded-full <?= $st === 'active' ? 'bg-[#b7c4ff] animate-pulse' : 'bg-outline' ?>"></span>
<?= $st === 'active' ? 'Publicado' : 'Borrador' ?>
</span>
</td>
<td class="p-md">
<div class="flex justify-end gap-1">
<a href="product_edit.php?id=<?= $p['id'] ?>" class="p-2 hover:bg-primary/10 rounded-full text-on-surface-variant hover:text-primary transition-all" title="Editar"><span class="material-symbols-outlined">edit</span></a>
<a href="product_delete.php?id=<?= $p['id'] ?>" class="p-2 hover:bg-error/10 rounded-full text-on-surface-variant hover:text-error transition-all" title="Eliminar" onclick="return confirm('¿Eliminar este producto?')"><span class="material-symbols-outlined">delete</span></a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<div class="flex flex-col md:flex-row justify-between items-center gap-sm glass-panel p-sm rounded-2xl">
<p class="font-body-sm text-body-sm text-on-surface-variant/70">Mostrando <?= $totalCount ?> de <?= $totalCount ?> productos</p>
<div class="flex items-center gap-1">
<button class="flex items-center gap-1 px-sm py-1 rounded-full font-label-caps text-label-caps text-on-surface-variant/60 hover:text-tertiary hover:bg-surface-variant/20 transition-all disabled opacity-30 pointer-events-none"><span class="material-symbols-outlined text-[16px]">chevron_left</span> Anterior</button>
<div class="flex gap-1"><button class="w-8 h-8 rounded-full bg-primary/20 text-primary font-bold text-label-caps text-[11px]">1</button></div>
<button class="flex items-center gap-1 px-sm py-1 rounded-full font-label-caps text-label-caps text-on-surface-variant/60 hover:text-tertiary hover:bg-surface-variant/20 transition-all disabled opacity-30 pointer-events-none">Siguiente <span class="material-symbols-outlined text-[16px]">chevron_right</span></button>
</div>
</div>
<?php endif; ?>
</div>
</section>
</main>
<script>
document.querySelectorAll('tbody tr').forEach(row => {
row.addEventListener('mouseenter', () => { row.style.transform = 'translateY(-2px)'; row.style.transition = 'transform 0.2s ease'; });
row.addEventListener('mouseleave', () => { row.style.transform = 'translateY(0)'; });
});
function filterTable(val) {
const q = val.toLowerCase();
document.querySelectorAll('tbody tr').forEach(row => {
row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
});
}
</script>
</body>
</html>
