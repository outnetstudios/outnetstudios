<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Variables de entorno disponibles:</h2>";
$vars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD', 'MJ_APIKEY_PUBLIC', 'MJ_APIKEY_PRIVATE', 'MAIL_FROM_EMAIL', 'MAIL_FROM_NAME', 'MAIL_ADMIN_EMAIL'];
foreach ($vars as $v) {
    $val = getenv($v);
    echo "<b>$v</b>: " . ($val ? htmlspecialchars($val) : '❌ NO DEFINIDA') . "<br>";
}

echo "<h2>Config desde env.php:</h2>";
try {
    $config = require __DIR__ . '/config/env.php';
    echo "<pre>" . htmlspecialchars(print_r($config, true)) . "</pre>";
} catch (Exception $e) {
    echo "❌ Error cargando config: " . $e->getMessage();
}
