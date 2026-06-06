<?php
class Database
{
    private static $instance = null;

    public static function getConnection()
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/env.php';
            $db = $config['db'];

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;port=%s;charset=utf8mb4',
                $db['host'],
                $db['dbname'],
                $db['port']
            );

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                error_log('PDO connection failed: ' . $e->getMessage());
                throw $e;
            }
        }

        return self::$instance;
    }
}
