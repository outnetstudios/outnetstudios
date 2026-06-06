<?php
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';

catalogSessionStart();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Completa email y contraseña.';
    } else {
        $repo = new CatalogUserRepository();
        $user = $repo->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            catalogLogin((int)$user['id'], $user['email'], $user['name']);
            if (!empty($user['must_change_password'])) {
                header('Location: change_password.php');
                exit;
            }
            header('Location: ../catalog_admin/index.php');
            exit;
        }

        $error = 'Email o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login Catálogo</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
<?php
$authPanelClass = 'login-panel';
$authTitle = 'Acceso Catálogo';
$authSubtitle = 'Ingresa con tu correo y contraseña de catálogo para administrar productos y usuarios.';
$authErrorMessage = $error;
$authFormAction = '';
$authFormMethod = 'post';
$authButtonText = 'Entrar';
$authFields = [
    ['type' => 'email', 'id' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
    ['type' => 'password', 'id' => 'password', 'name' => 'password', 'label' => 'Contraseña', 'required' => true],
];
$authLinks = [
    ['href' => 'forgot_password.php', 'label' => '¿Olvidaste tu contraseña?'],
];
include __DIR__ . '/../templates/partials/auth_form.php';
?>
</body>
</html>
