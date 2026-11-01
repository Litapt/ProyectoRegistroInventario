<?php
// Pantalla principal del administrador: se muestra después de iniciar sesión.
// IMPORTANTE: todavía no revisa si el usuario inició sesión de verdad.
// Eso se agregará cuando exista la tabla de usuarios en la base de datos.

require_once __DIR__ . '/includes/stock_alerts.php';

// Cuántas alertas de stock bajo hay (si es 0, no se muestra el aviso)
$lowStockCount = countLowStockAlerts();

// Botones de cada sección: texto, ícono y a dónde llevan (todavía a ningún lado).
$commercialActions = [
    ['label' => 'Ventas',      'icon' => 'icon-cart.svg',    'href' => '#'],
    ['label' => 'Clientes',    'icon' => 'icon-user.svg',    'href' => '#'],
    ['label' => 'Existencias', 'icon' => 'icon-package.svg', 'href' => 'registroExistencias.php'],
];

$auditActions = [
    ['label' => 'Historial',     'icon' => 'icon-clock.svg',    'href' => '#'],
    ['label' => 'Configuración', 'icon' => 'icon-settings.svg', 'href' => '#'],
];

// Dibuja un botón grande (tarjeta) del panel
function renderActionCard(array $action): void
{
    $label = htmlspecialchars($action['label']);
    $icon = htmlspecialchars($action['icon']);
    $href = htmlspecialchars($action['href']);
    echo <<<HTML
          <a href="{$href}" class="action-card d-flex gap-3 bg-white text-decoration-none">
            <span class="action-icon d-flex align-items-center justify-content-center align-self-center flex-shrink-0">
              <img src="public/img/{$icon}" width="24" height="24" alt="">
            </span>
            <span class="action-label fw-semibold">{$label}</span>
          </a>

HTML;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel principal | Luna headlights</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Nuestros estilos propios -->
  <link href="public/css/styles.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- Barra de menú superior -->
  <header class="app-header d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom bg-white">
    <a href="panelAdministrador.php" class="d-flex align-items-center gap-3 text-decoration-none">
      <span class="logo-box d-flex align-items-center justify-content-center">
        <img src="public/img/logo.svg" width="20" height="20" alt="">
      </span>
      <span class="brand-name fw-bold">Luna headlights</span>
    </a>
    <nav class="d-flex flex-wrap align-items-center gap-4">
      <a href="#" class="header-link fw-medium text-decoration-none">Ayuda</a>
      <a href="#" class="header-link fw-medium text-decoration-none">Configuración de la aplicación</a>
      <a href="index.php" class="header-link fw-medium text-decoration-none">Cerrar sesión</a>
    </nav>
  </header>

  <main class="panel-main flex-grow-1">

    <!-- Barra de notificaciones: solo aparece si hay alertas de stock bajo -->
<?php if ($lowStockCount > 0): ?>
    <div class="notice-bar d-flex align-items-center gap-3 bg-white">
      <img src="public/img/icon-warning.svg" width="20" height="20" alt="" class="flex-shrink-0">
      <p class="notice-text fw-medium flex-grow-1 m-0">Tienes alertas de stock bajo</p>
      <a href="#" class="btn btn-app-primary btn-notice fw-semibold flex-shrink-0">Ir a alertas</a>
    </div>
<?php endif; ?>

    <!-- Panel de acciones (si no hay aviso, sube y ocupa su lugar) -->
    <div class="actions-panel d-flex flex-column<?= $lowStockCount > 0 ? '' : ' mt-0' ?>">

      <!-- Sección: Operaciones comerciales -->
      <section class="panel-section d-flex flex-column gap-3">
        <div class="d-flex align-items-center gap-3">
          <span class="section-icon d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="public/img/icon-cart-white.svg" width="20" height="20" alt="">
          </span>
          <h2 class="section-title fw-bold m-0">Operaciones comerciales</h2>
        </div>
        <div class="d-flex flex-wrap gap-4">
<?php foreach ($commercialActions as $action) { renderActionCard($action); } ?>
        </div>
      </section>

      <!-- Línea divisoria -->
      <hr class="panel-divider m-0">

      <!-- Sección: Auditoría y configuración -->
      <section class="panel-section d-flex flex-column gap-3">
        <div class="d-flex align-items-center gap-3">
          <span class="section-icon d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="public/img/icon-clock-white.svg" width="20" height="20" alt="">
          </span>
          <h2 class="section-title fw-bold m-0">Auditoría y configuración</h2>
        </div>
        <div class="d-flex flex-wrap gap-4">
<?php foreach ($auditActions as $action) { renderActionCard($action); } ?>
        </div>
      </section>

    </div>
  </main>

<?php require __DIR__ . '/includes/watermark.php'; ?>

</body>
</html>
