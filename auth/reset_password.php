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
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
<?php
$authPanelClass = 'panel';
$authTitle = 'Restablecer contraseña';
$authSubtitle = '';
$authErrorMessage = ($success_message !== '') ? '' : $error_message;
$authSuccessMessage = $success_message;
$authFormAction = '?token=' . urlencode($token);
$authFormMethod = 'post';
$authButtonText = 'Cambiar contraseña';
$authFields = [
    ['type' => 'hidden', 'name' => 'token', 'value' => $token],
    ['type' => 'password', 'id' => 'password', 'name' => 'password', 'label' => 'Nueva contraseña', 'required' => true],
    ['type' => 'password', 'id' => 'password_confirm', 'name' => 'password_confirm', 'label' => 'Repite la contraseña', 'required' => true],
];
$authShowForm = $resetRecord && $authSuccessMessage === '';
$authLinks = [];
if ($authSuccessMessage !== '') {
    $authLinks = [
        ['href' => 'login.php', 'label' => 'Volver al login'],
    ];
} elseif ($resetRecord) {
    $authLinks = [
        ['href' => 'login.php', 'label' => 'Volver al login'],
    ];
} else {
    $authLinks = [
        ['href' => 'forgot_password.php', 'label' => 'Solicitar otro enlace'],
    ];
}
include __DIR__ . '/../templates/partials/auth_form.php';
?>
</body>
</html>
