<?php
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';
require_once __DIR__ . '/../src/Services/MailService.php';
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';

use App\Services\MailService;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $message = 'Ingresa un email válido.';
    } else {
        $repo = new CatalogUserRepository();
        $user = $repo->findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(24));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $repo->createPasswordReset((int)$user['id'], $token, $expires);

            $mailService = new MailService();
            $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/catalog_auth/reset_password.php?token=' . $token;
            try {
                $mailService->sendPasswordReset($email, $resetLink);
            } catch (Exception $e) {
                error_log('Send reset failed: ' . $e->getMessage());
            }
        }

        $message = 'Si el email existe, te hemos enviado instrucciones para restablecer la contraseña.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Olvidé mi contraseña</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
<?php
$authPanelClass = 'login-panel';
$authTitle = 'Recuperar contraseña';
$authSubtitle = '';
$authErrorMessage = $message;
$authFormAction = '';
$authFormMethod = 'post';
$authButtonText = 'Enviar';
$authFields = [
    ['type' => 'email', 'id' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
];
$authLinks = [
    ['href' => 'login.php', 'label' => 'Volver al login'],
];
include __DIR__ . '/../templates/partials/auth_form.php';
?>
</body>
</html>
