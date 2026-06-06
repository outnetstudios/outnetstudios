<?php
session_start();

// Verifica que el usuario esté autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/plan_helpers.php';

// Obtener datos de la tabla "contacto"
$contactData = getContactData($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at top, rgba(0, 81, 255, 0.12), transparent 28%), #080a14;
            color: white;
            padding: 2rem 1rem 3rem;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 32px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(0, 81, 255, 0.18), rgba(14, 14, 219, 0.08));
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: clamp(1.8rem, 2.3vw, 2.4rem);
            line-height: 1.05;
            color: #ffffff;
        }

        .dashboard-header p {
            margin: 0.5rem 0 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }

        .button-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background: linear-gradient(90deg, #0051FF, #0e0edb);
            border-radius: 28px;
            border: none;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .button-primary:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 860px;
        }

        table thead tr {
            background: rgba(255, 255, 255, 0.08);
            color: #d8e0ff;
        }

        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: #e9ecff;
        }

        th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            color: #b0b8d4;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.04);
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        tbody td {
            font-size: 0.94rem;
        }

        .table-scroll {
            overflow-x: auto;
            padding: 0.5rem 0 0;
        }

        @media (max-width: 900px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .dashboard-table {
                min-width: 720px;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            th, td {
                padding: 0.85rem;
            }

            .dashboard-table {
                min-width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="dashboard-shell">
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1>Panel administrativo</h1>
                <p>Revisa los prospectos y datos de contacto desde un mismo lugar.</p>
            </div>
            <a href="logout.php" class="button-primary">Cerrar sesión</a>
        </div>
        <div class="module-nav">
            <a class="module-nav__item is-active" href="dashboard.php">Prospectos</a>
            <a class="module-nav__item" href="../catalog_users.php">Usuarios catálogo</a>
            <span class="module-nav__item is-disabled">Catálogos</span>
        </div>
        <div class="table-scroll">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Nombre y Apellido</th>
                        <th>Email</th>
                        <th>Número de Teléfono</th>
                        <th>Plan de Interés</th>
                        <th>Nombre de la Empresa</th>
                        <th>Sector/Industria</th>
                        <th>Descripción de Proyecto</th>
                        <th>Fecha de Envío</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($contactData && $contactData->num_rows > 0) {
                        while ($row = $contactData->fetch_assoc()) {
                            $nombre = htmlspecialchars($row['nombre_apellido'] ?? '', ENT_QUOTES, 'UTF-8');
                            $email = htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8');
                            $telefono = htmlspecialchars($row['telefono'] ?? '', ENT_QUOTES, 'UTF-8');
                            $plan = htmlspecialchars(formatPlanInteresLabel($row['plan_interes'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $empresa = htmlspecialchars($row['nombre_empresa'] ?? '', ENT_QUOTES, 'UTF-8');
                            $sector = htmlspecialchars($row['sector'] ?? '', ENT_QUOTES, 'UTF-8');
                            $descripcion = nl2br(htmlspecialchars($row['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'));
                            $fecha = htmlspecialchars($row['fecha_envio'] ?? '', ENT_QUOTES, 'UTF-8');

                            echo "<tr>
                                <td>{$nombre}</td>
                                <td>{$email}</td>
                                <td>{$telefono}</td>
                                <td>{$plan}</td>
                                <td>{$empresa}</td>
                                <td>{$sector}</td>
                                <td>{$descripcion}</td>
                                <td>{$fecha}</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center; color: rgba(255,255,255,0.7);'>No hay datos disponibles.</td></tr>";
                    }

                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
