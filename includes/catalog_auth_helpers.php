<?php
require_once __DIR__ . '/../src/Repositories/CatalogUserRepository.php';

function catalogSessionStart()
{
    if (session_status() === PHP_SESSION_NONE) {
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
    unset($_SESSION['catalog_loggedin'], $_SESSION['catalog_user_id'], $_SESSION['catalog_user_email'], $_SESSION['catalog_user_name']);
    session_regenerate_id(true);
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

// Backwards compatible alias
function requireCatalogLogin()
{
    return catalogRequireLogin();
}
