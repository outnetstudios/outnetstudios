<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
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

$categoryRepo = new CategoryRepository();
$categories = $categoryRepo->allByCatalog($catalogId);
$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');

if (isset($_GET['move'], $_GET['move_id'])) {
    $moveId = (int)$_GET['move_id'];
    $moveCat = $categoryRepo->findById($moveId);
    if ($moveCat && (int)$moveCat['catalog_id'] === $catalogId) {
        $dir = $_GET['move'] === 'up' ? -1 : 1;
        $currentOrder = (int)$moveCat['sort_order'];
        $newOrder = $currentOrder + $dir;
        $catList = $categories;
        $other = array_filter($catList, fn($c) => (int)$c['sort_order'] === $newOrder && (int)$c['id'] !== $moveId);
        if ($other) {
            $other = reset($other);
            $categoryRepo->update((int)$other['id'], ['sort_order' => $currentOrder] + $other);
            $categoryRepo->update($moveId, ['sort_order' => $newOrder] + $moveCat);
        } else {
            $categoryRepo->update($moveId, ['sort_order' => $newOrder] + $moveCat);
        }
    }
    header('Location: categories.php?catalog_id=' . $catalogId);
    exit;
}
$categories = $categoryRepo->allByCatalog($catalogId);
$totalCount = count($categories);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías - <?= $catalogName ?></title>
<?php $pageTitle = 'Categorías - ' . $catalogName; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
</head>
<body class="bg-background text-on-background min-h-screen">
<?php $navbarBackUrl = 'index.php'; require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
<div class="max-w-7xl mx-auto">
<header class="mb-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
<div>
<h1 class="font-display-lg-mobile text-display-lg-mobile text-on-surface">Categorías</h1>
</div>
<div class="flex items-center gap-xs">
<a href="category_create.php?catalog_id=<?= $catalogId ?>" class="primary-gradient text-white px-sm py-xs rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow no-underline">+ Nueva categoría</a>
<div class="glass-panel px-sm py-xs rounded-xl flex items-center gap-xs">
<span class="material-symbols-outlined text-[18px] text-primary">folder</span>
<div class="flex items-center gap-1">
<span class="text-label-caps font-label-caps text-on-surface-variant">TOTAL</span>
<span class="text-title-sm font-title-sm text-on-surface"><?= $totalCount ?></span>
</div>
</div>
</div>
</header>

<?php if (empty($categories)): ?>
<div class="glass-panel rounded-3xl p-xl text-center">
<span class="material-symbols-outlined text-6xl text-on-surface-variant/30 mb-md">folder</span>
<p class="font-title-sm text-title-sm text-on-surface mb-xs">No hay categorías todavía</p>
<p class="font-body-sm text-body-sm text-on-surface-variant/70 mb-md">Crea categorías para organizar tus productos.</p>
<a href="category_create.php?catalog_id=<?= $catalogId ?>" class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm inline-flex active:scale-95 transition-transform primary-glow no-underline">+ Crear categoría</a>
</div>
<?php else: ?>
<div class="glass-panel rounded-3xl overflow-hidden mb-xl">
<div class="p-md border-b border-outline-variant/10 flex flex-wrap gap-md justify-between items-center bg-surface-container-highest/20">
<h2 class="font-title-sm text-title-sm text-primary flex items-center gap-xs"><span class="material-symbols-outlined">list_alt</span>Inventario de Categorías</h2>
<div class="flex gap-xs items-center">
<span class="material-symbols-outlined text-on-surface-variant/50">search</span>
<input id="tableSearch" class="bg-surface-variant/20 border border-outline-variant/30 rounded-full px-md py-1 text-body-sm text-on-surface placeholder-on-surface-variant/50 focus:outline-none focus:border-primary/50 w-48" placeholder="Buscar..." type="text" oninput="filterTable(this.value)">
</div>
</div>
<div class="overflow-x-auto">
<table class="w-full border-collapse">
<thead>
<tr class="text-left bg-surface-container-low/50">
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Orden</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Nombre</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Descripción</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Estado</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-right">Acciones</th>
</tr>
</thead>
<tbody class="divide-y divide-outline-variant/5">
<?php foreach ($categories as $i => $cat): ?>
<tr class="hover:bg-surface-variant/10 transition-colors group">
<td class="p-md text-body-sm font-medium opacity-50">
<div class="flex items-center gap-1">
<?php if ($i > 0): ?>
<a href="?catalog_id=<?= $catalogId ?>&move=up&move_id=<?= $cat['id'] ?>" class="text-primary hover:text-primary-fixed-dim transition-colors"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></a>
<?php else: ?>
<span class="text-on-surface-variant/20"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></span>
<?php endif; ?>
<?php if ($i < $totalCount - 1): ?>
<a href="?catalog_id=<?= $catalogId ?>&move=down&move_id=<?= $cat['id'] ?>" class="text-primary hover:text-primary-fixed-dim transition-colors"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></a>
<?php else: ?>
<span class="text-on-surface-variant/20"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></span>
<?php endif; ?>
</div>
</td>
                                        <td class="p-md">
                                            <div class="flex items-center gap-3">
                                                <?php if (!empty($cat['image'])): ?>
                                                <img src="<?= htmlspecialchars(imageUrl($cat['image']), ENT_QUOTES, 'UTF-8') ?>" class="w-10 h-10 rounded-lg object-cover border border-outline-variant/30">
                                                <?php else: ?>
                                                <div class="w-10 h-10 rounded-lg bg-surface-container-high overflow-hidden flex items-center justify-center"><span class="material-symbols-outlined text-on-surface-variant/40 text-[20px]">folder</span></div>
                                                <?php endif; ?>
                                                <span class="font-title-sm text-on-surface font-semibold group-hover:text-primary transition-colors"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </td>
                                        <td class="p-md font-body-sm text-body-sm text-on-surface-variant"><?= htmlspecialchars($cat['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
<td class="p-md">
<?php $st = $cat['status']; ?>
<span class="px-md py-1 rounded-full text-label-caps font-label-caps <?= $st === 'active' ? 'bg-primary/10 text-primary border border-primary/20' : 'bg-on-surface-variant/10 text-on-surface-variant border border-on-surface-variant/20' ?>"><?= $st === 'active' ? 'Activo' : 'Inactivo' ?></span>
</td>
<td class="p-md">
<div class="flex justify-end gap-1">
<a href="category_edit.php?id=<?= $cat['id'] ?>" class="p-2 hover:bg-primary/10 rounded-full text-on-surface-variant hover:text-primary transition-all" title="Editar"><span class="material-symbols-outlined">edit</span></a>
<a href="category_delete.php?id=<?= $cat['id'] ?>" class="p-2 hover:bg-error/10 rounded-full text-on-surface-variant hover:text-error transition-all" title="Eliminar" onclick="return confirm('¿Eliminar esta categoría? Los productos se quedarán sin categoría.')"><span class="material-symbols-outlined">delete</span></a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<div class="flex flex-col md:flex-row justify-between items-center gap-sm glass-panel px-sm py-xs rounded-2xl">
<p class="font-body-sm text-body-sm text-on-surface-variant/70">Mostrando <?= $totalCount ?> de <?= $totalCount ?> categorías</p>
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
