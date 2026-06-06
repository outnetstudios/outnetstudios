<?php
session_start();
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/PasswordResetRepository.php';
require_once __DIR__ . '/../src/Services/SmtpMailer.php';
require_once __DIR__ . '/../src/Services/MailService.php';

use App\Services\MailService;

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error_message = 'Por favor ingresa tu correo electrónico.';
    } else {
        $userRepository = new UserRepository();
        $user = $userRepository->findByEmail($email);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $resetRepository = new PasswordResetRepository();
            $resetRepository->create((int)$user['id'], $token, $expiresAt);

            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $resetLink = $baseUrl . '/auth/reset_password.php?token=' . urlencode($token);

            $mailService = new MailService();
            $mailService->sendPasswordReset($user['email'], $resetLink);
        }

        $success_message = 'Si el correo está registrado, hemos enviado un enlace para restablecer la contraseña.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
</head>
<body class="auth-shell">
<?php
$authPanelClass = 'panel';
$authTitle = 'Recuperar contraseña';
$authSubtitle = '';
$authErrorMessage = $error_message;
$authSuccessMessage = $success_message;
$authFormAction = '';
$authFormMethod = 'post';
$authButtonText = 'Enviar enlace de recuperación';
$authFields = [
    ['type' => 'text', 'id' => 'email', 'name' => 'email', 'label' => 'Correo electrónico', 'placeholder' => 'tu@correo.com', 'required' => true],
];
$authLinks = [
    ['href' => 'login.php', 'label' => 'Volver al login'],
];
include __DIR__ . '/../templates/partials/auth_form.php';
?>
</body>
</html>
