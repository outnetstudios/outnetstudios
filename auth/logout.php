<?php
session_start();
session_destroy(); // Destruir la sesión
header("Location: login.php"); // Redirigir al login
exit();
