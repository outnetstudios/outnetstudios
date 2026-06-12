<?php
require_once __DIR__ . '/includes/catalog_auth_helpers.php';
catalogRequireLogin();
$userId = (int)catalogGetUserId();

require_once __DIR__ . '/src/Database.php';
$pdo = Database::getConnection();

$message = '';
$deleted = 0;

// Delete duplicate category pages (keep first per catalog)
if (isset($_GET['delete']) && $_GET['delete'] === 'all') {
    // For each catalog, find all category pages ordered by sort_order, keep first, delete rest
    $stmt = $pdo->prepare("SELECT id, catalog_id FROM catalog_pages
        WHERE user_id = ? AND page_type = 'category'
        ORDER BY catalog_id, sort_order ASC, id ASC");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $seen = [];
    $toDelete = [];
    foreach ($rows as $r) {
        $cid = (int)$r['catalog_id'];
        if (isset($seen[$cid])) {
            $toDelete[] = (int)$r['id'];
        } else {
            $seen[$cid] = true;
        }
    }

    if (!empty($toDelete)) {
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $delStmt = $pdo->prepare("DELETE FROM catalog_pages WHERE id IN ($placeholders)");
        $delStmt->execute($toDelete);
        $deleted = $delStmt->rowCount();
        $message = "✅ Se eliminaron $deleted páginas de categoría duplicadas.";
    } else {
        $message = "No hay páginas duplicadas.";
    }
}

// Show current pages
$stmt = $pdo->prepare("SELECT cp.id, cp.title, cp.page_type, cp.sort_order, c.name AS catalog_name
    FROM catalog_pages cp
    JOIN catalogs c ON cp.catalog_id = c.id
    WHERE cp.user_id = ?
    ORDER BY cp.catalog_id, cp.sort_order");
$stmt->execute([$userId]);
$pages = $stmt->fetchAll();

// Count category pages per catalog
$stmt = $pdo->prepare("SELECT c.id, c.name, COUNT(cp.id) AS cnt
    FROM catalogs c
    LEFT JOIN catalog_pages cp ON cp.catalog_id = c.id AND cp.page_type = 'category' AND cp.user_id = ?
    GROUP BY c.id
    HAVING cnt > 1");
$stmt->execute([$userId]);
$dupedCatalogs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Limpiar páginas</title>
<style>
body { font-family: monospace; background: #111; color: #ddd; padding: 2rem; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 0.4rem 0.8rem; text-align: left; border-bottom: 1px solid #333; font-size: 0.85rem; }
th { color: #a5b4fc; }
a { color: #a5b4fc; }
.msg { background: #1a2a1a; border: 1px solid #3a5a3a; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
.warn { background: #2a1a1a; border: 1px solid #5a3a3a; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
code { background: #222; padding: 0.2rem 0.4rem; border-radius: 4px; }
</style></head>
<body>
<h1>🧹 Limpiar páginas de categoría</h1>

<?php if ($message): ?><div class="msg"><?= $message ?></div><?php endif; ?>

<?php if (!empty($dupedCatalogs)): ?>
<div class="warn">
    <strong>Catálogos con páginas de categoría duplicadas:</strong>
    <ul>
    <?php foreach ($dupedCatalogs as $d): ?>
        <li><?= htmlspecialchars($d['name']) ?> (<?= $d['cnt'] ?> páginas de categoría)</li>
    <?php endforeach; ?>
    </ul>
    <p><a href="?delete=all">🗑️ Eliminar todas las duplicadas (conservar 1 por catálogo)</a></p>
</div>
<?php else: ?>
<p>No hay catálogos con páginas de categoría duplicadas.</p>
<?php endif; ?>

<h2>Todas las páginas</h2>
<table>
<tr><th>ID</th><th>Catálogo</th><th>Título</th><th>Tipo</th><th>Orden</th></tr>
<?php foreach ($pages as $p): ?>
<tr>
    <td><?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['catalog_name']) ?></td>
    <td><?= htmlspecialchars($p['title'] ?? '') ?></td>
    <td><?= $p['page_type'] ?></td>
    <td><?= $p['sort_order'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<p><a href="catalog_admin/index.php">← Volver a catálogos</a></p>
</body></html>
