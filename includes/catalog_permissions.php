<?php
require_once __DIR__ . '/../src/Repositories/CollaboratorRepository.php';

const PERM_EDIT_CATALOG = 'edit_catalog';
const PERM_EDIT_PAGES = 'edit_pages';
const PERM_EDIT_PRODUCTS = 'edit_products';
const PERM_EDIT_CATEGORIES = 'edit_categories';

const ACCESS_OWNER = 'owner';
const ACCESS_COLLABORATOR = 'collaborator';
const ACCESS_NONE = 'none';

const ALL_PERMISSIONS = [
    PERM_EDIT_CATALOG,
    PERM_EDIT_PAGES,
    PERM_EDIT_PRODUCTS,
    PERM_EDIT_CATEGORIES,
];

function catalogGetAccessLevel(int $catalogId, int $userId): string
{
    $catRepo = new CatalogRepository();
    $catalog = $catRepo->findById($catalogId);
    if ($catalog && (int)$catalog['user_id'] === $userId) {
        return ACCESS_OWNER;
    }
    $collabRepo = new CollaboratorRepository();
    $collab = $collabRepo->findByUserAndCatalog($userId, $catalogId);
    if ($collab) {
        $perms = json_decode($collab['permissions'], true);
        if (is_array($perms)) {
            foreach (ALL_PERMISSIONS as $p) {
                if (!empty($perms[$p])) {
                    return ACCESS_COLLABORATOR;
                }
            }
        }
    }
    return ACCESS_NONE;
}

function catalogCanEdit(int $catalogId, int $userId, string $section): bool
{
    $catRepo = new CatalogRepository();
    $catalog = $catRepo->findById($catalogId);
    if ($catalog && (int)$catalog['user_id'] === $userId) {
        return true;
    }
    $collabRepo = new CollaboratorRepository();
    $collab = $collabRepo->findByUserAndCatalog($userId, $catalogId);
    if ($collab) {
        $perms = json_decode($collab['permissions'], true);
        return !empty($perms[$section]);
    }
    return false;
}

function catalogHasAnyAccess(int $catalogId, int $userId): bool
{
    $level = catalogGetAccessLevel($catalogId, $userId);
    return $level !== ACCESS_NONE;
}

function catalogGetUserAccessLevel(): string
{
    global $catalog, $userId;
    if (!isset($catalog, $userId)) return ACCESS_NONE;
    return catalogGetAccessLevel((int)$catalog['id'], $userId);
}

function catalogIsOwner(int $catalogId, int $userId): bool
{
    $catRepo = new CatalogRepository();
    $catalog = $catRepo->findById($catalogId);
    return $catalog && (int)$catalog['user_id'] === $userId;
}