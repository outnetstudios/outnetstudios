<?php
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';

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
                                                    <img src="../<?= htmlspecialchars($c['cover_image'], ENT_QUOTES, 'UTF-8') ?>" class="w-full h-full object-cover">
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

    <script>
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
        }
    </script>
</body>
</html>
