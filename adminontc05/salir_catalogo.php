<?php
session_name('CATALOG_SESSION');
session_start();
$_SESSION = [];
session_regenerate_id(true);
session_destroy();
setcookie(session_name(), '', time() - 42000, '/');

header('Location: catalogos.php');
exit;
