<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/catalog_permissions.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CollaboratorRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';

catalogRequireLogin();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$userId = (int)catalogGetUserId();
$navbarBackUrl = 'index.php';

$userRepo = new CatalogUserRepository();
$collabRepo = new CollaboratorRepository();
$catRepo = new CatalogRepository();

function generatePassword(int $length = 10): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

function isAdminUser(int $userId): bool
{
    $catRepo = new CatalogRepository();
    return count($catRepo->allByUser($userId)) > 0;
}

$isAdmin = isAdminUser($userId);
$error = '';
$success = '';
$generatedPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        $error = 'Solo los administradores pueden gestionar colaboradores.';
    } elseif (!empty($_POST['create_collaborator'])) {
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($email === '') {
            $error = 'El email es obligatorio.';
        } elseif ($name === '') {
            $error = 'El nombre es obligatorio.';
        } else {
            $existing = $userRepo->findByEmail($email);
            if ($existing) {
                if (isAdminUser((int)$existing['id'])) {
                    $error = 'Este usuario ya es un creador de catálogos (admin), no puede ser agregado como colaborador.';
                } else {
                    $success = 'El usuario <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong> ya existe. Puedes asignarle acceso a catálogos desde la lista.';
                }
            } else {
                $password = generatePassword();
                $userRepo->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'must_change_password' => 0,
                    'created_by' => $userId,
                ]);
                $generatedPassword = $password;
                $success = 'Usuario <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong> creado con contraseña <code>' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '</code>';
            }
        }
    } elseif (!empty($_POST['add_access'])) {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $catalogId = (int)($_POST['catalog_id'] ?? 0);
        if ($targetUserId <= 0 || $catalogId <= 0) {
            $error = 'Selecciona un usuario y un catálogo.';
        } elseif (isAdminUser($targetUserId)) {
            $error = 'Este usuario es un creador de catálogos (admin), no puede ser agregado como colaborador.';
        } else {
            $existing = $collabRepo->findByUserAndCatalog($targetUserId, $catalogId);
            if ($existing) {
                $error = 'Este usuario ya tiene acceso a ese catálogo.';
            } else {
                $perms = [
                    PERM_EDIT_CATALOG => !empty($_POST['perm_edit_catalog']),
                    PERM_EDIT_PAGES => !empty($_POST['perm_edit_pages']),
                    PERM_EDIT_PRODUCTS => !empty($_POST['perm_edit_products']),
                    PERM_EDIT_CATEGORIES => !empty($_POST['perm_edit_categories']),
                ];
                $collabRepo->create($catalogId, $targetUserId, $perms);
                $success = 'Acceso agregado.';
            }
        }
    } elseif (!empty($_POST['remove_access'])) {
        $collabId = (int)($_POST['collab_id'] ?? 0);
        $collabRepo->delete($collabId);
        $success = 'Acceso eliminado.';
    } elseif (!empty($_POST['update_perms'])) {
        $collabId = (int)($_POST['collab_id'] ?? 0);
        $perms = [
            PERM_EDIT_CATALOG => !empty($_POST['perm_edit_catalog']),
            PERM_EDIT_PAGES => !empty($_POST['perm_edit_pages']),
            PERM_EDIT_PRODUCTS => !empty($_POST['perm_edit_products']),
            PERM_EDIT_CATEGORIES => !empty($_POST['perm_edit_categories']),
        ];
        $collabRepo->updatePermissions($collabId, $perms);
        $success = 'Permisos actualizados.';
    } elseif (!empty($_POST['reset_password'])) {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        if (isAdminUser($targetUserId)) {
            $error = 'No puedes cambiar la contraseña de un administrador.';
        } else {
            $newPassword = generatePassword();
            $userRepo->updatePassword($targetUserId, password_hash($newPassword, PASSWORD_DEFAULT));
            $success = 'Contraseña generada para <strong>' . htmlspecialchars($userRepo->findById($targetUserId)['email'] ?? '', ENT_QUOTES, 'UTF-8') . '</strong>: <code>' . htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8') . '</code>';
        }
    }
}

$collaborators = $userRepo->findByCreatedBy($userId);
$myCatalogs = $catRepo->allByUser($userId);

// Collect all distinct collaborator users across my catalogs
$collaboratorUserIds = [];
foreach ($myCatalogs as $cat) {
    $cid = (int)$cat['id'];
    $entries = $collabRepo->findByCatalog($cid);
    foreach ($entries as $e) {
        $collaboratorUserIds[(int)$e['user_id']] = true;
    }
}

// Show users I created + users that are collaborators on my catalogs
$createdByMe = $userRepo->findByCreatedBy($userId);
$collabUsers = [];
if (!empty($collaboratorUserIds)) {
    $collabUsers = $userRepo->findByIds(array_keys($collaboratorUserIds));
}

// Merge: prefer users I created (they have more info), add others
$merged = [];
foreach ($createdByMe as $u) {
    $merged[(int)$u['id']] = $u;
}
foreach ($collabUsers as $u) {
    $uid = (int)$u['id'];
    if (!isset($merged[$uid])) {
        $merged[$uid] = $u;
    }
}
$collaborators = array_values($merged);

// Load access data for each collaborator
$collabAccess = [];
foreach ($collaborators as $u) {
    $uid = (int)$u['id'];
    $collabAccess[$uid] = $collabRepo->findCatalogsByUser($uid);
}
?>
<?php $pageTitle = 'Colaboradores'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<?php require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<div class="max-w-5xl mx-auto p-margin-mobile md:p-margin-desktop">

<div class="glass-panel rounded-3xl p-md md:p-xl mb-xl">
<h1 class="font-title-sm text-title-sm text-on-surface mb-md flex items-center gap-2">
    <span class="material-symbols-outlined text-primary text-[18px]">group</span>
    Colaboradores
</h1>

<?php if ($error): ?>
<div class="bg-error-container/20 border border-error/30 rounded-xl px-md py-sm mb-md">
<p class="font-body-sm text-body-sm text-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="bg-tertiary/20 border border-tertiary/30 rounded-xl px-md py-sm mb-md"><p class="font-body-sm text-body-sm text-tertiary"><?= $success ?></p></div>
<?php endif; ?>

<?php if (count($collaborators) === 0): ?>
<p class="font-body-sm text-body-sm text-on-surface-variant/60 mb-md">Aún no hay colaboradores.</p>
<?php else: ?>
<div class="flex flex-col gap-sm mb-xl">
<?php foreach ($collaborators as $u):
    $uid = (int)$u['id'];
    $access = $collabAccess[$uid] ?? [];
?>
<div class="flex flex-col p-sm rounded-xl bg-surface-variant/10 border border-outline-variant/20">
    <div class="flex items-center justify-between flex-wrap gap-sm">
        <div class="flex items-center gap-2 min-w-0">
            <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px]">person</span>
            <div class="min-w-0">
                <span class="font-body-sm text-body-sm text-on-surface font-semibold truncate block"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="font-body-xs text-body-xs text-on-surface-variant/60"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <span class="font-body-xs text-body-xs text-on-surface-variant/60"><?= count($access) ?> catálogo(s)</span>
            <button onclick="toggleAccess(<?= $uid ?>)" class="px-sm py-0.5 rounded-full border border-outline-variant font-label-caps text-label-caps text-on-surface-variant hover:text-primary hover:border-primary/30 transition-all">Gestionar acceso</button>
        </div>
    </div>

    <div id="access-<?= $uid ?>" class="hidden mt-sm pt-sm border-t border-outline-variant/20">
        <?php if (count($access) > 0): ?>
        <div class="flex flex-col gap-1 mb-sm">
        <?php foreach ($access as $a): ?>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-1 p-1.5 rounded-lg bg-surface-variant/20">
            <span class="font-body-xs text-body-xs text-on-surface truncate"><?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?></span>
            <form method="POST" class="flex flex-wrap items-center gap-x-sm gap-y-0.5">
                <input type="hidden" name="collab_id" value="<?= (int)($a['collab_id'] ?? $a['id']) ?>">
                <?php $perms = is_string($a['permissions']) ? json_decode($a['permissions'], true) : ($a['permissions'] ?? []); ?>
                <label class="flex items-center gap-0.5 cursor-pointer text-[0.65rem]">
                    <input type="checkbox" name="perm_edit_catalog" value="1" <?= !empty($perms[PERM_EDIT_CATALOG]) ? 'checked' : '' ?> class="accent-primary w-3 h-3">
                    <span class="text-on-surface-variant/70">Cat</span>
                </label>
                <label class="flex items-center gap-0.5 cursor-pointer text-[0.65rem]">
                    <input type="checkbox" name="perm_edit_pages" value="1" <?= !empty($perms[PERM_EDIT_PAGES]) ? 'checked' : '' ?> class="accent-primary w-3 h-3">
                    <span class="text-on-surface-variant/70">Pag</span>
                </label>
                <label class="flex items-center gap-0.5 cursor-pointer text-[0.65rem]">
                    <input type="checkbox" name="perm_edit_products" value="1" <?= !empty($perms[PERM_EDIT_PRODUCTS]) ? 'checked' : '' ?> class="accent-primary w-3 h-3">
                    <span class="text-on-surface-variant/70">Prod</span>
                </label>
                <label class="flex items-center gap-0.5 cursor-pointer text-[0.65rem]">
                    <input type="checkbox" name="perm_edit_categories" value="1" <?= !empty($perms[PERM_EDIT_CATEGORIES]) ? 'checked' : '' ?> class="accent-primary w-3 h-3">
                    <span class="text-on-surface-variant/70">Catg</span>
                </label>
                <button type="submit" name="update_perms" value="1" class="px-1.5 py-0.5 rounded-full border border-outline-variant font-label-caps text-[0.6rem] text-on-surface-variant hover:text-primary hover:border-primary/30 transition-all">Act</button>
                <button type="submit" name="remove_access" value="1" class="px-1.5 py-0.5 rounded-full border border-outline-variant font-label-caps text-[0.6rem] text-on-surface-variant hover:text-error hover:border-error/30 transition-all" onclick="return confirm('¿Quitar acceso a este catálogo?')">Quitar</button>
            </form>
        </div>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="font-body-xs text-body-xs text-on-surface-variant/40 mb-sm">Sin acceso a ningún catálogo.</p>
        <?php endif; ?>

        <form method="POST" class="flex flex-col md:flex-row items-start md:items-center gap-sm p-2 rounded-lg bg-surface-variant/20">
            <input type="hidden" name="target_user_id" value="<?= $uid ?>">
            <select name="catalog_id" class="form-input text-[0.75rem] py-1 px-2">
                <option value="">Seleccionar catálogo</option>
                <?php foreach ($myCatalogs as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex flex-wrap items-center gap-x-sm gap-y-0.5">
                <label class="flex items-center gap-0.5 cursor-pointer text-[0.65rem]">
                    <input type="checkbox" name="perm_edit_catalog" value="1" checked class="accent-primary w-3 h-3">
                    <span class="text-on-surface-variant/70">Cat</span>
                </label>
                <label class="flex items-center gap-0.5 cursor-pointer text-[0.65rem]">
                    <input type="checkbox" name="perm_edit_pages" value="1" checked class="accent-primary w-3 h-3">
                    <span class="text-on-surface-variant/70">Pag</span>
                </label>
                <label class="flex items-center gap-0.5 cursor-pointer text-[0.65rem]">
                    <input type="checkbox" name="perm_edit_products" value="1" checked class="accent-primary w-3 h-3">
                    <span class="text-on-surface-variant/70">Prod</span>
                </label>
                <label class="flex items-center gap-0.5 cursor-pointer text-[0.65rem]">
                    <input type="checkbox" name="perm_edit_categories" value="1" checked class="accent-primary w-3 h-3">
                    <span class="text-on-surface-variant/70">Catg</span>
                </label>
                <button type="submit" name="add_access" value="1" class="px-sm py-0.5 rounded-full border border-outline-variant font-label-caps text-[0.65rem] text-on-surface-variant hover:text-primary hover:border-primary/30 transition-all">Agregar acceso</button>
            </div>
        </form>
        <div class="mt-sm pt-sm border-t border-outline-variant/10 flex items-center gap-sm">
            <span class="font-body-xs text-body-xs text-on-surface-variant/60">Contraseña:</span>
            <form method="POST">
                <input type="hidden" name="target_user_id" value="<?= $uid ?>">
                <button type="submit" name="reset_password" value="1" class="px-sm py-0.5 rounded-full border border-outline-variant font-label-caps text-[0.65rem] text-on-surface-variant hover:text-warning hover:border-warning/30 transition-all">Generar nueva</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="border-t border-outline-variant/20 pt-md">
<h3 class="font-label-caps text-label-caps text-on-surface-variant mb-sm">Nuevo colaborador</h3>
<form method="POST" class="flex flex-col md:flex-row gap-sm items-start md:items-end">
    <div class="flex-1 w-full">
        <label class="font-body-xs text-body-xs text-on-surface-variant/60 block mb-0.5">Email</label>
        <input type="email" name="email" required class="form-input" placeholder="correo@ejemplo.com">
    </div>
    <div class="flex-1 w-full">
        <label class="font-body-xs text-body-xs text-on-surface-variant/60 block mb-0.5">Nombre</label>
        <input type="text" name="name" required class="form-input" placeholder="Nombre completo">
    </div>
    <button type="submit" name="create_collaborator" value="1" class="primary-gradient text-white px-lg py-sm rounded-full font-label-caps text-label-caps active:scale-95 transition-transform primary-glow whitespace-nowrap">Crear colaborador</button>
</form>
</div>
<?php endif; ?>
</div>

</div>
</main>
<script>
function toggleAccess(id) {
    var el = document.getElementById('access-' + id);
    el.classList.toggle('hidden');
}
</script>
</body>
</html>
