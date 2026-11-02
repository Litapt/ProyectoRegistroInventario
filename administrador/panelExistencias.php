<?php
// Panel de existencias (en Figma: "Pantalla principal almacenista").
// Se llega aquí con el botón "Existencias" del panel del administrador.
// Reúne todas las acciones sobre las piezas del inventario.

require_once __DIR__ . '/../includes/stock_alerts.php';
require_once __DIR__ . '/../includes/action_card.php';

// Cuántas alertas de stock bajo hay (si es 0, no se muestra el aviso)
$lowStockCount = countLowStockAlerts();

// Secciones del panel: título, ícono blanco del encabezado y sus botones.
// Por ahora solo los 3 primeros botones (nuevos productos, entrada y salida) llevan a una pantalla real.
$sections = [
    [
        'title'   => 'Gestión de piezas',
        'icon'    => 'icon-package-white.svg',
        'actions' => [
            ['label' => 'Registrar nuevos productos',    'icon' => 'icon-plus.svg',            'href' => 'registroExistencias.php'],
            ['label' => 'Registrar entrada de producto', 'icon' => 'icon-arrow-down-left.svg', 'href' => 'registroEntrada.php'],
            ['label' => 'Registrar salida de producto',  'icon' => 'icon-arrow-up-right.svg',  'href' => 'registroSalida.php'],
            ['label' => 'Editar producto registrado',    'icon' => 'icon-edit.svg',            'href' => '#'],
        ],
    ],
    [
        'title'   => 'Gestión de producto',
        'icon'    => 'icon-box-white.svg',
        'actions' => [
            ['label' => 'Registrar incidencias',          'icon' => 'icon-alert-triangle.svg', 'href' => '#'],
            ['label' => 'Enviar alertas de stock mínimo', 'icon' => 'icon-bell-primary.svg',   'href' => '#'],
        ],
    ],
    [
        'title'   => 'Consultas y eliminación',
        'icon'    => 'icon-search-white.svg',
        'actions' => [
            ['label' => 'Consultar piezas', 'icon' => 'icon-search.svg', 'href' => '#'],
            ['label' => 'Eliminar piezas',  'icon' => 'icon-trash.svg',  'href' => '#'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Existencias | Luna headlights</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Nuestros estilos propios -->
  <link href="../public/css/styles.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- Barra de menú superior -->
  <header class="app-header d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom bg-white">
    <a href="panelAdministrador.php" class="d-flex align-items-center gap-3 text-decoration-none">
      <span class="logo-box d-flex align-items-center justify-content-center">
        <img src="../public/img/logo.svg" width="20" height="20" alt="">
      </span>
      <span class="brand-name fw-bold">Luna headlights</span>
    </a>
    <nav class="d-flex flex-wrap align-items-center gap-4">
      <a href="#" class="header-link fw-medium text-decoration-none">Ayuda</a>
      <a href="#" class="header-link fw-medium text-decoration-none">Configuración de la aplicación</a>
      <a href="../index.php" class="header-link fw-medium text-decoration-none">Cerrar sesión</a>
    </nav>
  </header>

  <main class="panel-main flex-grow-1">

    <!-- Barra de notificaciones: solo aparece si hay alertas de stock bajo -->
<?php if ($lowStockCount > 0): ?>
    <div class="notice-bar d-flex align-items-center gap-3 bg-white">
      <img src="../public/img/icon-warning.svg" width="20" height="20" alt="" class="flex-shrink-0">
      <p class="notice-text fw-medium flex-grow-1 m-0">Tienes alertas de stock bajo</p>
      <a href="#" class="btn btn-app-primary btn-notice fw-semibold flex-shrink-0">Ir a alertas</a>
    </div>
<?php endif; ?>

    <!-- Panel de acciones (si no hay aviso, sube y ocupa su lugar) -->
    <div class="actions-panel d-flex flex-column<?= $lowStockCount > 0 ? '' : ' mt-0' ?>">
<?php foreach ($sections as $index => $section): ?>
<?php if ($index > 0): ?>

      <!-- Línea divisoria -->
      <hr class="panel-divider m-0">
<?php endif; ?>

      <!-- Sección: <?= htmlspecialchars($section['title']) ?> -->
      <section class="panel-section d-flex flex-column gap-3">
        <div class="d-flex align-items-center gap-3">
          <span class="section-icon d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="../public/img/<?= htmlspecialchars($section['icon']) ?>" width="20" height="20" alt="">
          </span>
          <h2 class="section-title fw-bold m-0"><?= htmlspecialchars($section['title']) ?></h2>
        </div>
        <div class="d-flex flex-wrap gap-4">
<?php foreach ($section['actions'] as $action) { renderActionCard($action); } ?>
        </div>
      </section>
<?php endforeach; ?>

    </div>
  </main>

<?php require __DIR__ . '/../includes/watermark.php'; ?>

</body>
</html>
