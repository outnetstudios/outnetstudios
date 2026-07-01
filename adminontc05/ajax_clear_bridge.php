<?php
$bridgeSessionId = $_COOKIE['CATALOG_SESSION'] ?? '';
if ($bridgeSessionId) {
    require_once __DIR__ . '/../src/Database.php';
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('DELETE FROM sessions WHERE session_id = ?');
    $stmt->execute([$bridgeSessionId]);
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
