<?php
require_once __DIR__ . '/_session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';
require_once __DIR__ . '/../includes/plan_helpers.php';

$pageTitle = 'Usuarios catálogo';
$pageSubtitle = 'Crea usuarios con contraseña temporal para el nuevo proyecto.';

$flashMessage = '';
$flashPassword = '';
$errorMessage = '';
$users = [];

function generateTemporaryPassword(int $length = 12): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    $maxIndex = strlen($alphabet) - 1;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $maxIndex)];
    }
    return $password;
}

try {
    $catalogUserRepository = new CatalogUserRepository();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tempPasswordInput = trim($_POST['temporary_password'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $expiresAtInput = trim($_POST['temp_password_expires_at'] ?? '');

        if ($name === '' || $email === '') {
            $errorMessage = 'Completa nombre y correo para crear el usuario del catálogo.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'El correo no tiene un formato válido.';
        } else {
            $temporaryPassword = $tempPasswordInput !== '' ? $tempPasswordInput : generateTemporaryPassword();
            $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

            $createdAt = null;
            if ($expiresAtInput !== '') {
                $createdAt = date('Y-m-d H:i:s', strtotime($expiresAtInput));
            }

            $catalogUserRepository->create([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'must_change_password' => 1,
                'temp_password_expires_at' => $createdAt,
                'status' => $status,
                'created_by' => null,
            ]);

            $flashMessage = 'Usuario del catálogo creado correctamente.';
            $flashPassword = $temporaryPassword;
        }
    }

    $users = $catalogUserRepository->all();
} catch (PDOException $e) {
    error_log('Catalog user admin failed: ' . $e->getMessage());
    $errorMessage = 'Aún falta crear las tablas SQL del catálogo para poder listar y guardar usuarios.';
} catch (Throwable $e) {
    error_log('Catalog user admin unexpected error: ' . $e->getMessage());
    $errorMessage = 'Ocurrió un error inesperado al preparar el módulo de usuarios del catálogo.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios catálogo</title>
    <link rel="stylesheet" href="../css/auth-admin-theme.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <style>
        .pw-row { display: flex; gap: 0.5rem; align-items: stretch; }
        .pw-row input { flex: 1; min-width: 0; }
        .pw-row .btn-gen { flex-shrink: 0; padding: 0.9rem 1rem; border-radius: 20px; border: 1px solid rgba(245,162,0,0.3); background: rgba(245,162,0,0.12); color: #ffd966; cursor: pointer; font-weight: 600; white-space: nowrap; transition: background 0.2s; }
        .pw-row .btn-gen:hover { background: rgba(245,162,0,0.22); }
    </style>
</head>
<body class="dashboard-shell">
    <div class="dashboard-container">
        <?php require __DIR__ . '/navbar.php'; ?>

        <div class="module-grid">
            <?php if ($errorMessage !== ''): ?>
                <div class="module-note"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($flashMessage !== ''): ?>
                <div class="module-note">
                    <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($flashPassword !== ''): ?>
                        <div class="flash-password">
                            <strong>Contraseña temporal:</strong>
                            <span><?php echo htmlspecialchars($flashPassword, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="module-panel">
                <h2 style="margin-top:0;">Alta manual de usuario</h2>
                <form class="module-form" method="post" action="">
                    <div class="grid-two">
                        <div>
                            <label for="name">Nombre</label>
                            <input type="text" id="name" name="name" required placeholder="Nombre del usuario">
                        </div>
                        <div>
                            <label for="email">Correo</label>
                            <input type="email" id="email" name="email" required placeholder="usuario@empresa.com">
                        </div>
                    </div>

                    <div class="grid-two">
                        <div>
                            <label for="temporary_password">Contraseña temporal</label>
                            <div class="pw-row">
                                <input type="text" id="temporary_password" name="temporary_password" placeholder="Generar automáticamente">
                                <button type="button" class="btn-gen" id="btnGeneratePw">Generar</button>
                            </div>
                        </div>
                        <div>
                            <label for="temp_password_expires_at">Expira la contraseña (72h)</label>
                            <input type="datetime-local" id="temp_password_expires_at" name="temp_password_expires_at">
                        </div>
                    </div>

                    <div class="grid-two">
                        <div>
                            <label for="status">Estado</label>
                            <select id="status" name="status">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button class="button-primary" type="submit">Crear usuario catálogo</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="module-panel">
                <h2 style="margin-top:0;">Usuarios creados</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Cambio obligatorio</th>
                                <th>Expira temporal</th>
                                <th>Creado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; color: rgba(255,255,255,0.7);">Aún no hay usuarios del catálogo.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($user['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo !empty($user['must_change_password']) ? 'Sí' : 'No'; ?></td>
                                        <td><?php echo htmlspecialchars($user['temp_password_expires_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($user['created_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>
    </div>

<script>
function pad(n) { return n.toString().padStart(2, '0'); }
function toDatetimeLocal(date) {
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
}
(function() {
    var expiresField = document.getElementById('temp_password_expires_at');
    if (expiresField && !expiresField.value) {
        var future = new Date(Date.now() + 72 * 60 * 60 * 1000);
        expiresField.value = toDatetimeLocal(future);
    }
    document.getElementById('btnGeneratePw').addEventListener('click', function() {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        var pw = '';
        for (var i = 0; i < 14; i++) {
            pw += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('temporary_password').value = pw;
    });
})();
</script>
</body>
</html>
