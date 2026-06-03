<?php
$config = require __DIR__ . '/../config/env.php';
$db = $config['db'];

$conn = new mysqli($db['host'], $db['user'], $db['password'], $db['dbname'], $db['port']);

// Verifica la conexión
if ($conn->connect_error) {
    error_log('MySQL connection failed: ' . $conn->connect_error);
    die('No se pudo conectar a la base de datos.');
}

// Función para obtener datos de la tabla "contacto"
function getContactData($conn) {
    $query = "SELECT * FROM contacto";
    $result = $conn->query($query);

    if ($result) {
        return $result;
    } else {
        return false;
    }
}
?>
