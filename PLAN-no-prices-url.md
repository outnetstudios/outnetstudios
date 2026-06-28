# Plan: URLs públicas con códigos independientes

## Problema real

Si ambas URLs se diferencian solo por un parámetro (`?c=X&s=token` vs `?c=X`), cualquiera que tenga la URL con precios puede **modificar o quitar parámetros** y potencialmente adivinar/forzar la URL sin precios.

El usuario necesita **dos códigos completamente distintos** para el mismo catálogo, de forma que tener uno NO permita deducir el otro.

```
Con precios:   ver_catalogo.php?c=ABC123    ← código único A
Sin precios:   ver_catalogo.php?c=XYZ789    ← código único B (completamente diferente)
```

Son dos identificadores independientes. No hay relación entre ellos.

---

## Solución: Tabla `catalog_codes`

```sql
CREATE TABLE catalog_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    catalog_id INT NOT NULL,
    code VARCHAR(16) NOT NULL UNIQUE,
    show_prices TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_catalog (catalog_id)
);
```

Al crear un catálogo se insertan **dos filas** en esta tabla:

| catalog_id | code | show_prices |
|-----------|------|-------------|
| 3 | `ABC123` | 1 |
| 3 | `XYZ789` | 0 |

### Flujo de lookup

```php
// preview.php / export_pdf.php
$catalogId = 0;
$showPrices = true;

if (!empty($_GET['c'])) {
    $codeData = $catRepo->findCatalogByCode($_GET['c']);
    if ($codeData) {
        $catalogId = (int)$codeData['catalog_id'];
        $showPrices = (bool)$codeData['show_prices'];
        $catalog = $codeData; // incluye todos los campos de catalogs
    }
}

// Fallback: ?id= sigue funcionando (con precios por defecto)
if (!$catalogId) {
    $catalogId = (int)($_GET['id'] ?? 0);
    $catalog = $catRepo->findById($catalogId);
    $showPrices = true;
}
```

### Generación de códigos

Códigos de 8 caracteres sin vocales ni ambiguos:

```php
function generatePublicCode(): string {
    $chars = 'BCDFGHJKLMNPQRSTVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
```

### Catálogos existentes

Script `setup_catalog_codes.php` que crea la tabla y genera ambos códigos para cada catálogo:

```php
<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/src/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// Crear tabla catalog_codes
$pdo->exec("
    CREATE TABLE IF NOT EXISTS catalog_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        catalog_id INT NOT NULL,
        code VARCHAR(16) NOT NULL UNIQUE,
        show_prices TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (catalog_id) REFERENCES catalogs(id) ON DELETE CASCADE,
        INDEX idx_code (code),
        INDEX idx_catalog (catalog_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

function generatePublicCode(PDO $pdo): string {
    $chars = 'BCDFGHJKLMNPQRSTVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $check = $pdo->prepare('SELECT COUNT(*) FROM catalog_codes WHERE code = ?');
        $check->execute([$code]);
    } while ($check->fetchColumn() > 0);
    return $code;
}

$catalogs = $pdo->query('SELECT id FROM catalogs ORDER BY id');
$insert = $pdo->prepare('INSERT IGNORE INTO catalog_codes (catalog_id, code, show_prices) VALUES (?, ?, ?)');
$count = 0;

while ($cat = $catalogs->fetch()) {
    $cid = (int)$cat['id'];
    // Verificar si ya tiene códigos
    $exists = $pdo->prepare('SELECT COUNT(*) FROM catalog_codes WHERE catalog_id = ?');
    $exists->execute([$cid]);
    if ($exists->fetchColumn() > 0) continue;

    $insert->execute([$cid, generatePublicCode($pdo), 1]);  // con precios
    $insert->execute([$cid, generatePublicCode($pdo), 0]);  // sin precios
    $count++;
}

echo "OK: $count catálogos actualizados.\n";
```

---

## Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `setup_catalog_codes.php` | (NUEVO) Crear tabla + generar códigos para catálogos existentes |
| `src/Repositories/CatalogRepository.php` | Agregar `findCatalogByCode()`, `createCatalogCode()`, `regenerateCodes()` |
| `catalog_admin/preview.php` | Lookup por `c` + fallback `id`; `$showPrices` viene del code, no de GET |
| `catalog_admin/export_pdf.php` | Ídem |
| `catalog_admin/index.php` | Modal JS genera URLs con códigos (need to fetch codes from API or embed in HTML) |
| `catalog_admin/edit.php` | Botón "Regenerar códigos públicos" (genera new codes, invalida anteriores) |
| `catalog_admin/save.php` | Generar ambos códigos al crear catálogo |
| `includes/catalog_preview_renderer.php` | Helper `generatePublicCode()` |

### Index.php: cómo pasar códigos al JS

Los códigos deben estar disponibles en el HTML para que el modal de compartir pueda construir las URLs. Dos opciones:

**Opción A (recomendada):** Cargar los códigos via data attributes en cada card.

```html
<button onclick="abrirModalCompartir(this)"
        data-code-prices="ABC123"
        data-code-no-prices="XYZ789">
```

El JS lee los atributos del botón clickeado.

**Opción B:** Endpoint JSON aparte al abrir el modal.

Opción A es más simple y no requiere requests extra.

---

## Consideraciones de seguridad

- **Códigos independientes**: tener la URL con precios NO permite adivinar la URL sin precios (son códigos distintos, aleatorios, sin relación)
- **Regeneración**: al regenerar códigos desde edit.php, las URLs anteriores dejan de funcionar inmediatamente
- **Sin ID numérico**: el `?id=` queda solo como fallback legacy; las URLs nuevas solo usan `?c=`
- **Sin flag visible**: no hay `no_prices=1` ni `s=token` — el modo viene implícito en el código mismo

---

## Resumen de URLs finales

| Modo | URL |
|------|-----|
| Con precios | `ver_catalogo.php?c=ABC123` |
| Sin precios | `ver_catalogo.php?c=XYZ789` |
| Legacy (con precios) | `ver_catalogo.php?id=3` (sigue funcionando) |

Si alguien obtiene `ver_catalogo.php?c=ABC123`, **no puede** quitar ni modificar nada para ver el catálogo sin precios. Necesitaría el código `XYZ789`, que es completamente distinto.
