<?php
session_start();

// Verifica que el usuario esté autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../includes/db_connection.php'; // Asegúrate de que la ruta sea correcta

// Obtener datos de la tabla "contacto"
$contactData = getContactData($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4; /* Fondo general */
            margin: 0;
            padding: 20px;
        }

        .dashboard-container {
            background-color: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 1000px; /* Ancho máximo de la tabla */
            margin: auto; /* Centrar el contenedor */
        }

        h1 {
            text-align: center; /* Centrar el título */
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #007BFF; /* Color de fondo para el encabezado */
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2; /* Color de fondo para filas pares */
        }

        tr:hover {
            background-color: #e0e0e0; /* Color de fondo al pasar el mouse */
        }

        @media (max-width: 600px) {
            th, td {
                font-size: 14px; /* Tamaño de fuente en pantallas pequeñas */
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Datos de Prospectos</h1>
        <table>
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
                // Conexión a la base de datos
                require_once 'includes/db_connection.php';

                // Consulta para obtener los datos de la tabla contacto
                $query = "SELECT nombre_apellido, email, telefono, plan_interes, nombre_empresa, sector, descripcion, fecha_envio FROM contacto";
                $result = $conn->query($query);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['nombre_apellido']}</td>
                            <td>{$row['email']}</td>
                            <td>{$row['telefono']}</td>
                            <td>{$row['plan_interes']}</td>
                            <td>{$row['nombre_empresa']}</td>
                            <td>{$row['sector']}</td>
                            <td>{$row['descripcion']}</td>
                            <td>{$row['fecha_envio']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No hay datos disponibles.</td></tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
