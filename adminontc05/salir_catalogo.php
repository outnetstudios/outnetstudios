<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Session/DatabaseSessionHandler.php';

$pdo = Database::getConnection();
$handler = new DatabaseSessionHandler($pdo);
session_set_save_handler($handler, true);

session_name('CATALOG_SESSION');
session_start();
$_SESSION = [];
session_write_close();

header('Location: catalogos.php');
exit;
