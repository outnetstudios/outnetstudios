<?php
// Datos de la base de datos
$host = "sql211.infinityfree.com"; // Cambia esto si tu base de datos está en otro servidor
$port = '3306'; 
$dbname = "if0_37283474_outnetstudios";
$username = "if0_37283474";
$password = "2joscH5xCwnO";

// Crear conexión
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si los datos han sido enviados por el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_apellido = $conn->real_escape_string($_POST['nombre_apellido']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $plan_interes = $conn->real_escape_string($_POST['plan_interes']);
    $nombre_empresa = $conn->real_escape_string($_POST['nombre_empresa']);
    $sector = $conn->real_escape_string($_POST['sector']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);

    // Insertar los datos en la base de datos
    $sql = "INSERT INTO contacto (nombre_apellido, email, telefono, plan_interes, nombre_empresa, sector, descripcion)
            VALUES ('$nombre_apellido', '$email', '$telefono', '$plan_interes', '$nombre_empresa', '$sector', '$descripcion')";

    if ($conn->query($sql) === TRUE) {
        echo "Datos enviados correctamente.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
