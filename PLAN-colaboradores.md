# Plan: Colaboradores de Catálogo

## 1. Resumen

Agregar un sistema de **colaboradores** que permite al dueño de un catálogo invitar a otros usuarios de `catalog_users` a acceder y editar selectivamente partes del catálogo. Un solo nivel de acceso (colaborador), con permisos granulares por sección.

---

## 2. Estado Actual

### Modelo de ownership
- Cada catálogo tiene un `user_id` (dueño).
- Cada CRUD verifica `$catalog['user_id'] === $userId` — solo el dueño accede.
- `CatalogRepository::allByUser($userId)` solo devuelve catálogos del dueño.

### Tablas existentes relevantes
- `catalog_users` — usuarios del sistema de catálogos (con `status`, `must_change_password`).
- `catalogs` — catálogos (con `user_id` FK → `catalog_users`).
- `catalog_pages`, `catalog_categories`, `catalog_products` — todas con `user_id` del creador.

### Secciones editables del catálogo
| Módulo | Archivos | Tipo |
|--------|----------|------|
| Config. catálogo | `edit.php`, `delete.php`, `catalog_update.php` | Edición |
| Páginas | `pages.php`, `page_create.php`, `page_edit.php`, `page_delete.php` | CRUD |
| Productos | `products.php`, `product_create.php`, `product_edit.php`, `product_delete.php` | CRUD |
| Categorías | `categories.php`, `category_create.php`, `category_edit.php`, `category_delete.php` | CRUD |
| Dashboard | `index.php` | Solo lectura |
| Vista previa | `preview.php` | Solo lectura |
| Exportar PDF | `export_pdf.php` | Solo lectura |
| Crear catálogo | `create.php`, `save.php` | Solo dueño |

---

## 3. Lo que hace falta

### 3.1 Base de datos — Nueva tabla

```sql
CREATE TABLE catalog_collaborators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    catalog_id INT NOT NULL,
    user_id INT NOT NULL,
    permissions JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_collab (catalog_id, user_id),
    INDEX idx_catalog (catalog_id),
    INDEX idx_user (user_id)
    -- FK: catalog_id → catalogs(id) ON DELETE CASCADE
    -- FK: user_id → catalog_users(id) ON DELETE CASCADE
);
```

### 3.2 Permisos (guardados como JSON)

```json
{
    "edit_catalog": false,
    "edit_pages": true,
    "edit_products": true,
    "edit_categories": false
}
```

| Permiso | Controla |
|---------|----------|
| `edit_catalog` | Editar nombre, slug, descripción, imágenes de portada, moneda, estado, PDF toggle, eliminar catálogo |
| `edit_pages` | CRUD de páginas (crear, editar, reordenar, eliminar) |
| `edit_products` | CRUD de productos |
| `edit_categories` | CRUD de categorías |

### 3.3 Nueva lógica de permisos

Se necesita un helper centralizado, por ejemplo en `includes/catalog_permissions.php`:

```php
function catalogGetAccessLevel(int $catalogId, int $userId): string {
    // 'owner' si $userId === dueño
    // 'collaborator' si está en catalog_collaborators con al menos un permiso true
    // 'none' si no tiene acceso
}

function catalogCanEdit(int $catalogId, int $userId, string $section): bool {
    // true si es owner
    // true si es collaborator y tiene el permiso $section = true
    // false en otro caso
}
```

### 3.4 Archivos a modificar (mapeo completo)

#### A) Repositorio nuevo
- `src/Repositories/CollaboratorRepository.php` — CRUD para `catalog_collaborators`

#### B) Helper de permisos (nuevo)
- `includes/catalog_permissions.php` — `catalogGetAccessLevel()`, `catalogCanEdit()`, helper de sesión para saber si el usuario actual es owner/collaborator

#### C) CatalogRepository
- `allByUser()` → renombrar/ampliar a `allAccessibleByUser($userId)` que devuelva catálogos donde es owner + donde es collaborator, con indicador `_access_level` ('owner' | 'collaborator')

#### D) Cada archivo de `catalog_admin/` con verificación de ownership

Actual: `if (!$catalog || (int)$catalog['user_id'] !== $userId) { ... exit; }`

Nuevo: cambiar según el módulo:

| Archivo | Permiso requerido | Notas |
|---------|-------------------|-------|
| `index.php` | `allAccessibleByUser()` | Muestra catálogos donde es owner o collaborator |
| `preview.php` (admin) | Verificar que `userId` es owner o collaborator | Sin permiso específico, solo ver |
| `export_pdf.php` (admin) | Verificar que `userId` es owner o collaborator | Sin permiso específico, solo ver |
| `edit.php` | `edit_catalog` | Incluye delete link si es owner |
| `catalog_update.php` | `edit_catalog` | |
| `delete.php` | `edit_catalog` + solo owner | Solo el dueño puede eliminar |
| `pages.php` | `edit_pages` | Ocultar botones "Crear", "Editar", "Borrar" si no tiene permiso |
| `page_create.php` | `edit_pages` | |
| `page_edit.php` | `edit_pages` | |
| `page_delete.php` | `edit_pages` | |
| `categories.php` | `edit_categories` | Ocultar botones si no tiene permiso |
| `category_create.php` | `edit_categories` | |
| `category_edit.php` | `edit_categories` | |
| `category_delete.php` | `edit_categories` | |
| `products.php` | `edit_products` | Ocultar botones si no tiene permiso |
| `product_create.php` | `edit_products` | |
| `product_edit.php` | `edit_products` | |
| `product_delete.php` | `edit_products` | |
| `create.php` / `save.php` | Solo owner | Los colaboradores NO pueden crear catálogos |

#### E) Navegación condicional

El header/navbar debe ocultar botones de edición según permisos:
- `preview.php` — botón "Editar" (pages.php) solo si `edit_pages`
- `index.php` — tarjetas deben mostrar/ocultar acciones según permisos del catálogo
- `pages.php` — botón "Crear página" solo si `edit_pages`
- `categories.php` — botón "Crear categoría" solo si `edit_categories`
- `products.php` — botón "Crear producto" solo si `edit_products`
- `edit.php` — enlace solo si `edit_catalog`

#### F) Gestión de colaboradores (nueva UI)

Opción recomendada: agregar sección en `edit.php` o crear `collaborators.php?catalog_id=X`.

- Lista de colaboradores actuales con sus permisos
- Formulario para agregar: input de email → busca en `catalog_users` → si existe, agrega con checkboxes de permisos
- Botón para remover colaborador
- Checkboxes para togglear permisos (guardar inline o con botón "Guardar cambios")

Sugerencia: integrarlo al final del formulario de `edit.php` para mantener todo en un solo lugar.

---

## 4. Estrategia de implementación (orden sugerido)

### Fase 1 — Base
1. Migración DB: `setup_collaborators_db.php` con la nueva tabla
2. `CollaboratorRepository.php` (CRUD básico)
3. `includes/catalog_permissions.php` (helper centralizado)

### Fase 2 — Backend permisos
4. Modificar `CatalogRepository::allAccessibleByUser()`
5. Actualizar `index.php` para mostrar catálogos como colaborador con badge
6. Actualizar `preview.php` (admin) y `export_pdf.php` (admin) con verificación de acceso

### Fase 3 — CRUD condicional por sección
7. Actualizar archivos de catálogo (edit, delete)
8. Actualizar archivos de páginas
9. Actualizar archivos de productos
10. Actualizar archivos de categorías

### Fase 4 — UI colaboradores
11. Agregar gestión de colaboradores en `edit.php`
12. Ocultar/mostrar botones de navegación según permisos

### Fase 5 — Pulido
13. Traducciones/labels consistentes
14. Pruebas

---

## 5. Riesgos y consideraciones

- **Migración de datos**: la tabla nueva no afecta datos existentes. Los catálogos existentes siguen siendo 100% funcionales.
- **Rendimiento**: la verificación de permisos agrega 1 query extra por página. Es marginal.
- **`user_id` en tablas hijas**: al crear páginas/productos/categorías como colaborador, ¿qué `user_id` se guarda? Opciones:
  - a) El ID del colaborador (consistente con la FK actual, pero pierdes el rastro del dueño)
  - b) El ID del dueño (el colaborador actúa en nombre del dueño)
  
  **Recomendación**: (b) usar el `user_id` del dueño al crear/editar registros como colaborador. Así las tablas hijas siempre apuntan al dueño. Si se quiere auditoría, se puede agregar columna `created_by` opcional.
- **Admin bridge** (`acceso_catalogo.php`): el super-admin ya hereda la sesión del dueño vía bridge → tiene acceso completo automáticamente. Sin cambios necesarios.
- **Eliminación de colaborador**: al removerlo, debe perder acceso inmediato. La verificación es en cada request contra DB, así que es inmediato.

---

## 6. Resumen de cambios por archivo

| Archivo | Cambio |
|---------|--------|
| `setup_collaborators_db.php` | (NUEVO) Crea `catalog_collaborators` |
| `src/Repositories/CollaboratorRepository.php` | (NUEVO) CRUD colaboradores |
| `includes/catalog_permissions.php` | (NUEVO) Helpers de permiso |
| `src/Repositories/CatalogRepository.php` | Agregar `allAccessibleByUser()` |
| `catalog_admin/index.php` | Mostrar catálogos como collaborator, badge, acciones condicionales |
| `catalog_admin/preview.php` | Verificar owner o collaborator |
| `catalog_admin/export_pdf.php` | Verificar owner o collaborator |
| `catalog_admin/edit.php` | Verificar `edit_catalog` + gestión de colaboradores |
| `catalog_admin/delete.php` | Solo owner |
| `catalog_admin/catalog_update.php` | Verificar `edit_catalog` |
| `catalog_admin/pages.php` | Verificar `edit_pages`, ocultar botones si no |
| `catalog_admin/page_create.php` | Verificar `edit_pages` |
| `catalog_admin/page_edit.php` | Verificar `edit_pages` |
| `catalog_admin/page_delete.php` | Verificar `edit_pages` |
| `catalog_admin/categories.php` | Verificar `edit_categories` |
| `catalog_admin/category_create.php` | Verificar `edit_categories` |
| `catalog_admin/category_edit.php` | Verificar `edit_categories` |
| `catalog_admin/category_delete.php` | Verificar `edit_categories` |
| `catalog_admin/products.php` | Verificar `edit_products` |
| `catalog_admin/product_create.php` | Verificar `edit_products` |
| `catalog_admin/product_edit.php` | Verificar `edit_products` |
| `catalog_admin/product_delete.php` | Verificar `edit_products` |
