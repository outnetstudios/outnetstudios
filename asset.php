<?php
$path = $_GET['p'] ?? '';
if (!$path) { http_response_code(404); exit; }

// 1. Try filesystem first
$full = __DIR__ . '/' . $path;
if (file_exists($full)) {
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => 'application/octet-stream',
    };
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=31536000');
    readfile($full);
    exit;
}

// 2. Try database
try {
    require_once __DIR__ . '/src/Database.php';
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT data, mime FROM catalog_assets WHERE path = ?');
    $stmt->execute([$path]);
    $row = $stmt->fetch();
    if ($row) {
        header('Content-Type: ' . $row['mime']);
        header('Cache-Control: public, max-age=31536000');
        echo $row['data'];
        exit;
    }
} catch (\Throwable $e) {
    error_log('asset.php DB error: ' . $e->getMessage());
}

http_response_code(404);
