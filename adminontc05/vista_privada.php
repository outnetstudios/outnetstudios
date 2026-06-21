<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$displayName = $_SESSION['username'] ?? 'Administrador';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/plan_helpers.php';

$contacts = [];
$result = getContactData($conn);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body{font-family:Inter, sans-serif;background:linear-gradient(180deg,#0b0d1d,#071024);color:#fff;margin:0;padding:2rem}
        .card{max-width:1200px;margin:0 auto;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);padding:2rem;border-radius:24px;box-shadow:0 30px 80px rgba(0,0,0,0.25)}
        .header-row{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem}
        .header-row h1{margin:0;font-size:2rem}
        .button{display:inline-flex;align-items:center;justify-content:center;padding:.85rem 1.5rem;border-radius:30px;background:linear-gradient(90deg,#0051FF,#0e0edb);color:#fff;text-decoration:none;border:none}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse}
        th,td{padding:1rem 0.85rem;text-align:left;border-bottom:1px solid rgba(255,255,255,0.08);color:#e9ecff}
        th{font-size:.95rem;text-transform:uppercase;letter-spacing:.05em;color:#aab4ff;background:rgba(255,255,255,0.05)}
        tbody tr:nth-child(even){background:rgba(255,255,255,0.03)}
        tbody tr:hover{background:rgba(255,255,255,0.06)}
        .empty{padding:2rem;text-align:center;color:rgba(255,255,255,0.7)}
    </style>
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="dashboard-shell">
    <div class="card">
        <div class="header-row">
            <div>
                <h1>Panel Admin</h1>
                <p>Bienvenido, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>.</p>
            </div>
            <a class="button" href="logout.php">Cerrar sesión</a>
        </div>

        <div class="module-nav">
            <a class="module-nav__item is-active" href="vista_privada.php">Prospectos</a>
            <a class="module-nav__item" href="../catalog_users.php">Usuarios catálogo</a>
            <a class="module-nav__item" href="catalogos.php">Catálogos</a>
        </div>

        <div class="table-wrap">
            <?php if (count($contacts) === 0): ?>
                <div class="empty">No hay prospectos registrados aún.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Plan</th>
                            <th>Empresa</th>
                            <th>Sector</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($contact['nombre_apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($contact['telefono'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(formatPlanInteresLabel($contact['plan_interes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($contact['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($contact['sector'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($contact['descripcion'], ENT_QUOTES, 'UTF-8')); ?></td>
                                <td><?php echo htmlspecialchars($contact['fecha_envio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
