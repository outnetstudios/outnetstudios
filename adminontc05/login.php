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
                header('Location: dashboard.php');
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
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
<?php
$authPanelClass = 'login-panel';
$authTitle = 'Admin Outnet Studios';
$authSubtitle = 'Accede al panel administrativo para revisar prospectos y gestionar datos.';
$authErrorMessage = $error_message;
$authFormAction = '';
$authFormMethod = 'post';
$authButtonText = 'Ingresar';
$authFields = [
    ['type' => 'text', 'id' => 'username', 'name' => 'username', 'label' => 'Usuario', 'required' => true, 'attributes' => 'autocomplete="off"'],
    ['type' => 'password', 'id' => 'password', 'name' => 'password', 'label' => 'Contraseña', 'required' => true, 'attributes' => 'autocomplete="current-password"'],
];
$authLinks = [
    ['href' => 'forgot_password.php', 'label' => '¿Olvidaste tu contraseña?'],
];
include __DIR__ . '/../templates/partials/auth_form.php';
?>
</body>
</html>
