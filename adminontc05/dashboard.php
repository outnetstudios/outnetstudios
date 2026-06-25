<?php
require_once __DIR__ . '/_session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/plan_helpers.php';

$pageTitle = 'Panel Admin';
$pageSubtitle = '';

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
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="dashboard-shell">
    <div class="card">
        <?php require __DIR__ . '/navbar.php'; ?>

        <div class="table-wrap" style="padding:0 2rem 2rem;">
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
