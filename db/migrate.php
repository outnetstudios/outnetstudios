<?php
/**
 * Run ONCE to create database + all tables.
 * DELETE this file after execution.
 */
$config = require __DIR__ . '/../config/env.php';
$db = $config['db'];

try {
    // Connect without database to create it
    $pdo = new PDO("mysql:host={$db['host']};port={$db['port']};charset=utf8mb4", $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $dbname = $db['dbname'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `$dbname`");

    $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));

    echo "✅ Database '$dbname' created and all tables migrated successfully.";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
