<?php
$host = 'sql211.infinityfree.com';
$user = 'if0_37283474';
$password = '2joscH5xCwnO';
$dbname = 'if0_37283474_outnetstudios';
$port = '3306'; // Verifica si este puerto es el correcto

$conn = new mysqli($host, $user, $pass, $db);

// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
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
