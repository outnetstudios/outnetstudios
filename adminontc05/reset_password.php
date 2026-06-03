<?php
session_start();
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/PasswordResetRepository.php';

$error_message = '';
$success_message = '';
$token = $_GET['token'] ?? ''; 

$resetRepository = new PasswordResetRepository();
$reset = $resetRepository->findValidToken($token);

if (!$reset) {
    $error_message = 'Token inválido o expirado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($password === '' || $confirm_password === '') {
        $error_message = 'Completa ambos campos de contraseña.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error_message = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $userRepository = new UserRepository();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userRepository->updatePassword((int)$reset['user_id'], $hashedPassword);
        $resetRepository->deleteToken($token);
        $success_message = 'Contraseña actualizada. Ya puedes iniciar sesión.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body { min-height:100vh; margin:0; display:flex; align-items:center; justify-content:center; background: radial-gradient(circle at top, rgba(0,81,255,0.18), transparent 30%), #0b0d1d; color:#fff; font-family:'Inter',sans-serif; }
        .panel { width:min(100%,440px); padding:2rem; border-radius:28px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); box-shadow:0 30px 80px rgba(0,0,0,0.2); }
        h2 { margin:0 0 1rem; font-size:2rem; text-align:center; }
        label { display:block; margin-bottom:0.5rem; color:#d8e0ff; }
        input { display:block; width:100%; min-width:0; padding:14px 18px; border-radius:28px; border:1px solid rgba(255,255,255,0.18); background:rgba(255,255,255,0.08); color:#fff; margin-bottom:1rem; }
        button { width:100%; padding:14px 18px; border:none; border-radius:28px; background:linear-gradient(90deg,#0051FF,#0e0edb); color:#fff; cursor:pointer; }
        .message { margin-bottom:1rem; padding:1rem; border-radius:24px; }
        .error { background:rgba(255,65,65,0.12); color:#ffb3b3; border:1px solid rgba(255,65,65,0.24); }
        .success { background:rgba(0,255,138,0.12); color:#c8ffdc; border:1px solid rgba(0,255,138,0.24); }
        .link { margin-top:1rem; text-align:center; }
        .link a { color:#a2c7ff; text-decoration:none; }
    </style>
</head>
<body>
    <div class="panel">
        <h2>Restablecer contraseña</h2>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($reset && !$success_message): ?>
            <form method="post" action="?token=<?php echo urlencode($token); ?>">
                <label for="password">Nueva contraseña</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirmar contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit">Actualizar contraseña</button>
            </form>
        <?php endif; ?>

        <div class="link">
            <a href="login.php">Volver al login</a>
        </div>
    </div>
</body>
</html>
