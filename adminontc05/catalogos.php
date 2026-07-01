<?php
require_once __DIR__ . '/_session.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';

$pageTitle = 'Catálogos';

$repo = new CatalogRepository();
$catalogs = $repo->all();

$bridgeActive = false;
$bridgeCatalogId = 0;
$bridgeCatalogName = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_write_close();

session_name('CATALOG_SESSION');
session_start();
if (!empty($_SESSION['catalog_loggedin']) && !empty($_SESSION['catalog_admin_bridge'])) {
    $bridgeActive = true;
    $bridgeUserId = (int)$_SESSION['catalog_user_id'];
    foreach ($catalogs as $c) {
        if ((int)$c['user_id'] === $bridgeUserId) {
            $bridgeCatalogId = (int)$c['id'];
            $bridgeCatalogName = htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }
}
session_write_close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Catálogos — Admin</title>
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <style>
        .badge { display:inline-block; padding:.2em .6em; border-radius:12px; font-size:.75rem; font-weight:600; }
        .badge--published { background:rgba(0,184,148,0.2); color:#00b894; }
        .badge--draft { background:rgba(253,203,110,0.2); color:#fdcb6e; }
        .badge--archived { background:rgba(214,48,49,0.2); color:#ff7675; }
        .notice { padding:1rem; border-radius:12px; margin-bottom:1.5rem; font-size:.9rem; }
        .notice--info { background:rgba(0,81,255,0.15); border:1px solid rgba(0,81,255,0.3); color:#aab4ff; }
    </style>
</head>
<body class="dashboard-shell">
    <div class="card">
        <?php require __DIR__ . '/navbar.php'; ?>

        <div style="padding:0 2rem 2rem;">
            <?php if ($bridgeActive): ?>
                <div class="notice notice--info">
                    Modo edición activo para <strong><?php echo $bridgeCatalogName; ?></strong>.
                    <a href="#" onclick="event.preventDefault(); fetch('ajax_clear_bridge.php').then(function(){ window.location.href='catalogos.php'; }); return false;" class="button button--small button--danger" style="margin-left:1rem; background:linear-gradient(90deg,#d63031,#e17055);">Finalizar edición</a>
                </div>
            <?php endif; ?>

            <div class="table-wrap">
                <?php if (count($catalogs) === 0): ?>
                    <div class="empty">No hay catálogos registrados aún.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Catálogo</th>
                                <th>Creador</th>
                                <th>Estado</th>
                                <th>Páginas</th>
                                <th>Productos</th>
                                <th>Creado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($catalogs as $catalog): ?>
                                <?php
                                $status = $catalog['status'] ?? 'draft';
                                $badgeClass = $status === 'published' ? 'badge--published' : ($status === 'archived' ? 'badge--archived' : 'badge--draft');
                                $created = date('d/m/Y', strtotime($catalog['created_at'] ?? 'now'));
                                $name = htmlspecialchars($catalog['name'], ENT_QUOTES, 'UTF-8');
                                $creator = htmlspecialchars($catalog['creator_name'] ?? '—', ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td><strong><?php echo $name; ?></strong></td>
                                    <td><?php echo $creator; ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span></td>
                                    <td><?php echo (int)$catalog['page_count']; ?></td>
                                    <td><?php echo (int)$catalog['product_count']; ?></td>
                                    <td><?php echo $created; ?></td>
                                    <td>
                                        <a class="button button--small" href="acceso_catalogo.php?catalog_id=<?php echo (int)$catalog['id']; ?>" style="background:linear-gradient(90deg,#00b894,#00cec9);">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
