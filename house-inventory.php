<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conoce House Inventory, un sistema de inventario y punto de venta para Android y Windows desarrollado por Outnet Studios.">
    <meta name="author" content="Outnet Studios">
    <meta property="og:type" content="website">
    <meta property="og:title" content="House Inventory | Caso de éxito - Outnet Studios">
    <meta property="og:description" content="Gestiona productos, ventas, facturas, clientes y proveedores con House Inventory para Android y Windows.">
    <meta property="og:image" content="https://outnetstudios.wasmer.app/img/house-inventory/dashboard-windows.png">
    <title>House Inventory | Caso de éxito - Outnet Studios</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/house-inventory.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body class="hi-page">
    <?php include __DIR__ . '/templates/partials/navbar.php'; ?>

    <main class="hi-shell">
        <section class="hi-hero">
            <div class="hi-hero__copy">
                <a class="hi-back" href="index.php">← Volver al inicio</a>
                <span class="hi-eyebrow">Caso de éxito · Outnet Studios</span>
                <h1>House Inventory</h1>
                <p class="hi-lead">Inventario y punto de venta para llevar el control de tu negocio desde la computadora o el móvil.</p>
                <div class="hi-platforms" aria-label="Plataformas disponibles">
                    <span>Android</span>
                    <span>Windows</span>
                </div>
                <a class="hi-button hi-button--whatsapp" href="https://wa.me/50585753910?text=Hola%2C%20quiero%20m%C3%A1s%20informaci%C3%B3n%20sobre%20House%20Inventory." target="_blank" rel="noopener noreferrer">Solicitar información por WhatsApp</a>
            </div>
            <figure class="hi-hero__image">
                <img src="img/house-inventory/dashboard-windows.png" alt="Panel principal de House Inventory en Windows" fetchpriority="high">
                <figcaption>Panel de control en Windows</figcaption>
            </figure>
        </section>

        <section class="hi-details" aria-labelledby="hi-details-title">
            <div class="hi-section-heading">
                <span class="hi-eyebrow">Una solución para el día a día</span>
                <h2 id="hi-details-title">Controla tu operación desde un solo lugar</h2>
                <p>House Inventory reúne las herramientas principales para administrar las operaciones de inventario y ventas de tu negocio.</p>
            </div>
            <div class="hi-features">
                <article class="hi-feature">
                    <span class="hi-feature__number">01</span>
                    <h3>Inventario organizado</h3>
                    <p>Administra productos, categorías, existencias, clientes y proveedores.</p>
                </article>
                <article class="hi-feature">
                    <span class="hi-feature__number">02</span>
                    <h3>Ventas y facturas</h3>
                    <p>Registra ventas en el punto de venta y consulta las facturas y reportes.</p>
                </article>
                <article class="hi-feature">
                    <span class="hi-feature__number">03</span>
                    <h3>En computadora o móvil</h3>
                    <p>Disponible para Windows y Android, con almacenamiento local y sincronización.</p>
                </article>
            </div>
        </section>

        <section class="hi-gallery" aria-labelledby="hi-gallery-title">
            <div class="hi-section-heading">
                <span class="hi-eyebrow">Conoce la aplicación</span>
                <h2 id="hi-gallery-title">Una mirada a House Inventory</h2>
            </div>
            <div class="hi-gallery__grid">
                <figure class="hi-shot hi-shot--wide">
                    <img src="img/house-inventory/facturacion-windows.png" alt="Pantalla de facturación y punto de venta en Windows" loading="lazy">
                    <figcaption>Facturación y punto de venta · Windows</figcaption>
                </figure>
                <figure class="hi-shot">
                    <img src="img/house-inventory/productos-windows.png" alt="Gestión de productos en House Inventory para Windows" loading="lazy">
                    <figcaption>Gestión de productos · Windows</figcaption>
                </figure>
                <figure class="hi-shot hi-shot--mobile">
                    <img src="img/house-inventory/dashboard-android.png" alt="Panel de control de House Inventory en un teléfono Android" loading="lazy">
                    <figcaption>Panel de control · Android</figcaption>
                </figure>
            </div>
        </section>

        <section class="hi-contact">
            <span class="hi-eyebrow">¿Te interesa para tu negocio?</span>
            <h2>Conoce más sobre House Inventory</h2>
            <p>Escríbenos y te contamos sobre sus funciones y disponibilidad.</p>
            <a class="hi-button hi-button--whatsapp" href="https://wa.me/50585753910?text=Hola%2C%20quiero%20m%C3%A1s%20informaci%C3%B3n%20sobre%20House%20Inventory." target="_blank" rel="noopener noreferrer">Consultar por WhatsApp</a>
        </section>
    </main>

    <?php include __DIR__ . '/templates/partials/footer.php'; ?>
    <script src="js/navbar_and_footer.js"></script>
</body>
</html>
