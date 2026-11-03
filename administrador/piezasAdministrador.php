<?php
// Pantalla de existencias del administrador: gestión de piezas.
// Por ahora solo muestra la interfaz: todavía no revisa quién inició sesión
// ni consulta las alertas de stock en la base de datos.

// Controla el aviso de stock bajo: true lo muestra y false lo oculta.
// Es un valor temporal; más adelante deberá calcularse con los datos de la base de datos.
$hasLowStockAlerts = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Existencias | Tienda</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Estilos generales y estilos que solo usa la interfaz del administrador -->
  <link href="../public/css/styles.css" rel="stylesheet">
  <link href="css/panelAdministrador.css" rel="stylesheet">
</head>
<body class="bg-white min-vh-100">

  <!-- Barra de menú superior -->
  <header class="app-header d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3 bg-white">
    <!-- El logo regresa a la ventana principal del administrador -->
    <a href="panelAdministrador.php" class="d-flex align-items-center gap-12 text-decoration-none">
      <span class="logo-box d-flex align-items-center justify-content-center">
        <img src="../imagenes/logo.svg" width="20" height="20" alt="">
      </span>
      <span class="brand-name fw-bold">Tienda</span>
    </a>
    <nav class="d-flex flex-wrap align-items-center gap-4" aria-label="Menú de la cuenta">
      <a href="#" class="header-link fw-medium text-decoration-none">Ayuda</a>
      <a href="#" class="header-link fw-medium text-decoration-none">Configuración de la aplicación</a>
      <a href="../index.php" class="header-link fw-medium text-decoration-none">Cerrar sesión</a>
    </nav>
  </header>

  <main class="page-content">

    <?php if ($hasLowStockAlerts): ?>
    <!-- Barra de notificaciones: avisa de las alertas de stock bajo -->
    <div class="notification-bar d-flex align-items-center gap-12 bg-white">
      <img src="../imagenes/icon-warning.svg" width="20" height="20" alt="">
      <p class="notification-text fw-medium flex-grow-1 m-0">Tienes alertas de stock bajo</p>
      <a href="registrarAlerta.php" class="btn btn-alerts fw-semibold text-nowrap flex-shrink-0">Ir a alertas</a>
    </div>
    <?php endif; ?>

    <!-- Panel de acciones: agrupa los botones por tema -->
    <div class="actions-panel d-flex flex-column gap-4 mx-auto">

      <section class="action-section d-flex flex-column gap-3">
        <div class="d-flex align-items-center gap-12">
          <span class="section-icon d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="../imagenes/icon-package-section.svg" width="20" height="20" alt="">
          </span>
          <h1 class="section-title fw-bold m-0">Gestión de piezas</h1>
        </div>
        <div class="d-flex flex-wrap gap-4">
          <a href="registrarPieza.php" class="action-card d-flex gap-3 bg-white text-decoration-none">
            <span class="action-icon d-flex align-items-center justify-content-center flex-shrink-0 align-self-center">
              <img src="../imagenes/icon-plus.svg" width="24" height="24" alt="">
            </span>
            <span class="action-label fw-semibold">Registrar nuevos productos</span>
          </a>
          <a href="consultarPiezas.php" class="action-card d-flex gap-3 bg-white text-decoration-none">
            <span class="action-icon d-flex align-items-center justify-content-center flex-shrink-0 align-self-center">
              <img src="../imagenes/icon-search.svg" width="24" height="24" alt="">
            </span>
            <span class="action-label fw-semibold">Consultar piezas</span>
          </a>
        </div>
      </section>

      <hr class="section-divider m-0">

      <section class="action-section d-flex flex-column gap-3">
        <div class="d-flex align-items-center gap-12">
          <span class="section-icon d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="../imagenes/icon-box-section.svg" width="20" height="20" alt="">
          </span>
          <h2 class="section-title fw-bold m-0">Gestión como almacenista</h2>
        </div>
        <div class="d-flex flex-wrap gap-4">
          <a href="alertasIncidencias.php" class="action-card d-flex gap-3 bg-white text-decoration-none">
            <span class="action-icon d-flex align-items-center justify-content-center flex-shrink-0 align-self-center">
              <img src="../imagenes/icon-alert-triangle.svg" width="24" height="24" alt="">
            </span>
            <span class="action-label fw-semibold">Revisar incidencias</span>
          </a>
          <a href="registrarAlerta.php" class="action-card d-flex gap-3 bg-white text-decoration-none">
            <span class="action-icon d-flex align-items-center justify-content-center flex-shrink-0 align-self-center">
              <img src="../imagenes/icon-bell.svg" width="24" height="24" alt="">
            </span>
            <span class="action-label fw-semibold">Revisar alertas de stock bajo</span>
          </a>
        </div>
      </section>

      <hr class="section-divider m-0">

    </div>
  </main>

<?php
// La página está dentro de una carpeta: la marca de agua sube un nivel para hallar su imagen.
$rootPath = '../';
require __DIR__ . '/../includes/watermark.php';
?>

</body>
</html>
