<?php
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';

catalogRequireLogin();
$repo = new CatalogRepository();
$userId = (int)catalogGetUserId();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars(catalogGetUserEmail(), ENT_QUOTES, 'UTF-8');
$catalogs = $repo->allByUser($userId);
$activeCount = count(array_filter($catalogs, fn($c) => $c['status'] === 'published'));
$totalCount = count($catalogs);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Catálogos</title>
<?php $pageTitle = 'Mis Catálogos'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
</head>
<body class="bg-background text-on-background min-h-screen">
<?php require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>

    <main class="min-h-[calc(100vh-80px)]">
        <!-- Main Content -->
        <section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
            <div class="max-w-7xl mx-auto">
                <header class="mb-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
                    <div>
                        <h1 class="font-display-lg-mobile text-display-lg-mobile text-on-surface">Mis Catálogos</h1>
                    </div>
                    <div class="flex items-center gap-xs">
                        <a href="create.php" class="primary-gradient text-white px-sm py-xs rounded-full font-title-sm text-title-sm active:scale-95 transition-transform primary-glow no-underline">+ Nuevo catálogo</a>
                        <div class="glass-panel px-sm py-xs rounded-xl flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[18px] text-primary">auto_awesome</span>
                            <div class="flex items-center gap-1">
                                <span class="text-label-caps font-label-caps text-on-surface-variant">ACTIVOS</span>
                                <span class="text-title-sm font-title-sm text-on-surface"><?= $activeCount ?></span>
                            </div>
                        </div>
                    </div>
                </header>

                <?php if (empty($catalogs)): ?>
                    <div class="glass-panel rounded-3xl p-xl text-center">
                        <span class="material-symbols-outlined text-6xl text-on-surface-variant/30 mb-md">menu_book</span>
                        <p class="font-title-sm text-title-sm text-on-surface mb-xs">Aún no tienes catálogos</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant/70 mb-md">Crea tu primer catálogo para empezar a agregar productos.</p>
                        <a href="create.php" class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm inline-flex active:scale-95 transition-transform primary-glow no-underline">+ Crear catálogo</a>
                    </div>
                <?php else: ?>
                    <!-- Table Container -->
                    <div class="glass-panel rounded-3xl overflow-hidden mb-xl">
                        <div class="p-md border-b border-outline-variant/10 flex flex-wrap gap-md justify-between items-center bg-surface-container-highest/20">
                            <h2 class="font-title-sm text-title-sm text-primary flex items-center gap-xs">
                                <span class="material-symbols-outlined">list_alt</span>
                                Inventario de Catálogos
                            </h2>
                            <div class="flex gap-xs items-center">
                                <span class="material-symbols-outlined text-on-surface-variant/50">search</span>
                                <input id="tableSearch" class="bg-surface-variant/20 border border-outline-variant/30 rounded-full px-md py-1 text-body-sm text-on-surface placeholder-on-surface-variant/50 focus:outline-none focus:border-primary/50 w-48" placeholder="Buscar..." type="text" oninput="filterTable(this.value)">
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="text-left bg-surface-container-low/50">
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Nombre</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Slug</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-center">Estado</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Creado</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-center">Categorías</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-center">Productos</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-center">Diseñar</th>
                                        <th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/5">
                                    <?php foreach ($catalogs as $c): ?>
                                    <?php
                                        $statusLabel = match ($c['status']) {
                                            'published' => 'Published',
                                            'archived' => 'Archived',
                                            default => 'Draft'
                                        };
                                        $statusClass = match ($c['status']) {
                                            'published' => 'bg-primary/10 text-primary border-primary/20',
                                            'archived' => 'bg-error/10 text-error/80 border-error/20',
                                            default => 'bg-on-surface-variant/10 text-on-surface-variant border-on-surface-variant/20'
                                        };
                                    ?>
                                    <tr class="hover:bg-surface-variant/10 transition-colors group">
                                        <td class="p-md">
                                            <div class="flex items-center gap-md">
                                                <div class="w-12 h-16 rounded-lg bg-surface-container-high overflow-hidden border border-outline-variant/30 group-hover:border-primary/50 transition-colors shadow-sm flex items-center justify-center">
                                                    <?php if (!empty($c['cover_image'])): ?>
                                                    <img src="<?= htmlspecialchars(imageUrl($c['cover_image']), ENT_QUOTES, 'UTF-8') ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                    <span class="material-symbols-outlined text-on-surface-variant/40">image</span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="font-title-sm text-title-sm text-on-surface font-semibold"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </td>
                                        <td class="p-md font-body-sm text-body-sm text-on-surface-variant italic"><?= htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="p-md text-center">
                                            <span class="px-md py-1 rounded-full text-label-caps font-label-caps <?= $statusClass ?> border"><?= $statusLabel ?></span>
                                        </td>
                                        <td class="p-md font-body-sm text-body-sm text-on-surface-variant"><?= htmlspecialchars($c['created_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="p-md text-center">
                                            <a href="categories.php?catalog_id=<?= $c['id'] ?>" class="inline-flex items-center gap-1 p-2 hover:bg-primary/20 rounded-lg text-primary transition-colors" title="Categorías"><span class="material-symbols-outlined text-[20px]">folder</span></a>
                                        </td>
                                        <td class="p-md text-center">
                                            <a href="products.php?catalog_id=<?= $c['id'] ?>" class="inline-flex items-center gap-1 p-2 hover:bg-primary/20 rounded-lg text-primary transition-colors" title="Productos"><span class="material-symbols-outlined text-[20px]">package</span></a>
                                        </td>
                                        <td class="p-md text-center">
                                            <a href="pages.php?catalog_id=<?= $c['id'] ?>" class="inline-flex items-center gap-1 p-2 hover:bg-primary/20 rounded-lg text-primary transition-colors" title="Páginas"><span class="material-symbols-outlined text-[20px]">description</span></a>
                                        </td>
                                        <td class="p-md text-center">
                                            <div class="flex justify-center gap-1">
                                                <button onclick="copiarEnlace(<?= $c['id'] ?>)" class="p-2 hover:bg-primary/20 rounded-lg text-primary transition-colors" title="Compartir"><span class="material-symbols-outlined text-[20px]">share</span></button>
                                                <a href="preview.php?catalog_id=<?= $c['id'] ?>" class="p-2 hover:bg-tertiary/20 rounded-lg text-tertiary transition-colors" title="Vista previa"><span class="material-symbols-outlined text-[20px]">visibility</span></a>
                                                <a href="edit.php?id=<?= $c['id'] ?>" class="p-2 hover:bg-on-surface-variant/20 rounded-lg text-on-surface-variant transition-colors" title="Editar"><span class="material-symbols-outlined text-[20px]">edit</span></a>
                                                <a href="delete.php?id=<?= $c['id'] ?>" class="p-2 hover:bg-error/20 rounded-lg text-error transition-colors" title="Borrar" onclick="return confirm('¿Borrar este catálogo y todos sus datos?')"><span class="material-symbols-outlined text-[20px]">delete</span></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                        </table>
                    </div>

                    <!-- Mobile cards -->
                    <div class="mobile-cards">
                    <?php foreach ($catalogs as $c):
                        $statusLabel = match ($c['status']) {
                            'published' => 'Published',
                            'archived' => 'Archived',
                            default => 'Draft'
                        };
                        $statusClass = match ($c['status']) {
                            'published' => 'bg-primary/10 text-primary border-primary/20',
                            'archived' => 'bg-error/10 text-error/80 border-error/20',
                            default => 'bg-on-surface-variant/10 text-on-surface-variant border-on-surface-variant/20'
                        };
                    ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header">
                            <div class="mobile-card-cover">
                                <?php if (!empty($c['cover_image'])): ?>
                                <img src="<?= htmlspecialchars(imageUrl($c['cover_image']), ENT_QUOTES, 'UTF-8') ?>">
                                <?php else: ?>
                                <span class="material-symbols-outlined">image</span>
                                <?php endif; ?>
                            </div>
                            <div class="mobile-card-header-text">
                                <span class="mobile-card-title"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="mobile-card-slug"><?= htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <div class="mobile-card-body">
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">Estado</span>
                                <span class="px-md py-1 rounded-full text-label-caps font-label-caps <?= $statusClass ?> border"><?= $statusLabel ?></span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">Creado</span>
                                <span class="mobile-card-value"><?= htmlspecialchars($c['created_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <div class="mobile-card-actions">
                            <div class="mobile-card-actions-left">
                                <a href="categories.php?catalog_id=<?= $c['id'] ?>" class="mobile-card-btn" title="Categorías"><span class="material-symbols-outlined">folder</span></a>
                                <a href="products.php?catalog_id=<?= $c['id'] ?>" class="mobile-card-btn" title="Productos"><span class="material-symbols-outlined">package</span></a>
                                <a href="pages.php?catalog_id=<?= $c['id'] ?>" class="mobile-card-btn" title="Páginas"><span class="material-symbols-outlined">description</span></a>
                            </div>
                            <div class="mobile-card-actions-right">
                                <button onclick="copiarEnlace(<?= $c['id'] ?>)" class="mobile-card-btn" title="Compartir"><span class="material-symbols-outlined">share</span></button>
                                <a href="preview.php?catalog_id=<?= $c['id'] ?>" class="mobile-card-btn preview" title="Vista previa"><span class="material-symbols-outlined">visibility</span></a>
                                <a href="edit.php?id=<?= $c['id'] ?>" class="mobile-card-btn edit" title="Editar"><span class="material-symbols-outlined">edit</span></a>
                                <a href="delete.php?id=<?= $c['id'] ?>" class="mobile-card-btn delete" title="Borrar" onclick="return confirm('¿Borrar este catálogo y todos sus datos?')"><span class="material-symbols-outlined">delete</span></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                </div>

                <!-- Pagination -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-sm glass-panel p-sm rounded-2xl">
                    <p class="font-body-sm text-body-sm text-on-surface-variant/70">Mostrando <?= $totalCount ?> de <?= $totalCount ?> catálogos</p>
                    <div class="flex items-center gap-1">
                        <button class="flex items-center gap-1 px-sm py-1 rounded-full font-label-caps text-label-caps text-on-surface-variant/60 hover:text-tertiary hover:bg-surface-variant/20 transition-all disabled opacity-30 pointer-events-none">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span> Anterior
                        </button>
                        <div class="flex gap-1">
                            <button class="w-8 h-8 rounded-full bg-primary/20 text-primary font-bold text-label-caps text-[11px]">1</button>
                        </div>
                        <button class="flex items-center gap-1 px-sm py-1 rounded-full font-label-caps text-label-caps text-on-surface-variant/60 hover:text-tertiary hover:bg-surface-variant/20 transition-all disabled opacity-30 pointer-events-none">
                            Siguiente <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
@media (max-width: 768px) {
.overflow-x-auto { display: none; }
.mobile-cards { display: flex; flex-direction: column; gap: 0.75rem; padding: 0.75rem; }
.mobile-card { background: var(--surface-container-high, #1e1e2e); border-radius: 12px; padding: 1rem; border: 1px solid rgba(255,255,255,0.06); }
.mobile-card-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
.mobile-card-cover { width: 56px; height: 64px; border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08); flex-shrink: 0; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; }
.mobile-card-cover img { width: 100%; height: 100%; object-fit: cover; }
.mobile-card-cover span { font-size: 20px; color: rgba(255,255,255,0.2); }
.mobile-card-header-text { flex: 1; min-width: 0; }
.mobile-card-title { font-size: 0.95rem; font-weight: 700; color: var(--text-on-surface, #e0e0f0); display: block; }
.mobile-card-slug { font-size: 0.75rem; color: rgba(255,255,255,0.35); font-style: italic; }
.mobile-card-body { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 0.75rem; }
.mobile-card-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; }
.mobile-card-label { color: rgba(255,255,255,0.4); font-weight: 500; }
.mobile-card-value { color: rgba(255,255,255,0.7); font-weight: 600; text-align: right; }
.mobile-card-actions { display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.06); }
.mobile-card-actions-left, .mobile-card-actions-right { display: flex; gap: 0.5rem; }
.mobile-card-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 2.6rem; height: 2.6rem; border-radius: 50%; color: rgba(255,255,255,0.5); text-decoration: none; transition: all 0.15s; border: none; background: transparent; cursor: pointer; font: inherit; padding: 0; }
.mobile-card-btn span { font-size: 1.1rem; }
.mobile-card-btn:hover { background: rgba(255,255,255,0.06); color: var(--text-on-surface, #e0e0f0); }
.mobile-card-btn.preview:hover { background: rgba(100,200,180,0.15); color: #64c8b4; }
.mobile-card-btn.edit:hover { background: rgba(100,180,255,0.15); color: #64b4ff; }
.mobile-card-btn.delete:hover { background: rgba(255,80,80,0.15); color: #ff5050; }
}
@media (min-width: 769px) {
.mobile-cards { display: none; }
}
</style>

<style>
.toast-share{position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:100;padding:0.6rem 1.2rem;border-radius:999px;background:rgba(0,200,150,0.9);color:#fff;font-size:0.85rem;font-weight:600;opacity:0;transition:opacity 0.3s;pointer-events:none}.toast-share.show{opacity:1}
</style>
<div class="toast-share" id="toastShare"></div>
<script>
    function mostrarToast(msg) {
        var t = document.getElementById('toastShare');
        t.textContent = msg; t.classList.add('show');
        setTimeout(function(){ t.classList.remove('show'); }, 2000);
    }
    function copiarEnlace(id) {
        var url = (location.protocol === 'https:' ? 'https' : 'http') + '://' + location.host + '/ver_catalogo.php?id=' + id;
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
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.transform = 'translateY(-2px)';
            row.style.transition = 'transform 0.2s ease';
        });
        row.addEventListener('mouseleave', () => {
            row.style.transform = 'translateY(0)';
        });
    });
    function filterTable(val) {
        const q = val.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
        document.querySelectorAll('.mobile-card').forEach(card => {
            card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }
</script>
</body>
</html>
