# Análisis: Carga de imágenes en export_pdf.php

## Pipeline completo de una imagen

```
DB (catalog_images / catalog_assets)
  → imageUrl($path) → "/asset.php?p=" . urlencode($path)
    → Browser request a /asset.php?p=...
      → asset.php busca en filesystem (efímero en Wasmer)
        → Si no, busca en DB catalog_assets
          → Devuelve Content-Type + Cache-Control max-age=31536000
```

## Archivos involucrados

| Archivo | Rol |
|---------|-----|
| `includes/upload_helper.php` | `uploadImage()` guarda en DB + filesystem; `imageUrl()` genera URL |
| `asset.php` | Sirve imágenes desde filesystem (primero) o DB (fallback) |
| `includes/catalog_preview_renderer.php` | `render*Page()` genera `<img>` y `background:url()` en HTML |
| `catalog_admin/export_pdf.php` | Renderiza TODAS las páginas, precarga imágenes, dispara `window.print()` |

## Problemas identificados

### 1. Bug de `onerror` post-`load` (CORREGIDO)

Cuando `window.addEventListener('load', ...)` se dispara, los `<img>` ya completaron su carga. Si fallaron, `img.complete === true` pero `img.naturalWidth === 0`. El evento `onerror` ya ocurrió y nunca se disparará aunque se asigne el handler después.

**Fix aplicado**: `attempt()` siempre ejecuta `img.src = img.src` después de asignar `onload`/`onerror`, forzando un reintento inmediato para imágenes en estado de error.

### 2. `<img>` y `background: url(...)` se precargan por separado

El preloader tiene DOS caminos:
- **`<img>` tags**: detectados con `container.querySelectorAll('img')`, reintentan hasta 3 veces
- **Background images**: detectados con `[style*="background:"]`, extraen URL via regex `url(...)`, crean `new Image()` con reintentos

**Problema**: Si una misma URL aparece como `<img>` y como `background` (ej: categoría con thumbnail que también es fondo de página), se precarga dos veces. No es bug funcional pero duplica requests.

### 3. `seen` dedup solo cubre background URLs

El objeto `seen` solo evita duplicados entre background images, no entre `<img>` y background. Una misma URL en ambos caminos genera dos tasks de precarga.

### 4. `img.src = img.src` puede reiniciar carga en progreso

Aunque el `load` event ya disparó, si un `<img>` por alguna razón no está completo cuando `attempt()` se ejecuta, `img.src = img.src` cancela la carga original y la reinicia. Esto es correcto pero añade latencia innecesaria.

### 5. Wasmer Filesystem efímero

En Wasmer Edge, `asset.php` rara vez encuentra la imagen en filesystem (se pierde entre restarts). Depende de DB `catalog_assets`. Si la conexión DB es lenta en un cold start, las imágenes pueden tardar en cargarse, causando timeouts en el preloader.

### 6. 20-segundos safety timeout

El timeout de seguridad (20s) fuerza `printReady = true` y habilita el botón aunque fallen todas las imágenes. El PDF se genera sin imágenes. Esto es aceptable como último recurso.

### 7. `print-page-bleed .page-content { display: none }` vs `preview-page[style*="background:"] .page-content { display: none }`

En `preview.php`, el ocultamiento de contenido se maneja con un selector `[style*="background:"]` que mira el atributo `style` inline generado por `pageBgStyle()`. En `export_pdf.php`, se usa una clase duplicada `print-page-bleed`. Ambas condiciones son equivalentes pero frágiles — si `pageBgStyle()` cambia su lógica, `export_pdf.php` no se sincroniza automáticamente.

## Resumen del estado actual

| Componente | Estado | Riesgo |
|------------|--------|--------|
| `asset.php` sirviendo imágenes | ✅ Correcto | DB connection en cold starts |
| `imageUrl()` generando URLs | ✅ Correcto | Ninguno |
| Renderer generando `<img>` tags | ✅ Correcto | Ninguno |
| Renderer generando `background:url()` | ✅ Correcto | Ninguno |
| Preloader `<img>` con reintentos | ✅ Corregido | Muy bajo |
| Preloader background con reintentos | ✅ Correcto | Muy bajo |
| Progress bar y botón | ✅ Correcto | Ninguno |
| Safety timeout 20s | ✅ Correcto | Muy bajo |
| Dedup entre `<img>` y background | ❌ No existe | Bajo (duplica requests) |
| `print-page-bleed` duplicado frágil | ⚠️ Inconsistencia | Medio si se modifica pageBgStyle() |

## Plan de corrección

### Prioridad Alta (afecta funcionalidad hoy)

1. **Consolidar la detección de imágenes duplicadas** entre `<img>` y `background:url()`. Unificar en un solo `seen` global para no precargar la misma URL dos veces.

2. **Reducir safety timeout** de 20s a 10s — tiempo suficiente para 3 retries con backoff (1s+2s+3s=6s) + buffer.

3. **Verificar que `renderPageContent()` en export_pdf.php** genera exactamente la misma estructura HTML que en preview, especialmente las `page-with-bg` con `background:url()`. Actualmente es correcto, pero un cambio futuro en el renderer podría romper export_pdf.php sin preview afectado.

### Prioridad Media (mejora)

4. **Refactorizar `export_pdf.php`** para usar el mismo selector CSS que `preview.php` (`.preview-page[style*="background:"] .page-content`) en lugar de `print-page-bleed`, eliminando la lógica PHP duplicada.

### Prioridad Baja (optimización)

5. **En los reintentos de `<img>`**, usar `img.src = img.src + '&t=' + Date.now()` para evitar que el browser sirva desde cache la misma respuesta fallida.

6. **Cachear respuestas de `asset.php`** con ETag o Last-Modified además de Cache-Control, para que los navegadores puedan validar imágenes cacheadas sin re-descargarlas.

¿Procedo con la implementación de este plan?
