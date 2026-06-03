<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php"); // Redirigir al login si no está autenticado
    exit();
}
?>

<!-- Contenido privado -->
<h1>Bienvenido, <?php echo $_SESSION['usuario']; ?>!</h1>
<p>Esta es tu vista privada.</p>
<a href="logout.php">Cerrar sesión</a>
