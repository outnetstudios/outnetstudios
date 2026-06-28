# Plan: Toggle independiente para URL con precios

## Objetivo

- La URL con precios y la URL sin precios son **completamente independientes**. Quien recibe una URL solo puede ver esa versión.
- El dueño del catálogo puede **desactivar** la URL con precios mediante un toggle. Al desactivarla:
  - Esa URL deja de funcionar (muestra "Catálogo no disponible")
  - La URL sin precios sigue funcionando normalmente
- En la vista pública, si hay botón compartir, solo comparte la URL actual (sin ofrecer la otra versión).

---

## URLs finales

```
Con precios (activa):   ver_catalogo.php?c=ABC123   → muestra precios
Con precios (inactiva): ver_catalogo.php?c=ABC123   → 404 "Catálogo no disponible"
Sin precios:            ver_catalogo.php?c=XYZ789   → siempre funciona
```

Las dos URLs usan códigos distintos. No hay relación entre ellas.

---

## Cambios necesarios

### 1. Base de datos

```sql
ALTER TABLE catalogs
    ADD COLUMN prices_url_active TINYINT(1) NOT NULL DEFAULT 1
    AFTER public_pdf_download;
```

### 2. CatalogRepository

- Agregar `prices_url_active` al array `$fields` en `update()`
- En `findCatalogByCode()`: el JOIN ya trae `c.*` que incluye el nuevo campo

### 3. Verificación en preview.php y export_pdf.php

Cuando se accede vía `?c=CODE` con `show_prices=1`:
```php
if ($showPrices && empty($catalog['prices_url_active'])) {
    // mismo error que catálogo no encontrado
    http_response_code(404);
    echo 'Catálogo no disponible.';
    exit;
}
```

### 4. edit.php — Toggle en sección de códigos

Agregar toggle después de "Regenerar códigos":

```
┌─────────────────────────────────────────────┐
│  Códigos públicos                            │
│                                              │
│  URL con precios:  ver_catalogo.php?c=ABC123 │
│  URL sin precios:  ver_catalogo.php?c=XYZ789 │
│                                              │
│  [Regenerar códigos]                         │
│                                              │
│  ┌────────────────────────────────────┐      │
│  │ 🌐  URL con precios activa        │ [⏏] │
│  └────────────────────────────────────┘      │
│                                              
└─────────────────────────────────────────────┘
```

El toggle `prices_url_active` se guarda con el POST del formulario principal.

### 5. preview.php — Modal de compartir (admin)

El modal en la vista previa admin debe reflejar el estado:

- Si `prices_url_active=0`: el botón "Compartir URL con precios" aparece **deshabilitado/atenuado** con texto "URL con precios (inactiva)" y tooltip explicativo
- Si `prices_url_active=1`: funciona normalmente

### 6. preview.php — Vista pública (sin modal)

En la vista pública **no hay modal con elección**. Solo un botón que copia la URL actual directamente:

```
Si ve con precios:     botón "Compartir" → copia ?c=ABC123
Si ve sin precios:     botón "Compartir" → copia ?c=XYZ789
```

Sin modal, sin opción de elegir la otra versión.

---

## Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `setup_prices_url_toggle.php` | (NUEVO) Migración: agregar columna `prices_url_active` |
| `src/Repositories/CatalogRepository.php` | Agregar `prices_url_active` a `$fields` en `update()` |
| `catalog_admin/preview.php` | Admin modal: deshabilitar opción con precios si inactiva. Público: share copia URL actual sin modal |
| `catalog_admin/edit.php` | Agregar toggle `prices_url_active` + guardar en POST |
| `catalog_admin/export_pdf.php` | Verificar `prices_url_active` si `show_prices=1` |

---

## Comportamiento esperado

| Escenario | Resultado |
|-----------|-----------|
| Admin comparte URL con precios | Receptor ve catálogo con precios |
| Admin comparte URL sin precios | Receptor ve catálogo sin precios |
| Admin desactiva toggle "URL con precios" | URL con precios existente → 404. URL sin precios → OK |
| Admin reactiva toggle | URL con precios vuelve a funcionar |
| Público ve catálogo con precios y da "Compartir" | Copia la URL con precios (solo esa) |
| Público ve catálogo sin precios y da "Compartir" | Copia la URL sin precios (solo esa) |
