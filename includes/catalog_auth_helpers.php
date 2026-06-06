<?php
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Session/DatabaseSessionHandler.php';

function catalogSessionStart()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CATALOG_SESSION');
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        try {
            $pdo = Database::getConnection();
            $handler = new DatabaseSessionHandler($pdo);
            session_set_save_handler($handler, true);
        } catch (\Throwable $e) {
            error_log('DB session handler init failed: ' . $e->getMessage());
        }
        session_start();
    }
}

function catalogLogin(int $userId, string $email, string $name)
{
    catalogSessionStart();
    session_regenerate_id(true);
    $_SESSION['catalog_loggedin'] = true;
    $_SESSION['catalog_user_id'] = $userId;
    $_SESSION['catalog_user_email'] = $email;
    $_SESSION['catalog_user_name'] = $name;
}

function catalogLogout()
{
    catalogSessionStart();
    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();
    setcookie(session_name(), '', time() - 42000, '/');
}

function isCatalogLoggedIn(): bool
{
    catalogSessionStart();
    return !empty($_SESSION['catalog_loggedin']) && $_SESSION['catalog_loggedin'] === true;
}

function catalogRequireLogin()
{
    if (!isCatalogLoggedIn()) {
        header('Location: /catalog_auth/login.php');
        exit;
    }
}

function catalogGetUserId(): ?int
{
    catalogSessionStart();
    return isset($_SESSION['catalog_user_id']) ? (int)$_SESSION['catalog_user_id'] : null;
}

function catalogGetUserName(): ?string
{
    catalogSessionStart();
    return $_SESSION['catalog_user_name'] ?? null;
}

function catalogGetUserEmail(): ?string
{
    catalogSessionStart();
    return $_SESSION['catalog_user_email'] ?? null;
}

function requireCatalogLogin()
{
    return catalogRequireLogin();
}
