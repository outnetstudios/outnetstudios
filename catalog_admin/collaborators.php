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
<section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
<div class="max-w-7xl mx-auto">

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

<!-- Header -->
<header class="mb-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
<div>
<h1 class="font-display-lg-mobile text-display-lg-mobile text-on-surface">Colaboradores</h1>
</div>
<div class="flex items-center gap-xs">
<div class="glass-panel px-sm py-xs rounded-xl flex items-center gap-xs">
<span class="material-symbols-outlined text-[18px] text-primary">group</span>
<div class="flex items-center gap-1">
<span class="text-label-caps font-label-caps text-on-surface-variant">COLABORADORES</span>
<span class="text-title-sm font-title-sm text-on-surface"><?= count($collaborators) ?></span>
</div>
</div>
<div class="glass-panel px-sm py-xs rounded-xl flex items-center gap-xs">
<span class="material-symbols-outlined text-[18px] text-primary">folder</span>
<div class="flex items-center gap-1">
<span class="text-label-caps font-label-caps text-on-surface-variant">CATÁLOGOS</span>
<span class="text-title-sm font-title-sm text-on-surface"><?= count($myCatalogs) ?></span>
</div>
</div>
</div>
</header>

<?php if ($isAdmin): ?>
<!-- Nuevo colaborador -->
<div id="new-collab-form" class="glass-panel rounded-2xl p-sm md:p-md mb-lg">
<div class="flex items-center gap-xs mb-sm">
<span class="material-symbols-outlined text-primary">person_add</span>
<h2 class="font-body-sm text-body-sm text-primary font-semibold">Nuevo colaborador</h2>
</div>
<form class="module-form" method="post" action="">
<div class="flex flex-col md:flex-row gap-sm">
<div class="flex-1">
<label class="font-body-xs text-body-xs text-on-surface-variant/60 block mb-1">Correo electrónico</label>
<input type="email" name="email" required class="form-input" placeholder="correo@ejemplo.com">
</div>
<div class="flex-1">
<label class="font-body-xs text-body-xs text-on-surface-variant/60 block mb-1">Nombre completo</label>
<input type="text" name="name" required class="form-input" placeholder="Nombre completo">
</div>
</div>
<div class="form-actions mt-sm">
<button class="primary-gradient text-white px-md py-xs rounded-full font-label-lg text-label-lg active:scale-95 transition-transform primary-glow" type="submit" name="create_collaborator" value="1">+ Crear colaborador</button>
</div>
</form>
</div>
<?php endif; ?>

<?php if (count($collaborators) === 0): ?>
<div class="glass-panel rounded-3xl p-xl text-center">
<span class="material-symbols-outlined text-6xl text-on-surface-variant/30 mb-md">group</span>
<p class="font-title-sm text-title-sm text-on-surface mb-xs">Aún no hay colaboradores</p>
<p class="font-body-sm text-body-sm text-on-surface-variant/70 mb-md">Invita a otras personas a gestionar tus catálogos.</p>
<?php if ($isAdmin): ?>
<a href="#" onclick="document.getElementById('new-collab-form').scrollIntoView({behavior:'smooth'}); return false;" class="primary-gradient text-white px-lg py-sm rounded-full font-title-sm text-title-sm inline-flex active:scale-95 transition-transform primary-glow no-underline">+ Invitar colaborador</a>
<?php endif; ?>
</div>
<?php else: ?>
<!-- Desktop table -->
<div class="glass-panel rounded-3xl overflow-hidden mb-xl">
<div class="p-md border-b border-outline-variant/10 flex flex-wrap gap-md justify-between items-center bg-surface-container-highest/20">
<h2 class="font-title-sm text-title-sm text-primary flex items-center gap-xs"><span class="material-symbols-outlined">group</span> Colaboradores</h2>
</div>
<div class="overflow-x-auto">
<table class="w-full border-collapse">
<thead>
<tr class="text-left bg-surface-container-low/50">
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">Colaborador</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-center">Acceso</th>
<th class="p-md font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider text-right">Acciones</th>
</tr>
</thead>
<tbody class="divide-y divide-outline-variant/5">
<?php foreach ($collaborators as $u):
$uid = (int)$u['id'];
$access = $collabAccess[$uid] ?? [];
$catalogoCount = count($access);
?>
<tr class="hover:bg-surface-variant/10 transition-colors group">
<td class="p-md">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-primary/15 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-primary text-[20px]">person</span>
</div>
<div>
<span class="font-title-sm text-title-sm text-on-surface font-semibold block"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></span>
<span class="font-body-xs text-body-xs text-on-surface-variant/60"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
</div>
</div>
</td>
<td class="p-md text-center">
<span class="px-md py-1 rounded-full text-label-caps font-label-caps <?= $catalogoCount > 0 ? 'bg-primary/10 text-primary border border-primary/20' : 'bg-on-surface-variant/10 text-on-surface-variant border border-on-surface-variant/20' ?>"><?= $catalogoCount ?> catálogo(s)</span>
</td>
<td class="p-md text-right">
<div class="flex justify-end gap-1">
<button onclick="toggleAccess(<?= $uid ?>)" id="toggle-btn-<?= $uid ?>" class="p-2 hover:bg-primary/10 rounded-full text-on-surface-variant hover:text-primary transition-all"><span class="material-symbols-outlined transition-transform duration-200 toggle-arrow-<?= $uid ?>">expand_more</span></button>
<form method="POST">
<input type="hidden" name="target_user_id" value="<?= $uid ?>">
<button type="submit" name="reset_password" value="1" class="p-2 hover:bg-warning/10 rounded-full text-on-surface-variant hover:text-warning transition-all" title="Generar nueva contraseña"><span class="material-symbols-outlined">key</span></button>
</form>
</div>
</td>
</tr>
<tr id="access-<?= $uid ?>" class="hidden">
<td colspan="3" class="p-0">
<div class="bg-surface-container-low/50 p-md space-y-md">
<?php if ($catalogoCount > 0): ?>
<div class="flex flex-col gap-2">
<?php foreach ($access as $a): ?>
<?php $perms = is_string($a['permissions']) ? json_decode($a['permissions'], true) : ($a['permissions'] ?? []); ?>
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
<input type="hidden" name="target_user_id" value="<?= $uid ?>">
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
<button type="submit" name="add_access" value="1" class="px-3 py-1 rounded-full bg-primary/15 text-primary font-label-caps text-[0.65rem] hover:bg-primary/25 transition-all flex items-center gap-1 whitespace-nowrap">
<span class="material-symbols-outlined text-[12px]">add</span> Agregar
</button>
</div>
</form>
</div>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Mobile cards -->
<div class="mobile-cards">
<?php foreach ($collaborators as $u):
$uid = (int)$u['id'];
$access = $collabAccess[$uid] ?? [];
$catalogoCount = count($access);
?>
<div class="mobile-card">
<div class="mobile-card-header">
<div class="mobile-card-icon"><span class="material-symbols-outlined">person</span></div>
<div class="mobile-card-header-text">
<span class="mobile-card-title"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></span>
<span class="mobile-card-slug"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
</div>
</div>
<div class="mobile-card-body">
<div class="mobile-card-row">
<span class="mobile-card-label">Acceso</span>
<span class="px-md py-0.5 rounded-full text-label-caps font-label-caps <?= $catalogoCount > 0 ? 'bg-primary/10 text-primary border border-primary/20' : 'bg-on-surface-variant/10 text-on-surface-variant border border-on-surface-variant/20' ?>"><?= $catalogoCount ?> catálogo(s)</span>
</div>
</div>
<div class="mobile-card-actions">
<button onclick="toggleAccess(<?= $uid ?>)" id="toggle-btn-mobile-<?= $uid ?>" class="mobile-card-btn"><span class="material-symbols-outlined transition-transform duration-200 toggle-arrow-<?= $uid ?>">expand_more</span></button>
<form method="POST">
<input type="hidden" name="target_user_id" value="<?= $uid ?>">
<button type="submit" name="reset_password" value="1" class="mobile-card-btn" title="Generar nueva contraseña"><span class="material-symbols-outlined">key</span></button>
</form>
</div>
</div>
<div id="access-mobile-<?= $uid ?>" class="hidden">
<div class="bg-surface-container-low/50 p-md space-y-md">
<?php if ($catalogoCount > 0): ?>
<div class="flex flex-col gap-2">
<?php foreach ($access as $a): ?>
<?php $perms = is_string($a['permissions']) ? json_decode($a['permissions'], true) : ($a['permissions'] ?? []); ?>
<div class="flex flex-col gap-2 bg-surface-variant/20 rounded-xl p-3">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-[16px]">folder</span>
<span class="font-body-sm text-body-sm text-on-surface font-semibold"><?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?></span>
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

<div class="flex flex-col gap-2 bg-surface-variant/20 rounded-xl p-3">
<span class="font-body-xs text-body-xs text-on-surface-variant/60">Agregar acceso:</span>
<form method="POST" class="flex flex-col gap-2">
<input type="hidden" name="target_user_id" value="<?= $uid ?>">
<select name="catalog_id" class="form-input py-1 px-2 text-[0.75rem]">
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
<button type="submit" name="add_access" value="1" class="px-3 py-1 rounded-full bg-primary/15 text-primary font-label-caps text-[0.65rem] hover:bg-primary/25 transition-all flex items-center gap-1 border border-primary/20">
<span class="material-symbols-outlined text-[12px]">add</span> Agregar
</button>
</div>
</form>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="flex flex-col md:flex-row justify-between items-center gap-sm glass-panel px-sm py-xs rounded-2xl">
<p class="font-body-sm text-body-sm text-on-surface-variant/70">Mostrando <?= count($collaborators) ?> de <?= count($collaborators) ?> colaboradores</p>
</div>
</div>
<?php endif; ?>
</div>
</section>
</main>
<style>
@media (max-width: 768px) {
.overflow-x-auto { display: none; }
.mobile-cards { display: flex; flex-direction: column; gap: 0.75rem; padding: 0.75rem; }
.mobile-card { background: var(--surface-container-high, #1e1e2e); border-radius: 12px; padding: 1rem; border: 1px solid rgba(255,255,255,0.06); }
.mobile-card-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
.mobile-card-icon { width: 44px; height: 44px; border-radius: 50%; background: rgba(108,140,255,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mobile-card-icon span { font-size: 22px; color: #6c8cff; }
.mobile-card-header-text { flex: 1; min-width: 0; }
.mobile-card-title { font-size: 0.95rem; font-weight: 700; color: var(--text-on-surface, #e0e0f0); display: block; }
.mobile-card-slug { font-size: 0.75rem; color: rgba(255,255,255,0.35); }
.mobile-card-body { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 0.75rem; }
.mobile-card-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; }
.mobile-card-label { color: rgba(255,255,255,0.4); font-weight: 500; }
.mobile-card-value { color: rgba(255,255,255,0.7); font-weight: 600; text-align: right; }
.mobile-card-actions { display: flex; justify-content: flex-end; gap: 0.25rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.06); }
.mobile-card-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.4rem; height: 2.4rem; border-radius: 50%; color: rgba(255,255,255,0.5); text-decoration: none; transition: all 0.15s; background: transparent; border: none; cursor: pointer; }
.mobile-card-btn span { font-size: 1.15rem; }
.mobile-card-btn:hover { background: rgba(255,255,255,0.06); color: var(--text-on-surface, #e0e0f0); }
}
@media (min-width: 769px) {
.mobile-cards { display: none; }
}
</style>
<script>
function toggleAccess(id) {
  var isMobile = window.innerWidth < 769;
  var row = document.getElementById(isMobile ? "access-mobile-" + id : "access-" + id);
  if (!row) return;
  row.classList.toggle("hidden");
  var arrows = document.querySelectorAll(".toggle-arrow-" + id);
  var isHidden = row.classList.contains("hidden");
  arrows.forEach(function(a) {
    a.style.transform = isHidden ? "" : "rotate(180deg)";
  });
}
</script>
</html>

