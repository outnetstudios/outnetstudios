<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/catalog_permissions.php';
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
if (!$catalog || !catalogCanEdit($catalogId, $userId, PERM_EDIT_PRODUCTS)) {
    header('Location: index.php');
    exit;
}

$productRepo = new ProductRepository();
$categoryRepo = new CategoryRepository();
$products = $productRepo->allByCatalog($catalogId);
$categories = $categoryRepo->allByCatalog($catalogId);

// Handle AJAX reorder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reorder') {
    $catId = (int)($_POST['category_id'] ?? -1);
    $order = $_POST['order'] ?? '';
    $ids = array_filter(array_map('intval', explode(',', $order)));
    if ($catId >= 0 && !empty($ids)) {
        foreach ($ids as $i => $pid) {
            $prod = $productRepo->findById($pid);
            if ($prod) {
                $prod['sort_order'] = $i;
                $productRepo->update($pid, $prod);
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

$catalogName = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');
$currencySym = $catalog['currency'] === 'USD' ? '$' : 'C$';

// Sort products by category order then sort_order (matches preview order)
$catOrderMap = [];
foreach ($categories as $i => $c) $catOrderMap[(int)$c['id']] = $i;
usort($products, function ($a, $b) use ($catOrderMap) {
    $ao = $catOrderMap[(int)$a['category_id']] ?? -1;
    $bo = $catOrderMap[(int)$b['category_id']] ?? -1;
    if ($ao !== $bo) return $ao - $bo;
    return (int)$a['sort_order'] - (int)$b['sort_order'];
});

// Group products by category
$sections = [];
$uncategorized = [];
$catProducts = [];
foreach ($products as $p) {
    $cid = (int)$p['category_id'];
    if ($cid <= 0) { $uncategorized[] = $p; continue; }
    if (!isset($catProducts[$cid])) $catProducts[$cid] = [];
    $catProducts[$cid][] = $p;
}
if (!empty($uncategorized)) $sections[] = ['id' => 0, 'name' => 'Sin categoría', 'products' => $uncategorized];
foreach ($categories as $cat) {
    $cid = (int)$cat['id'];
    if (!empty($catProducts[$cid])) $sections[] = ['id' => $cid, 'name' => $cat['name'], 'products' => $catProducts[$cid]];
}

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
<div class="flex items-center gap-2 flex-wrap">
<select id="categoryFilter" onchange="filterByCategory(this.value)" class="bg-surface-container-high border border-outline-variant/30 rounded-full px-md py-1 text-body-sm text-on-surface appearance-none focus:outline-none focus:border-primary/50" style="padding-right:2rem;background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23a0a8c8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 0.75rem center;">
<option value="">Todas las categorías</option>
<?php foreach ($categories as $cat): ?>
<option value="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
</select>
<div class="flex gap-xs items-center">
<span class="material-symbols-outlined text-on-surface-variant/50">search</span>
<input id="tableSearch" class="bg-surface-variant/20 border border-outline-variant/30 rounded-full px-md py-1 text-body-sm text-on-surface placeholder-on-surface-variant/50 focus:outline-none focus:border-primary/50 w-48" placeholder="Buscar..." type="text" oninput="filterTable(this.value)">
</div>
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
<?php $globalIdx = 0; foreach ($sections as $si => $section): 
$secName = htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8');
$secProdCount = count($section['products']);
$secId = (int)$section['id'];
?>
<tr class="section-header" data-section-id="<?= $secId ?>">
<td colspan="8" class="px-md py-2 bg-surface-container-low/40">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-[16px] text-primary"><?= $secId === 0 ? 'help' : 'folder' ?></span>
<span class="font-title-sm text-title-sm text-on-surface font-semibold"><?= $secName ?></span>
<span class="text-label-caps text-on-surface-variant/50">(<?= $secProdCount ?>)</span>
</div>
</td>
</tr>
<?php foreach ($section['products'] as $i => $p): $globalIdx++; ?>
<tr class="hover:bg-surface-variant/10 transition-colors group draggable-row" draggable="true" data-product-id="<?= (int)$p['id'] ?>" data-section-id="<?= $secId ?>" data-category="<?= $secName ?>">
<td class="p-md text-body-sm font-medium text-on-surface-variant/40 drag-handle cursor-grab active:cursor-grabbing" style="white-space:nowrap;">
<span class="material-symbols-outlined text-[16px] align-middle">drag_indicator</span>
<span class="align-middle opacity-50"><?= $i + 1 ?></span>
</td>
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
<td class="p-md"><span class="bg-surface-container px-3 py-1 rounded-full text-[11px] font-bold text-secondary uppercase tracking-wider"><?= $secName ?></span></td>
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
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Mobile cards -->
<div class="mobile-cards">
<?php foreach ($sections as $section):
$secName = htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8');
$secId = (int)$section['id'];
?>
<div class="mobile-section-group" data-section-id="<?= $secId ?>">
<p class="mobile-section-title"><span class="material-symbols-outlined"><?= $secId === 0 ? 'help' : 'folder' ?></span> <?= $secName ?> <span class="mobile-section-count">(<?= count($section['products']) ?>)</span></p>
<?php foreach ($section['products'] as $i => $p): ?>
<div class="mobile-card" data-product-id="<?= (int)$p['id'] ?>" data-section-id="<?= $secId ?>" data-category="<?= $secName ?>" draggable="true">
<div class="mobile-card-header">
<div class="mobile-card-drag"><span class="material-symbols-outlined">drag_indicator</span></div>
<?php if (!empty($p['main_image'])): ?>
<img src="<?= htmlspecialchars(imageUrl($p['main_image']), ENT_QUOTES, 'UTF-8') ?>">
<?php else: ?>
<div class="mobile-card-icon"><span class="material-symbols-outlined">package</span></div>
<?php endif; ?>
<div class="mobile-card-header-text">
<span class="mobile-card-title"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></span>
<span class="mobile-card-sku">SKU: <?= htmlspecialchars($p['sku'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
</div>
</div>
<div class="mobile-card-body">
<div class="mobile-card-row">
<span class="mobile-card-label">Estado</span>
<span class="mobile-card-status <?= $p['status'] === 'active' ? 'active' : '' ?>">
<span class="status-dot <?= $p['status'] === 'active' ? 'active' : '' ?>"></span>
<?= $p['status'] === 'active' ? 'Publicado' : 'Borrador' ?>
</span>
</div>
</div>
<div class="mobile-card-actions">
<a href="product_edit.php?id=<?= $p['id'] ?>" class="mobile-card-action-btn edit"><span class="material-symbols-outlined">edit</span> Editar</a>
<a href="product_delete.php?id=<?= $p['id'] ?>" class="mobile-card-action-btn delete" onclick="return confirm('¿Eliminar este producto?')"><span class="material-symbols-outlined">delete</span> Eliminar</a>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
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
<style>
@media (max-width: 768px) {
.overflow-x-auto { display: none; }
.mobile-cards { display: flex; flex-direction: column; gap: 0.5rem; padding: 0.75rem; }
.mobile-section-group { display: flex; flex-direction: column; gap: 0.5rem; }
.mobile-section-title { display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; font-weight: 700; color: rgba(255,255,255,0.5); padding: 0.5rem 0.25rem 0; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
.mobile-section-title span { font-size: 1rem; }
.mobile-section-count { font-weight: 400; color: rgba(255,255,255,0.3); }
.mobile-card { background: var(--surface-container-high, #1e1e2e); border-radius: 12px; padding: 0.75rem 1rem; border: 1px solid rgba(255,255,255,0.06); touch-action: none; user-select: none; }
.mobile-card.sorting { opacity: 0.5; transform: scale(0.97); }
.mobile-card.chosen { border-color: rgba(100,180,255,0.4); box-shadow: 0 4px 16px rgba(100,180,255,0.15); }
.mobile-card-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.mobile-card-drag { display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.2); cursor: grab; touch-action: none; }
.mobile-card-drag span { font-size: 1.2rem; }
.mobile-card-header img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(255,255,255,0.08); flex-shrink: 0; }
.mobile-card-icon { width: 44px; height: 44px; border-radius: 8px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mobile-card-icon span { font-size: 20px; color: rgba(255,255,255,0.3); }
.mobile-card-header-text { flex: 1; min-width: 0; }
.mobile-card-title { font-size: 0.9rem; font-weight: 700; color: var(--text-on-surface, #e0e0f0); display: block; }
.mobile-card-sku { font-size: 0.7rem; color: rgba(255,255,255,0.35); font-family: monospace; }
.mobile-card-body { margin-bottom: 0.5rem; }
.mobile-card-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; }
.mobile-card-label { color: rgba(255,255,255,0.35); font-weight: 500; }
.mobile-card-status { display: flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.3); }
.mobile-card-status.active { color: #b7c4ff; }
.status-dot { width: 0.45rem; height: 0.45rem; border-radius: 50%; background: rgba(255,255,255,0.2); display: inline-block; }
.status-dot.active { background: #b7c4ff; }
.mobile-card-actions { display: flex; gap: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.06); }
.mobile-card-action-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.4rem; border-radius: 8px; font-size: 0.78rem; font-weight: 600; text-decoration: none; color: rgba(255,255,255,0.6); transition: all 0.15s; background: rgba(255,255,255,0.04); }
.mobile-card-action-btn span { font-size: 1rem; }
.mobile-card-action-btn.edit:hover { background: rgba(100,180,255,0.15); color: #64b4ff; }
.mobile-card-action-btn.delete:hover { background: rgba(255,80,80,0.15); color: #ff5050; }
}
@media (min-width: 769px) {
.mobile-cards { display: none; }
}
</style>
<script>
let activeCategory = '';
function filterByCategory(val) {
activeCategory = val;
applyFilters();
}
function filterTable(val) {
window._searchVal = val.toLowerCase();
applyFilters();
}
function applyFilters() {
const q = (window._searchVal || '').toLowerCase();
document.querySelectorAll('.draggable-row').forEach(row => {
const catMatch = !activeCategory || row.dataset.category === activeCategory;
const searchMatch = !q || row.textContent.toLowerCase().includes(q);
row.style.display = catMatch && searchMatch ? '' : 'none';
});
// Hide empty section headers
document.querySelectorAll('.section-header').forEach(header => {
const sid = header.dataset.sectionId;
const visible = [...document.querySelectorAll(`.draggable-row[data-section-id="${sid}"]`)].some(r => r.style.display !== 'none');
header.style.display = visible ? '' : 'none';
});
// Apply same filters to mobile cards
document.querySelectorAll('.mobile-card').forEach(card => {
const catMatch = !activeCategory || card.dataset.category === activeCategory;
const searchMatch = !q || card.textContent.toLowerCase().includes(q);
card.style.display = catMatch && searchMatch ? '' : 'none';
});
}

// Drag and drop
let dragSrcRow = null;
document.addEventListener('dragstart', function(e) {
const row = e.target.closest('.draggable-row');
if (!row) return;
dragSrcRow = row;
row.classList.add('opacity-40');
e.dataTransfer.effectAllowed = 'move';
});
document.addEventListener('dragend', function(e) {
const row = e.target.closest('.draggable-row');
if (row) row.classList.remove('opacity-40');
dragSrcRow = null;
document.querySelectorAll('.drop-target').forEach(r => r.classList.remove('drop-target'));
});
document.addEventListener('dragover', function(e) {
const row = e.target.closest('.draggable-row');
if (!row || row === dragSrcRow) return;
if (row.dataset.sectionId !== dragSrcRow.dataset.sectionId) {
e.dataTransfer.dropEffect = 'none';
return;
}
e.preventDefault();
e.dataTransfer.dropEffect = 'move';
document.querySelectorAll('.drop-target').forEach(r => r.classList.remove('drop-target'));
row.classList.add('drop-target');
});
document.addEventListener('dragleave', function(e) {
const row = e.target.closest('.draggable-row');
if (row) row.classList.remove('drop-target');
});
document.addEventListener('drop', function(e) {
e.preventDefault();
const row = e.target.closest('.draggable-row');
if (!row || row === dragSrcRow || row.dataset.sectionId !== dragSrcRow.dataset.sectionId) return;
row.classList.remove('drop-target');
const tbody = row.closest('tbody');
const rows = [...tbody.querySelectorAll(`.draggable-row[data-section-id="${row.dataset.sectionId}"]`)];
const fromIdx = rows.indexOf(dragSrcRow);
const toIdx = rows.indexOf(row);
if (fromIdx === toIdx) return;
// Move in DOM
if (fromIdx < toIdx) {
dragSrcRow.parentNode.insertBefore(dragSrcRow, row.nextSibling);
} else {
dragSrcRow.parentNode.insertBefore(dragSrcRow, row);
}
// Update order numbers
const updatedRows = [...tbody.querySelectorAll(`.draggable-row[data-section-id="${row.dataset.sectionId}"]`)];
updatedRows.forEach((r, idx) => {
const td = r.querySelector('td:first-child');
if (td) td.textContent = idx + 1;
});
// Send AJAX
const ids = updatedRows.map(r => r.dataset.productId).join(',');
const formData = new FormData();
formData.append('action', 'reorder');
formData.append('category_id', row.dataset.sectionId);
formData.append('order', ids);
fetch('products.php?catalog_id=<?= $catalogId ?>', {
method: 'POST',
body: formData
});
});

</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
// Mobile touch drag reorder via SortableJS
function initMobileSortable() {
if (window.innerWidth > 768) { document.querySelectorAll('.mobile-section-group').forEach(function(g) { if (g.sortable) g.sortable.destroy(); }); return; }
document.querySelectorAll('.mobile-section-group').forEach(function(group) {
if (group.sortable) group.sortable.destroy();
group.sortable = new Sortable(group, {
animation: 200,
handle: '.mobile-card-drag',
delay: 200,
delayOnTouchOnly: true,
touchStartThreshold: 5,
direction: 'vertical',
onEnd: function(evt) {
var sectionGroup = evt.to;
var cards = [...sectionGroup.querySelectorAll('.mobile-card')];
var ids = cards.map(function(c) { return c.dataset.productId; }).join(',');
var sectionId = sectionGroup.dataset.sectionId;
var formData = new FormData();
formData.append('action', 'reorder');
formData.append('category_id', sectionId);
formData.append('order', ids);
fetch('products.php?catalog_id=<?= $catalogId ?>', { method: 'POST', body: formData });
}
});
});
}
initMobileSortable();
window.addEventListener('resize', function() {
setTimeout(initMobileSortable, 300);
});
</script>
</body>
</html>
