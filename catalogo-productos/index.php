<?php
// Página de acceso al catálogo de productos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Catálogo de productos - Outnet Studios</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        body { font-family: Inter, sans-serif; margin: 0; padding: 2rem; background: #f5f7fb; color: #172b4d; }
        .page-shell { max-width: 960px; margin: 0 auto; }
        .hero { padding: 2rem; background: #ffffff; border-radius: 18px; box-shadow: 0 24px 64px rgba(15, 23, 42, 0.08); }
        .hero h1 { margin-top: 0; }
        .links { display: grid; gap: 1rem; margin-top: 1.5rem; }
        .card { padding: 1.25rem 1.5rem; background: white; border: 1px solid #e2e8f0; border-radius: 14px; text-decoration: none; color: #102a43; transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12); }
        .card small { display: block; color: #64748b; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="page-shell">
        <div class="hero">
            <p><a href="/" style="color:#1d4ed8;text-decoration:none;">← Volver a Outnet Studios</a></p>
            <h1>Catálogo de productos</h1>
            <p>Esta sección agrupa el módulo de catálogo y las herramientas de administración que ya estamos construyendo.</p>
            <div class="links">
                <a class="card" href="/catalog_auth/login.php">
                    Iniciar sesión en catálogo
                    <small>Accede con usuarios del catálogo para gestionar tus catálogos.</small>
                </a>
                <a class="card" href="/catalog_admin/index.php">
                    Panel de administración de catálogo
                    <small>Lista y crea catálogos desde el módulo admin.</small>
                </a>
                <a class="card" href="/adminontc05/index.php">
                    Panel administrativo principal
                    <small>Acceso al dashboard administrativo de Outnet Studios.</small>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
