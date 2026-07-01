<?php
session_name('CATALOG_SESSION');
session_start();
unset($_SESSION['catalog_loggedin']);
unset($_SESSION['catalog_admin_bridge']);
unset($_SESSION['catalog_user_id']);
session_write_close();

header('Location: catalogos.php');
exit;
