<?php
// Panel del administrador para consultar las alertas de stock bajo.
// Por ahora usa datos de ejemplo: todavía no consulta la base de datos.

// Fecha de "Última actualización" en español, con la hora de Baja California Sur
date_default_timezone_set('America/Mazatlan');
$monthNames = array('ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
$lastUpdate = date('d') . ' ' . $monthNames[(int) date('n') - 1] . ' ' . date('Y') . ', ' . date('H:i') . ' h';

// Datos de ejemplo: los primeros 8 son del diseño y los últimos 4 completan la página 2
$alerts = array(
    array('name' => 'Filtro de aceite universal', 'code' => 'PZA-0018', 'category' => 'Filtros', 'location' => 'Estante A · Nivel 2', 'stock' => 0, 'min' => 10, 'level' => 'Sin existencias', 'date' => '02 nov 2026', 'sort' => '20261102'),
    array('name' => 'Bujía de encendido estándar', 'code' => 'PZA-0042', 'category' => 'Encendido', 'location' => 'Estante B · Nivel 1', 'stock' => 0, 'min' => 20, 'level' => 'Sin existencias', 'date' => '02 nov 2026', 'sort' => '20261102'),
    array('name' => 'Correa de distribución', 'code' => 'PZA-0076', 'category' => 'Motor', 'location' => 'Estante C · Nivel 3', 'stock' => 0, 'min' => 8, 'level' => 'Sin existencias', 'date' => '01 nov 2026', 'sort' => '20261101'),
    array('name' => 'Balatas delanteras', 'code' => 'PZA-0025', 'category' => 'Frenos', 'location' => 'Estante A · Nivel 3', 'stock' => 1, 'min' => 10, 'level' => 'Crítico', 'date' => '02 nov 2026', 'sort' => '20261102'),
    array('name' => 'Filtro de aire para motor', 'code' => 'PZA-0053', 'category' => 'Filtros', 'location' => 'Estante A · Nivel 2', 'stock' => 2, 'min' => 12, 'level' => 'Crítico', 'date' => '01 nov 2026', 'sort' => '20261101'),
    array('name' => 'Terminal de dirección', 'code' => 'PZA-0089', 'category' => 'Suspensión', 'location' => 'Estante D · Nivel 1', 'stock' => 2, 'min' => 8, 'level' => 'Crítico', 'date' => '01 nov 2026', 'sort' => '20261101'),
    array('name' => 'Aceite de motor 5W-30 · 1 L', 'code' => 'PZA-0031', 'category' => 'Lubricantes', 'location' => 'Estante E · Nivel 1', 'stock' => 3, 'min' => 15, 'level' => 'Crítico', 'date' => '02 nov 2026', 'sort' => '20261102'),
    array('name' => 'Limpiaparabrisas de 22 pulgadas', 'code' => 'PZA-0064', 'category' => 'Accesorios', 'location' => 'Estante B · Nivel 2', 'stock' => 4, 'min' => 10, 'level' => 'Bajo', 'date' => '31 oct 2026', 'sort' => '20261031'),
    array('name' => 'Bomba de agua', 'code' => 'PZA-0102', 'category' => 'Motor', 'location' => 'Estante C · Nivel 1', 'stock' => 3, 'min' => 8, 'level' => 'Bajo', 'date' => '31 oct 2026', 'sort' => '20261031'),
    array('name' => 'Líquido de frenos', 'code' => 'PZA-0115', 'category' => 'Frenos', 'location' => 'Estante E · Nivel 2', 'stock' => 5, 'min' => 12, 'level' => 'Bajo', 'date' => '30 oct 2026', 'sort' => '20261030'),
    array('name' => 'Filtro de gasolina', 'code' => 'PZA-0121', 'category' => 'Filtros', 'location' => 'Estante A · Nivel 1', 'stock' => 6, 'min' => 14, 'level' => 'Bajo', 'date' => '30 oct 2026', 'sort' => '20261030'),
    array('name' => 'Amortiguador delantero', 'code' => 'PZA-0128', 'category' => 'Suspensión', 'location' => 'Estante D · Nivel 2', 'stock' => 2, 'min' => 6, 'level' => 'Bajo', 'date' => '29 oct 2026', 'sort' => '20261029'),
);

// Equivalencias para mostrar y ordenar cada nivel de alerta
$levelTags = array('Sin existencias' => 'tag-high', 'Crítico' => 'tag-medium', 'Bajo' => 'tag-low');
$levelRanks = array('Sin existencias' => 1, 'Crítico' => 2, 'Bajo' => 3);

// Categorías y ubicaciones sin repetir para las listas de filtros
$categories = array();
$locations = array();
foreach ($alerts as $alert) {
    $categories[$alert['category']] = true;
    $locations[$alert['location']] = true;
}
$categories = array_keys($categories);
$locations = array_keys($locations);
sort($categories);
sort($locations);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Alertas de stock bajo | Tienda</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Estilos generales y estilos de las pantallas de alertas -->
  <link href="../public/css/styles.css" rel="stylesheet">
  <link href="css/alertas.css" rel="stylesheet">
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

  <!-- Ruta de navegación, título y acciones -->
  <div class="alerts-header d-flex flex-column gap-4">
    <nav class="d-flex flex-wrap align-items-center gap-2" aria-label="Ruta de navegación">
      <a href="panelAdministrador.php" class="breadcrumb-back d-flex align-items-center gap-2 fw-medium text-decoration-none">
        <img src="../imagenes/icon-arrow-left-sm.svg" width="16" height="16" alt="">
        Inicio
      </a>
      <img src="../imagenes/icon-chevron-right-sm.svg" width="14" height="14" alt="">
      <span class="breadcrumb-current" aria-current="page">Alertas de stock bajo</span>
    </nav>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <span class="title-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-triangle-alert-white.svg" width="24" height="24" alt="">
        </span>
        <div class="d-flex flex-column gap-2">
          <h1 class="page-title fw-bold m-0">Alertas de stock bajo</h1>
          <p class="page-subtitle m-0">Consulta las piezas por debajo del stock mínimo y prioriza su reposición.</p>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-12">
        <a href="alertasStockBajo.php" class="btn btn-soft d-flex align-items-center gap-2 fw-semibold">
          <img src="../imagenes/icon-refresh.svg" width="18" height="18" alt="">
          Actualizar
        </a>
        <button type="button" class="btn btn-solid d-flex align-items-center gap-2 fw-semibold">
          <img src="../imagenes/icon-download-white.svg" width="18" height="18" alt="">
          Exportar alertas
        </button>
      </div>
    </div>
  </div>

  <main class="alerts-content d-flex flex-column gap-4">

    <!-- Tarjetas de resumen -->
    <div class="d-flex flex-wrap gap-4">
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-package-md.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key=""></span>
            <span class="summary-name fw-semibold">Alertas activas</span>
          </div>
          <span class="summary-text">Piezas que necesitan reposición</span>
        </div>
      </div>
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-circle-alert.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key="level" data-count-value="Sin existencias"></span>
            <span class="summary-name fw-semibold">Sin existencias</span>
          </div>
          <span class="summary-text">Atención inmediata · stock en cero</span>
        </div>
      </div>
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-triangle-alert-md.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key="level" data-count-value="Crítico"></span>
            <span class="summary-name fw-semibold">Stock crítico</span>
          </div>
          <span class="summary-text">Disponibilidad ≤ 25 % del mínimo</span>
        </div>
      </div>
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-trending-down.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key="level" data-count-value="Bajo"></span>
            <span class="summary-name fw-semibold">Stock bajo</span>
          </div>
          <span class="summary-text">Disponibilidad inferior al mínimo</span>
        </div>
      </div>
    </div>

    <!-- Búsqueda y filtros -->
    <div class="filters-panel d-flex flex-wrap align-items-end">
      <div class="filter-search">
        <label for="alertSearch" class="control-label fw-medium">Buscar pieza</label>
        <div class="control-box d-flex align-items-center">
          <img src="../imagenes/icon-search-sm.svg" width="18" height="18" alt="">
          <input type="search" id="alertSearch" placeholder="Buscar por nombre o código de pieza">
        </div>
      </div>
      <div class="filter-medium">
        <label for="filterCategory" class="control-label fw-medium">Categoría</label>
        <select id="filterCategory" class="form-select control-select" data-filter="category">
          <option value="">Todas las categorías</option>
          <?php foreach ($categories as $category): ?>
          <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-medium">
        <label for="filterLocation" class="control-label fw-medium">Ubicación</label>
        <select id="filterLocation" class="form-select control-select" data-filter="location">
          <option value="">Todas las ubicaciones</option>
          <?php foreach ($locations as $location): ?>
          <option value="<?php echo htmlspecialchars($location); ?>"><?php echo htmlspecialchars($location); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-small">
        <label for="alertSort" class="control-label fw-medium">Ordenar por</label>
        <select id="alertSort" class="form-select control-select">
          <option value="rank">Mayor urgencia</option>
          <option value="recent">Más recientes</option>
          <option value="name">Nombre (A–Z)</option>
        </select>
      </div>
      <button type="button" id="clearFilters" class="btn-text filter-clear fw-medium">Limpiar filtros</button>
    </div>

    <!-- Tabla de alertas -->
    <section class="d-flex flex-column gap-3">
      <div class="table-card">
        <div class="table-tabs d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="alert-tab is-active" data-key="" data-value="">Todas<span class="tab-count" data-count-key=""></span></button>
            <button type="button" class="alert-tab" data-key="level" data-value="Sin existencias">Sin existencias<span class="tab-count" data-count-key="level" data-count-value="Sin existencias"></span></button>
            <button type="button" class="alert-tab" data-key="level" data-value="Crítico">Crítico<span class="tab-count" data-count-key="level" data-count-value="Crítico"></span></button>
            <button type="button" class="alert-tab" data-key="level" data-value="Bajo">Bajo<span class="tab-count" data-count-key="level" data-count-value="Bajo"></span></button>
          </div>
          <span class="table-note">Cantidades expresadas en unidades</span>
        </div>
        <div class="table-responsive">
          <table class="alerts-table">
            <colgroup>
              <col>
              <col style="width: 160px">
              <col style="width: 220px">
              <col style="width: 120px">
              <col style="width: 120px">
              <col style="width: 130px">
              <col style="width: 180px">
              <col style="width: 150px">
              <col style="width: 134px">
            </colgroup>
            <thead>
              <tr>
                <th scope="col">Pieza / Código</th>
                <th scope="col">Categoría</th>
                <th scope="col">Ubicación</th>
                <th scope="col" class="text-center">Stock actual</th>
                <th scope="col" class="text-center">Stock mínimo</th>
                <th scope="col" class="text-center">Faltante</th>
                <th scope="col">Nivel de alerta ↓</th>
                <th scope="col">Fecha de alerta</th>
                <th scope="col">Acciones</th>
              </tr>
            </thead>
            <tbody id="alertsBody">
              <?php foreach ($alerts as $alert): ?>
              <tr class="alert-row"
                  data-name="<?php echo htmlspecialchars($alert['name']); ?>"
                  data-code="<?php echo htmlspecialchars($alert['code']); ?>"
                  data-category="<?php echo htmlspecialchars($alert['category']); ?>"
                  data-location="<?php echo htmlspecialchars($alert['location']); ?>"
                  data-level="<?php echo htmlspecialchars($alert['level']); ?>"
                  data-rank="<?php echo (int) $levelRanks[$alert['level']]; ?>"
                  data-time="<?php echo htmlspecialchars($alert['sort']); ?>">
                <td>
                  <div class="cell-name"><?php echo htmlspecialchars($alert['name']); ?></div>
                  <div class="cell-code"><?php echo htmlspecialchars($alert['code']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($alert['category']); ?></td>
                <td><?php echo htmlspecialchars($alert['location']); ?></td>
                <td class="cell-stock text-center"><?php echo (int) $alert['stock']; ?></td>
                <td class="cell-dark text-center"><?php echo (int) $alert['min']; ?></td>
                <td class="cell-dark fw-semibold text-center"><?php echo (int) ($alert['min'] - $alert['stock']); ?></td>
                <td><span class="tag <?php echo htmlspecialchars($levelTags[$alert['level']]); ?>"><span class="tag-dot"></span><?php echo htmlspecialchars($alert['level']); ?></span></td>
                <td class="cell-date"><?php echo htmlspecialchars($alert['date']); ?></td>
                <td>
                  <a href="#" class="detail-link d-inline-flex align-items-center gap-2 text-decoration-none">
                    Ver detalle
                    <img src="../imagenes/icon-chevron-right-primary.svg" width="14" height="14" alt="">
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <tr id="emptyRow" class="d-none">
                <td colspan="9" class="text-center">No hay alertas con esos filtros.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="table-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <span id="rangeText" class="pagination-text" role="status"></span>
            <label for="pageSize" class="visually-hidden">Alertas por página</label>
            <select id="pageSize" class="form-select page-size-select">
              <option value="8">8 por página</option>
              <option value="16">16 por página</option>
              <option value="24">24 por página</option>
            </select>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" id="previousPage" class="page-nav">Anterior</button>
            <div id="pageNumbers" class="d-flex gap-2"></div>
            <button type="button" id="nextPage" class="page-nav d-flex align-items-center gap-2">
              Siguiente
              <img src="../imagenes/icon-chevron-right-primary.svg" width="14" height="14" alt="">
            </button>
          </div>
        </div>
      </div>

      <div class="alerts-footnote d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="d-flex align-items-center gap-2">
          <img src="../imagenes/icon-info-xs.svg" width="16" height="16" alt="">
          Faltante: unidades necesarias para alcanzar el stock mínimo. Las alertas se resuelven al reponer existencias.
        </span>
        <span>Última actualización: <?php echo htmlspecialchars($lastUpdate); ?></span>
      </div>
    </section>
  </main>

<?php
// La página está dentro de una carpeta: la marca de agua sube un nivel para hallar su imagen.
$rootPath = '../';
require __DIR__ . '/../includes/watermark.php';
?>

  <script src="js/alertasTabla.js"></script>
</body>
</html>
