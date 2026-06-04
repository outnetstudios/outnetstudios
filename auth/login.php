<?php
session_start();
require_once '../includes/db_connection.php'; // Verifica que esta ruta sea correcta

$error_message = ''; // Variable para almacenar mensajes de error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error_message = "Debe completar usuario y contraseña.";
    } else {
        // Verificar si la conexión a la base de datos funciona
        if (!$conn) {
            error_log('MySQL connection failed: ' . mysqli_connect_error());
            die("No se pudo procesar el inicio de sesión.");
        }

        // Buscar usuario en la base de datos
        $query = "SELECT * FROM admin_users WHERE username = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log('Login statement preparation failed: ' . $conn->error);
            die("No se pudo procesar el inicio de sesión.");
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit;
            }
        }

        $error_message = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, rgba(0, 81, 255, 0.18), transparent 30%), #0b0d1d;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        .login-container {
            width: min(100%, 440px);
            padding: 2rem;
            border-radius: 32px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(18px);
        }

        .login-container h2 {
            margin: 0 0 1rem;
            font-size: 2rem;
            letter-spacing: -0.03em;
            color: #ffffff;
            text-align: center;
        }

        .login-container p {
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #d8e0ff;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="password"] {
            display: block;
            width: 100%;
            padding: 14px 18px;
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            font-size: 1rem;
            outline: none;
            min-width: 0;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: rgba(0, 81, 255, 0.75);
            box-shadow: 0 0 0 4px rgba(0, 81, 255, 0.12);
        }

        .button-primary {
            width: 100%;
            border: none;
            border-radius: 28px;
            padding: 14px 18px;
            background: linear-gradient(90deg, #0051FF, #0e0edb);
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .button-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .error-message {
            margin-bottom: 1.25rem;
            padding: 12px 16px;
            border-radius: 24px;
            background: rgba(255, 65, 65, 0.12);
            color: #ffb3b3;
            border: 1px solid rgba(255, 65, 65, 0.24);
        }
    </style>
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
    <div class="login-container">
        <h2>Iniciar sesión</h2>
        <p>Accede al panel administrativo para revisar prospectos y gestionar datos.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button class="button-primary" type="submit">Iniciar Sesión</button>
        </form>
        <div style="text-align:center; margin-top:1rem;">
            <a class="auth-link" href="forgot_password.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html>
