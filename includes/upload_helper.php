<?php
$uploadBasePath = __DIR__ . '/../uploads/catalog';
$maxDim = 1920;
$jpgQuality = 80;
$webpQuality = 80;

function uploadImage(array $file, string $subfolder = ''): ?string
{
    global $uploadBasePath;
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
    $folder = $uploadBasePath . ($subfolder ? '/' . trim($subfolder, '/') : '');
    if (!is_dir($folder)) mkdir($folder, 0755, true);
    $filename = uniqid() . '.' . $ext;
    $dest = $folder . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    compressImage($dest, $file['type']);
    $relPath = 'uploads/catalog' . ($subfolder ? '/' . trim($subfolder, '/') : '') . '/' . $filename;
    return $relPath;
}

function compressImage(string $path, string $mime): void
{
    global $maxDim, $jpgQuality, $webpQuality;
    [$w, $h] = getimagesize($path);
    if (!$w || !$h) return;
    $ratio = min($maxDim / max($w, $h), 1);
    $nw = (int)round($w * $ratio);
    $nh = (int)round($h * $ratio);
    $src = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png' => imagecreatefrompng($path),
        'image/webp' => imagecreatefromwebp($path),
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
        'image/jpeg' => imagejpeg($dst, $tmp, $jpgQuality),
        'image/png' => imagepng($dst, $tmp, 9),
        'image/webp' => imagewebp($dst, $tmp, $webpQuality),
        default => null,
    };
    imagedestroy($src);
    if ($ratio < 1) imagedestroy($dst);
    if (file_exists($tmp) && filesize($tmp) > 0) {
        unlink($path);
        rename($tmp, $path);
    }
}

function deleteImage(?string $path): void
{
    if ($path) {
        $full = __DIR__ . '/../' . $path;
        if (file_exists($full)) unlink($full);
    }
}
