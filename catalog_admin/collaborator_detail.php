<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/catalog_permissions.php';
require_once __DIR__ . '/../includes/catalog_encryption.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CollaboratorRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';

catalogRequireLogin();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$userId = (int)catalogGetUserId();
$navbarBackUrl = 'collaborators.php';

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
if (!$isAdmin) {
    header('Location: index.php');
    exit;
}

$targetUserId = (int)($_GET['id'] ?? 0);
if ($targetUserId <= 0) {
    header('Location: collaborators.php');
    exit;
}

$targetUser = $userRepo->findById($targetUserId);
if (!$targetUser) {
    header('Location: collaborators.php');
    exit;
}

$createdByMe = $userRepo->findByCreatedBy($userId);
$accessible = false;
foreach ($createdByMe as $u) {
    if ((int)$u['id'] === $targetUserId) {
        $accessible = true;
        break;
    }
}
if (!$accessible) {
    $myCatalogs = $catRepo->allByUser($userId);
    foreach ($myCatalogs as $cat) {
        $entries = $collabRepo->findByCatalog((int)$cat['id']);
        foreach ($entries as $e) {
            if ((int)$e['user_id'] === $targetUserId) {
                $accessible = true;
                break 2;
            }
        }
    }
}
if (!$accessible) {
    header('Location: collaborators.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['add_access'])) {
        $catalogId = (int)($_POST['catalog_id'] ?? 0);
        if ($catalogId <= 0) {
            $error = 'Selecciona un catálogo.';
        } elseif (isAdminUser($targetUserId)) {
            $error = 'Este usuario es un administrador, no puede ser colaborador.';
        } else {
            $existing = $collabRepo->findByUserAndCatalog($targetUserId, $catalogId);
            if ($existing) {
                $error = 'Ya tiene acceso a ese catálogo.';
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
        if (isAdminUser($targetUserId)) {
            $error = 'No puedes cambiar la contraseña de un administrador.';
        } else {
            $newPassword = generatePassword();
            $userRepo->updatePasswordEncrypted($targetUserId, $newPassword);
            $targetUser = $userRepo->findById($targetUserId);
            $success = 'Contraseña generada.';
        }
    }
}

$access = $collabRepo->findCatalogsByUser($targetUserId);
$myCatalogs = $catRepo->allByUser($userId);

$pwdValue = null;
$hasPwd = !empty($targetUser['password_encrypted']);
if ($hasPwd) {
    try {
        $pwdValue = decryptPassword($targetUser['password_encrypted']);
    } catch (RuntimeException $e) {
        $pwdValue = null;
        $hasPwd = false;
    }
}
?>
<?php $pageTitle = 'Colaborador'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<?php require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
<div class="max-w-4xl mx-auto">

<?php if ($error): ?>
<div class="bg-error-container/20 border border-error/30 rounded-xl px-md py-sm mb-xl flex items-center gap-2">
<span class="material-symbols-outlined text-error text-[18px]">error</span>
<p class="font-body-sm text-body-sm text-error flex-1"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="bg-tertiary/20 border border-tertiary/30 rounded-xl px-md py-sm mb-xl flex items-center gap-2">
<span class="material-symbols-outlined text-tertiary text-[18px]">check_circle</span>
<p class="font-body-sm text-body-sm text-tertiary flex-1"><?= $success ?></p>
</div>
<?php endif; ?>

<div class="glass-panel rounded-2xl p-md mb-xl">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-primary/15 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-primary text-[20px]">person</span>
</div>
<div>
<h1 class="font-title-sm text-title-sm text-on-surface font-semibold"><?= htmlspecialchars($targetUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
<p class="font-body-sm text-body-sm text-on-surface-variant/60"><?= htmlspecialchars($targetUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
</div>
</div>
</div>

<div class="glass-panel rounded-2xl p-md mb-xl space-y-md">
<h2 class="font-title-sm text-title-sm text-primary flex items-center gap-xs">
<span class="material-symbols-outlined">folder</span> Acceso a catálogos
</h2>

<?php if (count($access) > 0): ?>
<div class="flex flex-col gap-2">
<?php foreach ($access as $a):
$perms = is_string($a['permissions']) ? json_decode($a['permissions'], true) : ($a['permissions'] ?? []);
?>
<div class="flex flex-col md:flex-row md:items-center justify-between gap-2 bg-surface-variant/20 rounded-xl p-3">
<div class="flex items-center gap-2 min-w-0">
<span class="material-symbols-outlined text-primary text-[16px] shrink-0">folder</span>
<span class="font-body-sm text-body-sm text-on-surface font-semibold truncate"><?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?></span>
</div>
<form method="POST" class="flex flex-wrap items-center gap-2">
<input type="hidden" name="collab_id" value="<?= (int)($a['collab_id'] ?? $a['id']) ?>">
<label class="flex items-center gap-1.5 cursor-pointer group">
<span class="font-body-xs text-body-xs text-on-surface-variant/60">Cat</span>
<input type="checkbox" name="perm_edit_catalog" value="1" <?= !empty($perms[PERM_EDIT_CATALOG]) ? 'checked' : '' ?> class="sr-only peer">
<div class="w-9 h-5 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
</label>
<label class="flex items-center gap-1.5 cursor-pointer group">
<span class="font-body-xs text-body-xs text-on-surface-variant/60">Pag</span>
<input type="checkbox" name="perm_edit_pages" value="1" <?= !empty($perms[PERM_EDIT_PAGES]) ? 'checked' : '' ?> class="sr-only peer">
<div class="w-9 h-5 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
</label>
<label class="flex items-center gap-1.5 cursor-pointer group">
<span class="font-body-xs text-body-xs text-on-surface-variant/60">Prod</span>
<input type="checkbox" name="perm_edit_products" value="1" <?= !empty($perms[PERM_EDIT_PRODUCTS]) ? 'checked' : '' ?> class="sr-only peer">
<div class="w-9 h-5 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
</label>
<label class="flex items-center gap-1.5 cursor-pointer group">
<span class="font-body-xs text-body-xs text-on-surface-variant/60">Catg</span>
<input type="checkbox" name="perm_edit_categories" value="1" <?= !empty($perms[PERM_EDIT_CATEGORIES]) ? 'checked' : '' ?> class="sr-only peer">
<div class="w-9 h-5 bg-surface-variant/40 rounded-full peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
</label>
<div class="flex items-center gap-1">
<button type="submit" name="update_perms" value="1" title="Guardar" class="p-1.5 rounded-lg hover:bg-primary/15 text-primary transition-all"><span class="material-symbols-outlined text-[16px]">check</span></button>
<button type="submit" name="remove_access" value="1" onclick="return confirm('¿Quitar acceso a este catálogo?')" title="Quitar" class="p-1.5 rounded-lg hover:bg-error/15 text-error/70 transition-all"><span class="material-symbols-outlined text-[16px]">close</span></button>
</div>
</form>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="flex items-center gap-2 bg-surface-variant/20 rounded-xl p-3">
<span class="material-symbols-outlined text-on-surface-variant/30 text-[16px]">info</span>
<p class="font-body-xs text-body-xs text-on-surface-variant/50">Sin acceso a ningún catálogo.</p>
</div>
<?php endif; ?>

<div class="bg-surface-variant/20 rounded-xl p-3">
<p class="font-label-caps text-label-caps text-on-surface-variant/60 mb-2">Agregar acceso</p>
<form method="POST" class="flex flex-col md:flex-row items-start md:items-center gap-2">
<select name="catalog_id" class="form-input py-1 px-2 text-[0.75rem] w-full md:w-auto">
<option value="">Seleccionar catálogo</option>
<?php foreach ($myCatalogs as $cat): ?>
<option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
</select>
<div class="flex flex-wrap items-center gap-2">
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
<button type="submit" name="add_access" value="1" class="px-3 py-1 rounded-full bg-primary/15 text-primary font-label-caps text-[0.65rem] hover:bg-primary/25 transition-all flex items-center gap-1 border border-primary/20 whitespace-nowrap">
<span class="material-symbols-outlined text-[12px]">add</span> Agregar
</button>
</div>
</form>
</div>
</div>

<div class="glass-panel rounded-2xl p-md mb-xl">
<div class="flex items-center justify-between gap-2">
<h2 class="font-title-sm text-title-sm text-primary flex items-center gap-xs">
<span class="material-symbols-outlined">key</span> Contraseña
</h2>
<form method="POST" class="shrink-0">
<button type="submit" name="reset_password" value="1" class="px-3 py-1 rounded-full bg-warning/15 text-warning font-label-caps text-[0.65rem] hover:bg-warning/25 transition-all flex items-center gap-1 border border-warning/20 whitespace-nowrap">
<span class="material-symbols-outlined text-[12px]">key</span> Generar
</button>
</form>
</div>
<?php if ($hasPwd): ?>
<div class="flex items-center gap-2 mt-3">
<div class="relative min-w-0" style="flex:0 1 80%">
<input type="password" id="pwd-field-<?= $targetUserId ?>" value="<?= htmlspecialchars($pwdValue ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly class="form-input py-1.5 px-2 text-[0.8rem] w-full font-mono pr-8">
<button type="button" onclick="togglePwdVisibility(<?= $targetUserId ?>)" class="absolute right-1 top-1/2 -translate-y-1/2 p-1 text-on-surface-variant/60 hover:text-on-surface transition-all" title="Mostrar/ocultar">
<span class="material-symbols-outlined text-[16px]" id="pwd-eye-<?= $targetUserId ?>">visibility</span>
</button>
</div>
<button type="button" onclick="copyPwd(<?= $targetUserId ?>)" class="px-2 py-1.5 rounded-lg hover:bg-primary/15 text-primary transition-all flex items-center justify-center shrink-0" title="Copiar">
<span class="material-symbols-outlined text-[18px]">content_copy</span>
</button>
</div>
<?php endif; ?>
</div>

</div>
</section>
</main>
<script>
function togglePwdVisibility(id) {
  var field = document.getElementById('pwd-field-' + id);
  var eye = document.getElementById('pwd-eye-' + id);
  if (!field || !eye) return;
  var isPassword = field.type === 'password';
  field.type = isPassword ? 'text' : 'password';
  eye.textContent = isPassword ? 'visibility_off' : 'visibility';
}
function copyPwd(id) {
  var field = document.getElementById('pwd-field-' + id);
  if (!field) return;
  navigator.clipboard.writeText(field.value).then(function() {
    var btn = field.parentElement.nextElementSibling;
    if (btn) {
      var icon = btn.querySelector('.material-symbols-outlined');
      if (icon) {
        var orig = icon.textContent;
        icon.textContent = 'check';
        setTimeout(function() { icon.textContent = orig; }, 1500);
      }
    }
  });
}
</script>
</html>
