<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
catalogLogout();
header('Location: login.php');
exit;
