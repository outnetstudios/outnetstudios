<?php
session_start();
require_once '../includes/db_connection.php'; // Verifica que esta ruta sea correcta

$error_message = ''; // Variable para almacenar mensajes de error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verificar si la conexión a la base de datos funciona
    if (!$conn) {
        die("Conexión fallida: " . mysqli_connect_error());
    }

    // Buscar usuario en la base de datos
    $query = "SELECT * FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Contraseña incorrecta.";
        }
    } else {
        $error_message = "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        /* Estilos generales para el formulario */
        form {
            background-color: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto; /* Centrar el formulario */
        }

        /* Estilos para los inputs y textarea */
        input[type="text"],
        input[type="password"], /* Asegúrate de incluir input[type="password"] */
        textarea,
        select {
            width: 100%;
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 30px; /* Esquinas totalmente redondas */
            border: 1px solid #ccc;
            background-color: #f7f7f7; /* Gris atenuado */
            font-size: 16px;
            color: #333;
            box-sizing: border-box;
        }

        /* Estilo para los botones de enviar */
        button[type="submit"] {
            background-color: #007BFF; /* Color de fondo para el botón */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 30px; /* Esquinas redondeadas */
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px; /* Espacio encima del botón */
            width: 100%; /* Asegúrate de que el botón tenga un ancho completo */
        }

        button[type="submit"]:hover {
            background-color: #0056b3; /* Color del botón en hover */
        }

        /* Ajustes para etiquetas */
        label {
            font-weight: medium;
            margin-bottom: 0.5rem;
            display: block;
            color: black;
            text-align: left; /* Alinea el texto a la izquierda */
            margin-left: 0.5rem;
        }


    </style>
</head>
<body>

<div class="login-container">
    <h2>Iniciar sesión</h2>
    
    <!-- Mensaje de error (si hay) -->
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <form id="loginForm" method="POST" action="auth/login.php">
    <label for="username">Usuario</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" required>

    <!-- Botón de Iniciar Sesión justo debajo del campo de contraseña -->
    <button type="submit">Iniciar Sesión</button>
</form>
</div>

</body>
</html>
