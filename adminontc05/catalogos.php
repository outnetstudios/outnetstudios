<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$displayName = $_SESSION['username'] ?? 'Administrador';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';

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
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
    <style>
        body{font-family:Inter,sans-serif;background:linear-gradient(180deg,#0b0d1d,#071024);color:#fff;margin:0;padding:2rem}
        .card{max-width:1200px;margin:0 auto;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);padding:2rem;border-radius:24px;box-shadow:0 30px 80px rgba(0,0,0,0.25)}
        .header-row{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem}
        .header-row h1{margin:0;font-size:2rem}
        .button{display:inline-flex;align-items:center;justify-content:center;padding:.85rem 1.5rem;border-radius:30px;background:linear-gradient(90deg,#0051FF,#0e0edb);color:#fff;text-decoration:none;border:none;font-size:.9rem;cursor:pointer}
        .button--small{padding:.5rem 1rem;font-size:.8rem}
        .button--success{background:linear-gradient(90deg,#00b894,#00cec9)}
        .button--danger{background:linear-gradient(90deg,#d63031,#e17055)}
        .button--warning{background:linear-gradient(90deg,#fdcb6e,#e17055);color:#2d3436}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse;font-size:.9rem}
        th,td{padding:.85rem .75rem;text-align:left;border-bottom:1px solid rgba(255,255,255,0.08);color:#e9ecff}
        th{font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;color:#aab4ff;background:rgba(255,255,255,0.05);white-space:nowrap}
        tbody tr:nth-child(even){background:rgba(255,255,255,0.03)}
        tbody tr:hover{background:rgba(255,255,255,0.06)}
        .badge{display:inline-block;padding:.2em .6em;border-radius:12px;font-size:.75rem;font-weight:600}
        .badge--published{background:rgba(0,184,148,0.2);color:#00b894}
        .badge--draft{background:rgba(253,203,110,0.2);color:#fdcb6e}
        .badge--archived{background:rgba(214,48,49,0.2);color:#ff7675}
        .empty{padding:2rem;text-align:center;color:rgba(255,255,255,0.7)}
        .notice{padding:1rem;border-radius:12px;margin-bottom:1.5rem;font-size:.9rem}
        .notice--info{background:rgba(0,81,255,0.15);border:1px solid rgba(0,81,255,0.3);color:#aab4ff}
        .module-nav{margin-bottom:1.5rem}
        @media(max-width:768px){
            body{padding:1rem}
            .card{padding:1rem}
            .header-row{flex-direction:column;align-items:flex-start}
        }
    </style>
</head>
<body class="dashboard-shell">
    <div class="card">
        <div class="header-row">
            <div>
                <h1>Catálogos</h1>
                <p>Bienvenido, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>.</p>
            </div>
            <a class="button" href="dashboard.php">← Panel</a>
        </div>

        <div class="module-nav">
            <a class="module-nav__item" href="dashboard.php">Prospectos</a>
            <a class="module-nav__item" href="catalog_users.php">Usuarios catálogo</a>
            <a class="module-nav__item is-active" href="catalogos.php">Catálogos</a>
        </div>

        <?php if ($bridgeActive): ?>
            <div class="notice notice--info">
                Modo edición activo para <strong><?php echo $bridgeCatalogName; ?></strong>.
                <a href="salir_catalogo.php" class="button button--small button--danger" style="margin-left:1rem">Finalizar edición</a>
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
                                    <a class="button button--small button--success" href="acceso_catalogo.php?catalog_id=<?php echo (int)$catalog['id']; ?>">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
