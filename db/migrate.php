<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require __DIR__ . '/../config/env.php';

if (empty($config['db']['host'])) {
    die("❌ DB_HOST no está configurado. Revisa las variables de entorno en Wasmer.");
}

$db = $config['db'];

try {
    $pdo = new PDO("mysql:host={$db['host']};port={$db['port']};charset=utf8mb4", $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $dbname = $db['dbname'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `$dbname`");

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        die("❌ No se pudo leer db/schema.sql");
    }

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $count = 0;
    foreach ($statements as $stmt) {
        if (!empty($stmt) && stripos($stmt, 'CREATE') === 0) {
            $pdo->exec($stmt);
            $count++;
        }
    }

    echo "✅ Base de datos '$dbname' lista. $count tablas creadas.";
} catch (PDOException $e) {
    echo "❌ Error de BD: " . $e->getMessage();
}
