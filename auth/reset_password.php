<?php
session_start();
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/PasswordResetRepository.php';

$error_message = '';
$success_message = '';
$token = trim($_GET['token'] ?? '');

$resetRepository = new PasswordResetRepository();
$resetRecord = $token ? $resetRepository->findValidToken($token) : false;

if (!$token || !$resetRecord) {
    $error_message = 'El enlace de recuperación no es válido o ha expirado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetRecord) {
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');

    if ($password === '' || $passwordConfirm === '') {
        $error_message = 'Completa ambos campos de contraseña.';
    } elseif ($password !== $passwordConfirm) {
        $error_message = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error_message = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $userRepository = new UserRepository();
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $userRepository->updatePassword((int)$resetRecord['user_id'], $hashed);
        $resetRepository->deleteToken($token);
        $success_message = 'Contraseña actualizada con éxito. Ya puedes iniciar sesión.';
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
            <div class="link"><a href="login.php">Volver al login</a></div>
        <?php elseif ($resetRecord): ?>
            <form method="post" action="?token=<?php echo urlencode($token); ?>">
                <label for="password">Nueva contraseña</label>
                <input type="password" id="password" name="password" required>

                <label for="password_confirm">Repite la contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" required>

                <button type="submit">Cambiar contraseña</button>
            </form>
            <div class="link"><a href="login.php">Volver al login</a></div>
        <?php else: ?>
            <div class="link"><a href="forgot_password.php">Solicitar otro enlace</a></div>
        <?php endif; ?>
    </div>
</body>
</html>
