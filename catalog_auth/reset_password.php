<?php
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';

$repo = new CatalogUserRepository();
$message = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

$reset = null;
if ($token !== '') {
    $reset = $repo->findValidToken($token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');
    $token = trim($_POST['token'] ?? '');

    if ($password === '' || $password !== $password2) {
        $message = 'Las contraseñas no coinciden o están vacías.';
    } else {
        $reset = $repo->findValidToken($token);
        if ($reset) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $repo->updatePassword((int)$reset['user_id'], $hash);
            $repo->deleteToken($token);
            $message = 'Contraseña actualizada. Puedes iniciar sesión.';
        } else {
            $message = 'Token inválido o expirado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Restablecer contraseña</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
<?php
$authPanelClass = 'login-panel';
$authTitle = 'Restablecer contraseña';
$authSubtitle = '';
$authErrorMessage = ($message === 'Contraseña actualizada. Puedes iniciar sesión.') ? '' : $message;
$authSuccessMessage = ($message === 'Contraseña actualizada. Puedes iniciar sesión.') ? $message : '';
$authFormAction = '?token=' . urlencode($token);
$authFormMethod = 'post';
$authButtonText = 'Actualizar';
$authFields = [
    ['type' => 'hidden', 'name' => 'token', 'value' => $token],
    ['type' => 'password', 'id' => 'password', 'name' => 'password', 'label' => 'Nueva contraseña', 'required' => true],
    ['type' => 'password', 'id' => 'password2', 'name' => 'password2', 'label' => 'Repetir contraseña', 'required' => true],
];
$authShowForm = $reset || $_SERVER['REQUEST_METHOD'] === 'POST';
if ($authSuccessMessage !== '') {
    $authShowForm = false;
}
$authLinks = [];
if ($authSuccessMessage !== '') {
    $authLinks = [
        ['href' => 'login.php', 'label' => 'Volver al login'],
    ];
} elseif ($reset) {
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
