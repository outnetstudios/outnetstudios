<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error_message = 'Debe completar usuario y contraseña.';
    } else {
        $stmt = $conn->prepare('SELECT * FROM admin_users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $user['username'];
                header('Location: vista_privada.php');
                exit;
            }
        }

        $error_message = 'Usuario o contraseña incorrectos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, rgba(0, 81, 255, 0.2), transparent 30%), #060816;
            font-family: 'Inter', sans-serif;
            color: #fff;
        }
        .login-panel {
            width: min(100%, 420px);
            padding: 2rem;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.28);
        }
        .login-panel h2 {
            margin: 0 0 1rem;
            font-size: 2rem;
            color: #fff;
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
        }
        input[type="text"], input[type="password"] {
            display: block;
            width: 100%;
            padding: 14px 18px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            margin-bottom: 1rem;
            font-size: 1rem;
            min-width: 0;
        }
        button {
            width: 100%;
            padding: 14px 18px;
            border-radius: 24px;
            border: none;
            background: linear-gradient(90deg, #0051FF, #0e0edb);
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
        }
        .error-message {
            margin: 1rem 0;
            padding: 0.95rem 1rem;
            border-radius: 20px;
            background: rgba(255, 65, 65, 0.12);
            color: #ffb3b3;
        }
    </style>
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
    <div class="login-panel">
        <h2>Admin Outnet Studios</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label for="username">Usuario</label>
            <input type="text" name="username" id="username" autocomplete="off" required>

            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" autocomplete="current-password" required>

            <button type="submit">Ingresar</button>
        </form>
        <div style="text-align:center; margin-top:1rem;">
            <a class="auth-link" href="forgot_password.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html>
