<?php
require_once __DIR__ . '/includes/db_connection.php';

$username = 'David.TC005';
$password = 'EsteEsMiAdmi050926!';
$email = 'outnetstudios@gmail.com';

// Check if user already exists
$stmt = $conn->prepare('SELECT id FROM admin_users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    // Update password
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE admin_users SET password = ? WHERE username = ?');
    $stmt->bind_param('ss', $hashed, $username);
    $stmt->execute();
    echo "Usuario actualizado: $username<br>Hash: $hashed";
} else {
    // Insert new user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $username, $hashed, $email);
    $stmt->execute();
    echo "Usuario creado: $username<br>Hash: $hashed";
}
