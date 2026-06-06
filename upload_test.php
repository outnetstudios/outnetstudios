<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$log = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['test_image'])) {
    $file = $_FILES['test_image'];
    
    $filename = uniqid('test_') . '.jpg';
    $dest = __DIR__ . '/uploads/catalog/' . $filename;
    
    $log[] = '1. move_uploaded_file → ' . ($file['tmp_name'] . ' -> ' . $dest);
    $moved = move_uploaded_file($file['tmp_name'], $dest);
    $log[] = '   Result: ' . ($moved ? 'OK' : 'FAILED');
    
    if ($moved) {
        $log[] = '2. file_exists: ' . (file_exists($dest) ? 'YES' : 'NO');
        $log[] = '3. filesize: ' . filesize($dest);
        $log[] = '4. fileperms: ' . substr(sprintf('%o', fileperms($dest)), -4);
        
        $log[] = '5. getimagesize() ...';
        $size = @getimagesize($dest);
        if ($size) {
            $log[] = '   OK: ' . $size[0] . 'x' . $size[1] . ' type=' . $size[2];
        } else {
            $log[] = '   FAILED (returns false)';
            // Try reading first bytes
            $fh = fopen($dest, 'rb');
            $header = $fh ? bin2hex(fread($fh, 16)) : 'cannot open';
            if ($fh) fclose($fh);
            $log[] = '   First 16 bytes (hex): ' . $header;
            $log[] = '   Expected JPEG: ffd8ffe0 or ffd8ffe1';
        }
        
        $log[] = '6. HTTP URL: /uploads/catalog/' . $filename;
    }
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Upload Test V2</title></head>
<body style="font-family:monospace;background:#111;color:#0f0;padding:2rem;">
<h1>Upload Test V2</h1>
<form method="POST" enctype="multipart/form-data">
<input type="file" name="test_image" accept="image/jpeg,image/png" required>
<button type="submit">Subir y diagnosticar</button>
</form>
<hr>
<?php foreach ($log as $line): ?>
<div><?= htmlspecialchars($line) ?></div>
<?php endforeach; ?>
</body></html>
