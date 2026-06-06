<?php
require_once __DIR__ . '/../src/Database.php';

function uploadImage(array $file, string $subfolder = ''): ?string
{
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed)) return null;
    $ext = match ($file['type']) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => null,
    };
    if (!$ext) return null;

    // Read file content
    $content = file_get_contents($file['tmp_name']);
    if ($content === false) return null;

    // Determine MIME type from actual content (more reliable than $_FILES)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($content);
    // Re-check against allowed types using the actual content
    if (!in_array($mime, $allowed)) return null;

    // Generate virtual path
    $filename = uniqid() . '.' . $ext;
    $relPath = 'uploads/catalog' . ($subfolder ? '/' . trim($subfolder, '/') : '') . '/' . $filename;

    // Try filesystem first
    $uploadBasePath = __DIR__ . '/../uploads/catalog';
    $folder = $uploadBasePath . ($subfolder ? '/' . trim($subfolder, '/') : '');
    if (!is_dir($folder)) @mkdir($folder, 0755, true);
    $dest = $folder . '/' . $filename;
    $fsOk = file_put_contents($dest, $content, LOCK_EX) !== false;
    if ($fsOk) @chmod($dest, 0644);

    // Verify filesystem write actually persisted
    if ($fsOk && file_exists($dest) && filesize($dest) > 0) {
        // Check if getimagesize can read it (fails on WASI)
        $imgOk = @getimagesize($dest);
        if ($imgOk) {
            compressImage($dest, $mime);
            return $relPath;
        }
        // WASI: file appears but content not readable — fall through to DB
        @unlink($dest);
    }

    // Store in database as fallback
    try {
        $pdo = Database::getConnection();
        // Ensure table exists
        $pdo->exec('CREATE TABLE IF NOT EXISTS catalog_assets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            path VARCHAR(500) NOT NULL UNIQUE,
            data MEDIUMBLOB NOT NULL,
            mime VARCHAR(100) NOT NULL,
            size INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_path (path)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $stmt = $pdo->prepare('INSERT INTO catalog_assets (path, data, mime, size) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), mime = VALUES(mime), size = VALUES(size)');
        $stmt->execute([$relPath, $content, $mime, strlen($content)]);

        // If there's an old file on disk (from a previous attempt), clean it up
        if (file_exists($dest)) @unlink($dest);

        return $relPath;
    } catch (\Throwable $e) {
        error_log('DB image storage failed: ' . $e->getMessage());
        return null;
    }
}

function imageUrl(?string $path): string
{
    if (!$path) return '';
    return '/asset.php?p=' . urlencode($path);
}

function compressImage(string $path, string $mime): void
{
    global $maxDim, $jpgQuality, $webpQuality;
    [$w, $h] = @getimagesize($path);
    if (!$w || !$h) return;
    $ratio = min($maxDim / max($w, $h), 1);
    $nw = (int)round($w * $ratio);
    $nh = (int)round($h * $ratio);
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png' => @imagecreatefrompng($path),
        'image/webp' => @imagecreatefromwebp($path),
        'image/gif' => null,
        default => null,
    };
    if (!$src) return;
    if ($mime === 'image/gif') { imagedestroy($src); return; }
    if ($ratio < 1) {
        $dst = imagecreatetruecolor($nw, $nh);
        if ($mime === 'image/png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    } else {
        $dst = $src;
    }
    $tmp = $path . '.tmp';
    match ($mime) {
        'image/jpeg' => @imagejpeg($dst, $tmp, 80),
        'image/png' => @imagepng($dst, $tmp, 9),
        'image/webp' => @imagewebp($dst, $tmp, 80),
        default => null,
    };
    imagedestroy($src);
    if ($ratio < 1) imagedestroy($dst);
    if (file_exists($tmp) && filesize($tmp) > 0) {
        $renamed = @rename($tmp, $path);
        if ($renamed) {
            @chmod($path, 0644);
        } else {
            @unlink($tmp);
        }
    }
}

function deleteImage(?string $path): void
{
    if (!$path) return;
    // Delete from filesystem if exists
    $full = __DIR__ . '/../' . $path;
    if (file_exists($full)) @unlink($full);
    // Delete from database
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM catalog_assets WHERE path = ?');
        $stmt->execute([$path]);
    } catch (\Throwable $e) {
        error_log('DB image delete failed: ' . $e->getMessage());
    }
}

// Global vars used by compressImage
$uploadBasePath = __DIR__ . '/../uploads/catalog';
$maxDim = 1920;
$jpgQuality = 80;
$webpQuality = 80;
