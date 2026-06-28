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

<?php if ($error): ?>
<div class="bg-error-container/20 border border-error/30 rounded-xl px-md py-sm mb-md flex items-center gap-2">
<span class="material-symbols-outlined text-error text-[18px]">error</span>
<p class="font-body-sm text-body-sm text-error flex-1"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="bg-tertiary/20 border border-tertiary/30 rounded-xl px-md py-sm mb-md flex items-center gap-2">
<span class="material-symbols-outlined text-tertiary text-[18px]">check_circle</span>
<p class="font-body-sm text-body-sm text-tertiary flex-1"><?= $success ?></p>
</div>
<?php endif; ?>

<!-- Header -->
<div class="glass-panel rounded-3xl p-md md:p-xl mb-xl">
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-md mb-lg">
<div class="flex items-center gap-3">
    <div class="w-12 h-12 rounded-2xl bg-primary/15 flex items-center justify-center">
        <span class="material-symbols-outlined text-primary text-[24px]">group</span>
    </div>
    <div>
        <h1 class="font-title-sm text-title-sm text-on-surface">Colaboradores</h1>
        <p class="font-body-xs text-body-xs text-on-surface-variant/60">Gestiona el acceso a tus catálogos</p>
    </div>
</div>
<div class="flex items-center gap-2">
    <div class="px-sm py-xs rounded-xl bg-surface-variant/20 border border-outline-variant/20 flex items-center gap-1">
        <span class="material-symbols-outlined text-primary text-[14px]">group</span>
        <span class="font-label-caps text-label-caps text-on-surface-variant"><?= count($collaborators) ?> colaboradores</span>
    </div>
    <div class="px-sm py-xs rounded-xl bg-surface-variant/20 border border-outline-variant/20 flex items-center gap-1">
        <span class="material-symbols-outlined text-primary text-[14px]">folder</span>
        <span class="font-label-caps text-label-caps text-on-surface-variant"><?= count($myCatalogs) ?> catálogos</span>
    </div>
</div>
</div>

<?php if (count($collaborators) === 0): ?>
<div class="flex flex-col items-center justify-center py-xl text-center">
<div class="w-16 h-16 rounded-full bg-surface-variant/20 flex items-center justify-center mb-md">
    <span class="material-symbols-outlined text-on-surface-variant/30 text-[32px]">group</span>
</div>
<p class="font-title-sm text-title-sm text-on-surface mb-xs">Aún no hay colaboradores</p>
<p class="font-body-sm text-body-sm text-on-surface-variant/60 mb-lg">Invita a otras personas a gestionar tus catálogos</p>
<?php if ($isAdmin): ?>
<a href="#" onclick="document.getElementById('new-collab-form').scrollIntoView({behavior:'smooth'}); return false;" class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm inline-flex active:scale-95 transition-transform primary-glow no-underline items-center gap-2">
    <span class="material-symbols-outlined text-[18px]">person_add</span> Invitar colaborador
</a>
<?php endif; ?>
</div>
<?php else: ?>
<div class="flex flex-col gap-md mb-xl">
<?php foreach ($collaborators as $u):
    $uid = (int)$u['id'];
    $access = $collabAccess[$uid] ?? [];
    $catalogoCount = count($access);
?>
<div class="rounded-2xl bg-surface-variant/10 border border-outline-variant/20 overflow-hidden transition-all duration-200 hover:border-primary/20 hover:bg-surface-variant/15">
    <div class="p-md flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-full bg-primary/15 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-primary text-[20px]">person</span>
            </div>
            <div class="min-w-0">
                <span class="font-title-sm text-title-sm text-on-surface font-semibold truncate block"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="font-body-xs text-body-xs text-on-surface-variant/60"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <span class="font-body-xs text-body-xs <?= $catalogoCount > 0 ? 'text-primary' : 'text-on-surface-variant/40' ?> bg-primary/10 px-sm py-0.5 rounded-full border border-primary/20"><?= $catalogoCount ?> catálogo(s)</span>
            <button onclick="toggleAccess(<?= $uid ?>)" id="toggle-btn-<?= $uid ?>" class="px-md py-1.5 rounded-full font-label-caps text-label-caps border border-outline-variant/30 text-on-surface-variant/70 hover:text-primary hover:border-primary/30 hover:bg-primary/10 transition-all flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">settings</span>
                Gestionar
            </button>
        </div>
    </div>

    <div id="access-<?= $uid ?>" class="hidden border-t border-outline-variant/10">
        <div class="p-md space-y-md">
            <?php if ($catalogoCount > 0): ?>
            <div class="flex flex-col gap-2">
            <?php foreach ($access as $a): ?>
            <?php $perms = is_string($a['permissions']) ? json_decode($a['permissions'], true) : ($a['permissions'] ?? []); ?>
            <div class="rounded-xl bg-surface-variant/20 border border-outline-variant/20 p-md">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-md">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-[16px]">folder</span>
                        <span class="font-body-sm text-body-sm text-on-surface font-semibold truncate"><?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <form method="POST" class="flex flex-wrap items-center gap-x-md gap-y-1">
                        <input type="hidden" name="collab_id" value="<?= (int)($a['collab_id'] ?? $a['id']) ?>">
                        <label class="flex items-center gap-1.5 cursor-pointer group">
                            <span class="font-body-xs text-body-xs text-on-surface-variant/60 w-[2rem]">Cat</span>
                            <input type="checkbox" name="perm_edit_catalog" value="1" <?= !empty($perms[PERM_EDIT_CATALOG]) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-9 h-5 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer group">
                            <span class="font-body-xs text-body-xs text-on-surface-variant/60 w-[2rem]">Pag</span>
                            <input type="checkbox" name="perm_edit_pages" value="1" <?= !empty($perms[PERM_EDIT_PAGES]) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-9 h-5 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer group">
                            <span class="font-body-xs text-body-xs text-on-surface-variant/60 w-[2rem]">Prod</span>
                            <input type="checkbox" name="perm_edit_products" value="1" <?= !empty($perms[PERM_EDIT_PRODUCTS]) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-9 h-5 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer group">
                            <span class="font-body-xs text-body-xs text-on-surface-variant/60 w-[2rem]">Catg</span>
                            <input type="checkbox" name="perm_edit_categories" value="1" <?= !empty($perms[PERM_EDIT_CATEGORIES]) ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-9 h-5 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
                        </label>
                        <div class="flex items-center gap-1">
                            <button type="submit" name="update_perms" value="1" title="Guardar permisos" class="p-2 rounded-lg hover:bg-primary/15 text-primary transition-all"><span class="material-symbols-outlined text-[16px]">check</span></button>
                            <button type="submit" name="remove_access" value="1" onclick="return confirm('¿Quitar acceso a este catálogo?')" title="Quitar acceso" class="p-2 rounded-lg hover:bg-error/15 text-error/70 transition-all"><span class="material-symbols-outlined text-[16px]">close</span></button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flex items-center gap-2 p-md rounded-xl bg-surface-variant/20 border border-dashed border-outline-variant/20">
                <span class="material-symbols-outlined text-on-surface-variant/30 text-[18px]">info</span>
                <p class="font-body-xs text-body-xs text-on-surface-variant/50">Sin acceso a ningún catálogo. Asigna uno abajo.</p>
            </div>
            <?php endif; ?>

            <div class="rounded-xl bg-surface-variant/20 border border-dashed border-outline-variant/20 p-md">
                <p class="font-label-caps text-label-caps text-on-surface-variant/60 mb-sm">Agregar acceso a catálogo</p>
                <form method="POST" class="flex flex-col md:flex-row items-start md:items-center gap-sm">
                    <input type="hidden" name="target_user_id" value="<?= $uid ?>">
                    <select name="catalog_id" class="form-input">
                        <option value="">Seleccionar catálogo</option>
                        <?php foreach ($myCatalogs as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="flex flex-wrap items-center gap-x-sm gap-y-1">
                        <label class="flex items-center gap-1 cursor-pointer group">
                            <span class="font-body-xs text-body-xs text-on-surface-variant/60">Cat</span>
                            <input type="checkbox" name="perm_edit_catalog" value="1" checked class="sr-only peer">
                            <div class="w-7 h-4 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:rounded-full after:h-3.5 after:w-3.5 after:transition-all relative"></div>
                        </label>
                        <label class="flex items-center gap-1 cursor-pointer group">
                            <span class="font-body-xs text-body-xs text-on-surface-variant/60">Pag</span>
                            <input type="checkbox" name="perm_edit_pages" value="1" checked class="sr-only peer">
                            <div class="w-7 h-4 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:rounded-full after:h-3.5 after:w-3.5 after:transition-all relative"></div>
                        </label>
                        <label class="flex items-center gap-1 cursor-pointer group">
                            <span class="font-body-xs text-body-xs text-on-surface-variant/60">Prod</span>
                            <input type="checkbox" name="perm_edit_products" value="1" checked class="sr-only peer">
                            <div class="w-7 h-4 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:rounded-full after:h-3.5 after:w-3.5 after:transition-all relative"></div>
                        </label>
                        <label class="flex items-center gap-1 cursor-pointer group">
                            <span class="font-body-xs text-body-xs text-on-surface-variant/60">Catg</span>
                            <input type="checkbox" name="perm_edit_categories" value="1" checked class="sr-only peer">
                            <div class="w-7 h-4 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:rounded-full after:h-3.5 after:w-3.5 after:transition-all relative"></div>
                        </label>
                        <button type="submit" name="add_access" value="1" class="px-md py-1.5 rounded-full bg-primary/15 text-primary font-label-caps text-label-caps hover:bg-primary/25 transition-all flex items-center gap-1 border border-primary/20">
                            <span class="material-symbols-outlined text-[14px]">add</span> Agregar
                        </button>
                    </div>
                </form>
            </div>

            <div class="flex items-center justify-between gap-md p-md rounded-xl bg-surface-variant/20 border border-outline-variant/20">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant/50 text-[16px]">key</span>
                    <span class="font-body-xs text-body-xs text-on-surface-variant/70">Contraseña</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="target_user_id" value="<?= $uid ?>">
                    <button type="submit" name="reset_password" value="1" class="px-md py-1.5 rounded-full font-label-caps text-label-caps border border-warning/30 text-warning/80 hover:bg-warning/10 hover:border-warning/50 transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">refresh</span> Generar nueva
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<div id="new-collab-form" class="glass-panel rounded-3xl p-md md:p-xl">
<div class="flex items-center gap-3 mb-lg">
    <div class="w-10 h-10 rounded-2xl bg-primary/15 flex items-center justify-center">
        <span class="material-symbols-outlined text-primary text-[20px]">person_add</span>
    </div>
    <div>
        <h2 class="font-title-sm text-title-sm text-on-surface">Nuevo colaborador</h2>
        <p class="font-body-xs text-body-xs text-on-surface-variant/60">Crea un usuario y asígnale acceso a tus catálogos</p>
    </div>
</div>
<form method="POST" class="flex flex-col md:flex-row gap-md items-start md:items-end">
    <div class="flex-1 w-full">
        <label class="font-body-xs text-body-xs text-on-surface-variant/60 block mb-1">Correo electrónico</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/40"><span class="material-symbols-outlined text-[16px]">mail</span></span>
            <input type="email" name="email" required class="form-input pl-10" placeholder="correo@ejemplo.com">
        </div>
    </div>
    <div class="flex-1 w-full">
        <label class="font-body-xs text-body-xs text-on-surface-variant/60 block mb-1">Nombre completo</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/40"><span class="material-symbols-outlined text-[16px]">person</span></span>
            <input type="text" name="name" required class="form-input pl-10" placeholder="Nombre completo">
        </div>
    </div>
    <button type="submit" name="create_collaborator" value="1" class="primary-gradient text-white px-lg py-sm rounded-full font-label-caps text-label-caps active:scale-95 transition-transform primary-glow whitespace-nowrap flex items-center gap-2 h-[48px]">
        <span class="material-symbols-outlined text-[18px]">person_add</span> Crear colaborador
    </button>
</form>
</div>
<?php endif; ?>

</div>
</main>
<script>
function toggleAccess(id) {
    var el = document.getElementById('access-' + id);
    var btn = document.getElementById('toggle-btn-' + id);
    var isHidden = el.classList.contains('hidden');
    el.classList.toggle('hidden');
    if (isHidden) {
        el.style.maxHeight = '0px';
        el.classList.remove('hidden');
        requestAnimationFrame(function() {
            el.style.maxHeight = el.scrollHeight + 'px';
            el.style.opacity = '1';
        });
    } else {
        el.style.maxHeight = '0px';
        el.style.opacity = '0';
        setTimeout(function() {
            el.classList.add('hidden');
        }, 300);
    }
}
</script>
</body>
</html>
