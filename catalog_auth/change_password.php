<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';

catalogSessionStart();
if (empty($_SESSION['catalog_user_id'])) {
    header('Location: login.php');
    exit;
}

$repo = new CatalogUserRepository();
$user = $repo->findById((int)$_SESSION['catalog_user_id']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');
    if ($password === '' || $password !== $password2) {
        $error = 'Las contraseñas no coinciden o están vacías.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($repo->updatePassword((int)$user['id'], $hash)) {
            catalogLogin((int)$user['id'], $user['email'], $user['name']);
            header('Location: ../catalog_admin/index.php');
            exit;
        }
        $error = 'No se pudo actualizar la contraseña.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cambiar contraseña</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
<?php
$authPanelClass = 'login-panel';
$authTitle = 'Cambiar contraseña';
$authSubtitle = '';
$authErrorMessage = $error;
$authFormAction = '';
$authFormMethod = 'post';
$authButtonText = 'Actualizar';
$authFields = [
    ['type' => 'password', 'id' => 'password', 'name' => 'password', 'label' => 'Nueva contraseña', 'required' => true],
    ['type' => 'password', 'id' => 'password2', 'name' => 'password2', 'label' => 'Repetir contraseña', 'required' => true],
];
$authLinks = [];
include __DIR__ . '/../templates/partials/auth_form.php';
?>
</body>
</html>
