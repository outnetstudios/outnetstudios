# Plan: Reemplazar dropdown de producto por buscador + lista seleccionable

## Problema

El formulario de creación/edición de páginas tipo "producto individual" usa un `<select>` con todos los productos. Esto falla cuando hay muchos productos (la lista es inmanejable), no hay forma de buscar, y en mobile es difícil de usar.

## Propuesta

Reemplazar el `<select id="product_id">` por un componente de **buscador + tarjetas seleccionables**:

```
[ Buscar producto...          ]
┌──────────────────────────────┐
│ ┌────┐  Producto A    ●○○○○  │  ← card seleccionada (checked)
│ │img │  SKU-001              │
│ └────┘                       │
├──────────────────────────────┤
│ ┌────┐  Producto B           │  ← card no seleccionada
│ │img │  SKU-002   C$150.00   │
│ └────┘                       │
├──────────────────────────────┤
│ ┌────┐  Producto C           │
│ │img │  SKU-003              │
│ └────┘                       │
└──────────────────────────────┘
```

## Cambios necesarios

### page_create.php
1. Reemplazar `#field-product select` por un input de búsqueda + contenedor de tarjetas
2. Pasar todos los productos a JS embebido (JSON) para filtrar del lado del cliente
3. JS: filtrar la lista de productos mientras se escribe en el buscador
4. Al hacer clic en una card, seleccionarla (efecto visual + hidden input con el ID)
5. Incluir Mobile (768px): cards en vertical con ancho completo
6. Incluir Desktop (>768px): rejilla de 2 columnas para las cards

### page_edit.php
1. Mismo reemplazo, pero preseleccionando la card del producto ya guardado
2. Asegurar `require_once` de `ProductRepository.php`

### CSS compartido (inline en cada página)
- Cards con hover y estado `selected` (borde primary + check)
- Scroll en el contenedor de resultados si hay muchos productos
- Buscador con icono de lupa

## UX esperado

| Estado | Comportamiento |
|--------|---------------|
| Sin productos en el catálogo | Mensaje: "No hay productos. Crea productos primero." |
| Buscador vacío | Muestra todos los productos |
| Escribir en buscador | Filtra por nombre, SKU o precio en tiempo real |
| Click en card | Selecciona, resalta con borde + check, guarda ID en hidden input |
| Edición | Card del producto actual preseleccionada |
