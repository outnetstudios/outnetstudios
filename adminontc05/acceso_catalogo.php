<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$adminUsername = $_SESSION['username'];
$catalogId = (int)($_GET['catalog_id'] ?? 0);

if ($catalogId <= 0) {
    header('Location: catalogos.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Repositories/CatalogRepository.php';
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';

$repo = new CatalogRepository();
$catalog = $repo->findById($catalogId);

if (!$catalog) {
    header('Location: catalogos.php');
    exit;
}

$userRepo = new CatalogUserRepository();
$owner = $userRepo->findById((int)$catalog['user_id']);

if (!$owner) {
    header('Location: catalogos.php');
    exit;
}

// --- Session bridge ---
session_write_close(); // close admin session

session_name('CATALOG_SESSION');
session_start();
$_SESSION['catalog_loggedin'] = true;
$_SESSION['catalog_user_id'] = (int)$owner['id'];
$_SESSION['catalog_user_email'] = $owner['email'];
$_SESSION['catalog_user_name'] = $owner['name'];
$_SESSION['catalog_admin_bridge'] = true;
$_SESSION['catalog_admin_username'] = $adminUsername;
session_write_close();

// --- Audit log ---
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('INSERT INTO catalog_admin_access (admin_username, catalog_id, catalog_name, catalog_user_id, catalog_user_name, action, ip_address) VALUES (:admin, :cid, :cname, :uid, :uname, :action, :ip)');
    $stmt->execute([
        ':admin' => $adminUsername,
        ':cid' => $catalogId,
        ':cname' => $catalog['name'],
        ':uid' => (int)$owner['id'],
        ':uname' => $owner['name'],
        ':action' => 'access',
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);
} catch (\Throwable $e) {
    error_log('Admin catalog access audit failed: ' . $e->getMessage());
}

header('Location: /catalog_admin/pages.php?catalog_id=' . $catalogId);
exit;
