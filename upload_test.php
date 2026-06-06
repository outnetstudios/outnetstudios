<?php
$log = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['test_image'])) {
    $file = $_FILES['test_image'];
    $dest = __DIR__ . '/uploads/catalog/' . uniqid('test_') . '.jpg';
    $moved = move_uploaded_file($file['tmp_name'], $dest);
    $log[] = 'moved: ' . ($moved ? 'OK' : 'FAIL');
    if ($moved) {
        $log[] = 'exists: ' . (file_exists($dest) ? 'yes' : 'no');
        $log[] = 'size: ' . filesize($dest);
        $log[] = 'perms: ' . substr(sprintf('%o', fileperms($dest)), -4);
        $fh = fopen($dest, 'rb');
        if ($fh) {
            $log[] = 'hex: ' . bin2hex(fread($fh, 16));
            fclose($fh);
        } else {
            $log[] = 'cannot open';
        }
        $log[] = 'url: /uploads/catalog/' . basename($dest);
    }
}
?>
<form method="POST" enctype="multipart/form-data">
<input type="file" name="test_image" required><button>Subir</button>
</form>
<pre><?php print_r($log) ?></pre>
