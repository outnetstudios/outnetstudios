<?php
require_once __DIR__ . '/includes/db_connection.php';

echo "<h2>Rutas de imágenes en la BD</h2>";

$tables = [
    'catalogs (cover_image)' => "SELECT id, 'cover' AS tipo, cover_image AS ruta FROM catalogs WHERE cover_image IS NOT NULL AND cover_image != ''",
    'catalogs (back_cover_image)' => "SELECT id, 'back_cover' AS tipo, back_cover_image AS ruta FROM catalogs WHERE back_cover_image IS NOT NULL AND back_cover_image != ''",
    'catalog_products (main_image)' => "SELECT id, 'producto' AS tipo, main_image AS ruta FROM catalog_products WHERE main_image IS NOT NULL AND main_image != ''",
    'catalog_categories (image)' => "SELECT id, 'categoria' AS tipo, image AS ruta FROM catalog_categories WHERE image IS NOT NULL AND image != ''",
    'catalog_pages (background_image)' => "SELECT id, 'pagina' AS tipo, background_image AS ruta FROM catalog_pages WHERE background_image IS NOT NULL AND background_image != ''",
];

$total = 0;
foreach ($tables as $label => $sql) {
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        echo "<h3>$label</h3><table border='1' cellpadding='6' style='border-collapse:collapse;font-family:monospace;'><tr><th>ID</th><th>Tipo</th><th>Ruta en BD</th><th>Archivo existe?</th><th>URL pública</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $total++;
            $fullPath = __DIR__ . '/' . $row['ruta'];
            $exists = file_exists($fullPath) ? '✅' : '❌ NO';
            $publicUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $row['ruta'];
            echo "<tr><td>{$row['id']}</td><td>{$row['tipo']}</td><td>{$row['ruta']}</td><td>$exists</td><td><a href='$publicUrl' target='_blank'>$publicUrl</a></td></tr>";
        }
        echo "</table>";
    }
}

if ($total === 0) {
    echo "<p>No hay imágenes registradas en ninguna tabla.</p>";
}
echo "<p><strong>Total de registros con imágenes: $total</strong></p>";
