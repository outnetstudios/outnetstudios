<?php
$log = [];

// Check what we're working with
$log[] = 'PHP version: ' . PHP_VERSION;
$log[] = 'POST max size: ' . ini_get('upload_max_filesize') . ' / ' . ini_get('post_max_size');
$log[] = 'Upload tmp dir: ' . (ini_get('upload_tmp_dir') ?: '(system default)');
$log[] = 'Sys tmp dir: ' . sys_get_temp_dir();
$log[] = 'upload_max_filesize bytes: ' . return_bytes(ini_get('upload_max_filesize'));
$log[] = 'post_max_size bytes: ' . return_bytes(ini_get('post_max_size'));

$log[] = 'GD: ' . (extension_loaded('gd') ? 'yes' : 'NO');
$log[] = 'PDO mysql: ' . (extension_loaded('pdo_mysql') ? 'yes' : 'NO');

$uploadBasePath = __DIR__ . '/uploads/catalog';
$log[] = 'Upload base path: ' . $uploadBasePath;
$log[] = 'Path exists: ' . (is_dir($uploadBasePath) ? 'yes' : 'NO');
$log[] = 'Path writable: ' . (is_writable($uploadBasePath) ? 'yes' : 'NO');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['test_image'])) {
    $file = $_FILES['test_image'];
    $log[] = '--- UPLOAD ATTEMPT ---';
    $log[] = 'File error code: ' . $file['error'];
    $log[] = 'File tmp_name: ' . $file['tmp_name'];
    $log[] = 'File name: ' . $file['name'];
    $log[] = 'File type: ' . $file['type'];
    $log[] = 'File size: ' . $file['size'];

    // Step 1: tmp file exists?
    $log[] = 'tmp file exists: ' . (file_exists($file['tmp_name']) ? 'yes' : 'NO');

    // Step 2: try move_uploaded_file
    $filename = uniqid('test_') . '.jpg';
    $dest = $uploadBasePath . '/' . $filename;
    $moved = move_uploaded_file($file['tmp_name'], $dest);
    $log[] = 'move_uploaded_file: ' . ($moved ? 'OK -> ' . $dest : 'FAILED');
    
    if ($moved) {
        $log[] = 'File on disk: ' . (file_exists($dest) ? 'yes (' . filesize($dest) . ' bytes)' : 'NO');
        
        // Step 3: getimagesize
        $size = @getimagesize($dest);
        $log[] = 'getimagesize: ' . ($size ? "{$size[0]}x{$size[1]} type={$size[2]}" : 'FAILED');
        
        // Step 4: imagecreatefromjpeg
        if ($size) {
            $src = @imagecreatefromjpeg($dest);
            $log[] = 'imagecreatefromjpeg: ' . ($src ? 'OK (resource)' : 'FAILED');
            
            if ($src) {
                // Step 5: imagejpeg to tmp
                $tmp = $dest . '.tmp';
                $saved = @imagejpeg($src, $tmp, 80);
                $log[] = 'imagejpeg to tmp: ' . ($saved ? 'OK' : 'FAILED');
                $log[] = 'tmp file exists: ' . (file_exists($tmp) ? 'yes (' . filesize($tmp) . ' bytes)' : 'NO');
                
                imagedestroy($src);
                
                // Step 6: rename
                if (file_exists($tmp) && filesize($tmp) > 0) {
                    $renamed = @rename($tmp, $dest);
                    $log[] = 'rename($tmp, $dest): ' . ($renamed ? 'OK' : 'FAILED');
                    $log[] = 'Final file on disk: ' . (file_exists($dest) ? 'yes (' . filesize($dest) . ' bytes)' : 'NO');
                    if (!$renamed) {
                        @unlink($tmp);
                        $log[] = 'Cleaned up tmp file';
                    }
                }
            }
        }
        
        $log[] = '--- URL: /uploads/catalog/' . $filename . ' ---';
    }
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower(substr($val, -1));
    $val = (int)substr($val, 0, -1);
    switch ($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Upload Test</title></head>
<body style="font-family:monospace;background:#111;color:#0f0;padding:2rem;">
<h1>Upload Test</h1>
<form method="POST" enctype="multipart/form-data">
<input type="file" name="test_image" accept="image/jpeg,image/png" required>
<button type="submit">Subir</button>
</form>
<hr>
<?php foreach ($log as $line): ?>
<div><?= htmlspecialchars($line) ?></div>
<?php endforeach; ?>
</body></html>
