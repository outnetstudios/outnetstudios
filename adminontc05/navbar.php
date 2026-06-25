<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$displayName = $_SESSION['username'] ?? 'Administrador';
$pages = [
    'dashboard.php'       => ['label' => 'Prospectos',       'icon' => 'inbox'],
    'catalog_users.php'   => ['label' => 'Usuarios catálogo', 'icon' => 'group'],
    'catalogos.php'       => ['label' => 'Catálogos',         'icon' => 'menu_book'],
];
?>
<div class="dashboard-header">
    <div>
        <h1><?= $pageTitle ?? 'Panel Admin' ?></h1>
        <p><?= $pageSubtitle ?? ('Bienvenido, ' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . '.') ?></p>
    </div>
    <a class="button" href="logout.php">Cerrar sesión</a>
</div>

<div class="module-nav">
    <?php foreach ($pages as $file => $info): ?>
        <a class="module-nav__item<?= $currentPage === $file ? ' is-active' : '' ?>" href="<?= $file ?>">
            <span class="material-symbols-outlined module-nav__icon"><?= $info['icon'] ?></span>
            <?= $info['label'] ?>
        </a>
    <?php endforeach; ?>
</div>
