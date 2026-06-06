<?php
echo "<h2>Debug de imágenes</h2>";

// 1. GD library
echo "<h3>1. GD Library</h3>";
echo "GD available: " . (extension_loaded('gd') ? '✅ YES' : '❌ NO') . "<br>";
if (extension_loaded('gd')) {
    echo "GD version: " . gd_info()['GD Version'] . "<br>";
}

// 2. Upload directory
echo "<h3>2. Upload directory</h3>";
$uploadDir = __DIR__ . '/uploads/catalog';
echo "Path: $uploadDir<br>";
echo "Exists: " . (is_dir($uploadDir) ? '✅ YES' : '❌ NO') . "<br>";
if (is_dir($uploadDir)) {
    echo "Writable: " . (is_writable($uploadDir) ? '✅ YES' : '❌ NO') . "<br>";
    $files = scandir($uploadDir);
    $files = array_diff($files, ['.', '..', '.gitkeep']);
    echo "Files: " . (count($files) > 0 ? implode(', ', $files) : '(empty)') . "<br>";
}

// 3. Check DB for stored image paths
echo "<h3>3. Database image paths</h3>";
require_once __DIR__ . '/includes/db_connection.php';

$tables = ['catalogs' => 'cover_image, back_cover_image', 'catalog_products' => 'main_image', 'catalog_categories' => 'image', 'catalog_pages' => 'background_image'];
foreach ($tables as $table => $columns) {
    $result = $conn->query("SELECT id, $columns FROM $table WHERE $columns IS NOT NULL AND $columns != '' LIMIT 10");
    echo "<b>$table:</b><br>";
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            foreach (explode(', ', $columns) as $col) {
                if (!empty($row[$col])) {
                    $fullPath = __DIR__ . '/' . $row[$col];
                    $exists = file_exists($fullPath);
                    echo "  ID {$row['id']} → {$row[$col]} → " . ($exists ? '✅ FILE OK' : '❌ FILE MISSING') . "<br>";
                    if (!$exists) {
                        echo "  Full path checked: $fullPath<br>";
                    }
                }
            }
        }
    } else {
        echo "  (no images in this table)<br>";
    }
}

// 4. Test URL resolution
echo "<h3>4. URL resolution test</h3>";
echo "Current script: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";

// 5. Check if images from the DB are accessible via HTTP
echo "<h3>5. Image URL access test</h3>";
if (!empty($files)) {
    foreach ($files as $f) {
        $url = "https://{$_SERVER['HTTP_HOST']}/uploads/catalog/$f";
        echo "<a href='$url' target='_blank'>$url</a><br>";
    }
}
