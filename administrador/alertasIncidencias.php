<?php
// Panel del administrador para consultar las alertas de incidencias.
// Por ahora usa datos de ejemplo: todavía no consulta la base de datos.

// Fecha de "Última actualización" en español, con la hora de Baja California Sur
date_default_timezone_set('America/Mazatlan');
$monthNames = array('ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
$lastUpdate = date('d') . ' ' . $monthNames[(int) date('n') - 1] . ' ' . date('Y') . ', ' . date('H:i') . ' h';

// Datos de ejemplo: los primeros 8 son del diseño y los últimos 4 completan la página 2
$incidents = array(
    array('name' => 'Batería automotriz 12 V · 60 Ah', 'code' => 'PZA-0097', 'category' => 'Sistema eléctrico', 'location' => 'Estante F · Nivel 1', 'type' => 'Fuga de electrolito', 'folio' => 'INC-0128', 'detail' => '1 unidad aislada por seguridad', 'priority' => 'Alta', 'status' => 'Pendiente', 'date' => '02 nov 2026', 'time' => '09:25', 'sort' => '202611020925'),
    array('name' => 'Balatas delanteras', 'code' => 'PZA-0025', 'category' => 'Frenos', 'location' => 'Estante A · Nivel 3', 'type' => 'Producto defectuoso', 'folio' => 'INC-0127', 'detail' => '2 juegos con material fracturado', 'priority' => 'Alta', 'status' => 'Pendiente', 'date' => '02 nov 2026', 'time' => '09:10', 'sort' => '202611020910'),
    array('name' => 'Aceite de motor 5W-30 · 1 L', 'code' => 'PZA-0031', 'category' => 'Lubricantes', 'location' => 'Estante E · Nivel 1', 'type' => 'Envase con fuga', 'folio' => 'INC-0126', 'detail' => '3 envases con sello dañado', 'priority' => 'Alta', 'status' => 'En revisión', 'date' => '02 nov 2026', 'time' => '08:45', 'sort' => '202611020845'),
    array('name' => 'Bujía de encendido estándar', 'code' => 'PZA-0042', 'category' => 'Encendido', 'location' => 'Estante B · Nivel 1', 'type' => 'Pieza dañada', 'folio' => 'INC-0125', 'detail' => '4 piezas con aislante agrietado', 'priority' => 'Media', 'status' => 'Pendiente', 'date' => '01 nov 2026', 'time' => '17:20', 'sort' => '202611011720'),
    array('name' => 'Correa de distribución', 'code' => 'PZA-0076', 'category' => 'Motor', 'location' => 'Estante C · Nivel 3', 'type' => 'Producto incorrecto', 'folio' => 'INC-0124', 'detail' => '2 piezas no coinciden con el empaque', 'priority' => 'Media', 'status' => 'En revisión', 'date' => '01 nov 2026', 'time' => '15:40', 'sort' => '202611011540'),
    array('name' => 'Filtro de aceite universal', 'code' => 'PZA-0018', 'category' => 'Filtros', 'location' => 'Estante A · Nivel 2', 'type' => 'Empaque dañado', 'folio' => 'INC-0123', 'detail' => '2 unidades revisadas y reempacadas', 'priority' => 'Media', 'status' => 'Resuelta', 'date' => '01 nov 2026', 'time' => '12:15', 'sort' => '202611011215'),
    array('name' => 'Terminal de dirección', 'code' => 'PZA-0089', 'category' => 'Suspensión', 'location' => 'Estante D · Nivel 1', 'type' => 'Accesorio faltante', 'folio' => 'INC-0122', 'detail' => '1 pieza sin tuerca de fijación', 'priority' => 'Baja', 'status' => 'En revisión', 'date' => '31 oct 2026', 'time' => '16:30', 'sort' => '202610311630'),
    array('name' => 'Limpiaparabrisas de 22 pulgadas', 'code' => 'PZA-0064', 'category' => 'Accesorios', 'location' => 'Estante B · Nivel 2', 'type' => 'Etiqueta ilegible', 'folio' => 'INC-0121', 'detail' => '3 unidades con etiqueta reemplazada', 'priority' => 'Baja', 'status' => 'Resuelta', 'date' => '31 oct 2026', 'time' => '10:05', 'sort' => '202610311005'),
    array('name' => 'Líquido de frenos', 'code' => 'PZA-0115', 'category' => 'Frenos', 'location' => 'Estante E · Nivel 2', 'type' => 'Empaque dañado', 'folio' => 'INC-0120', 'detail' => '1 envase con caja golpeada', 'priority' => 'Baja', 'status' => 'Pendiente', 'date' => '30 oct 2026', 'time' => '16:10', 'sort' => '202610301610'),
    array('name' => 'Filtro de gasolina', 'code' => 'PZA-0121', 'category' => 'Filtros', 'location' => 'Estante A · Nivel 1', 'type' => 'Etiqueta ilegible', 'folio' => 'INC-0119', 'detail' => '2 unidades sin código visible', 'priority' => 'Baja', 'status' => 'Pendiente', 'date' => '30 oct 2026', 'time' => '11:40', 'sort' => '202610301140'),
    array('name' => 'Bomba de agua', 'code' => 'PZA-0102', 'category' => 'Motor', 'location' => 'Estante C · Nivel 1', 'type' => 'Accesorio faltante', 'folio' => 'INC-0118', 'detail' => '1 pieza sin empaque de sello', 'priority' => 'Baja', 'status' => 'En revisión', 'date' => '29 oct 2026', 'time' => '15:20', 'sort' => '202610291520'),
    array('name' => 'Amortiguador delantero', 'code' => 'PZA-0128', 'category' => 'Suspensión', 'location' => 'Estante D · Nivel 2', 'type' => 'Empaque dañado', 'folio' => 'INC-0117', 'detail' => '1 pieza reempacada', 'priority' => 'Baja', 'status' => 'Resuelta', 'date' => '29 oct 2026', 'time' => '09:30', 'sort' => '202610290930'),
);

// Equivalencias para mostrar y ordenar cada prioridad y estado
$priorityTags = array('Alta' => 'tag-high', 'Media' => 'tag-medium', 'Baja' => 'tag-low');
$priorityRanks = array('Alta' => 1, 'Media' => 2, 'Baja' => 3);
$statusTags = array('Pendiente' => 'tag-low', 'En revisión' => 'tag-review', 'Resuelta' => 'tag-done');

// Tipos de incidencia sin repetir para la lista de filtros
$types = array();
foreach ($incidents as $incident) {
    $types[$incident['type']] = true;
}
$types = array_keys($types);
sort($types);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Alertas de incidencias | Tienda</title>

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
      <span class="breadcrumb-current" aria-current="page">Alertas de incidencias</span>
    </nav>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <span class="title-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-incident-title.svg" width="24" height="24" alt="">
        </span>
        <div class="d-flex flex-column gap-2">
          <h1 class="page-title fw-bold m-0">Alertas de incidencias</h1>
          <p class="page-subtitle m-0">Consulta las incidencias de tus productos, identifica su prioridad y da seguimiento a su atención.</p>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-12">
        <a href="alertasIncidencias.php" class="btn btn-soft d-flex align-items-center gap-2 fw-semibold">
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
          <img src="../imagenes/icon-incident-total.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key=""></span>
            <span class="summary-name fw-semibold">Incidencias registradas</span>
          </div>
          <span class="summary-text">Total de alertas de productos</span>
        </div>
      </div>
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-incident-pending.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key="status" data-count-value="Pendiente"></span>
            <span class="summary-name fw-semibold">Pendientes</span>
          </div>
          <span class="summary-text">En espera de atención</span>
        </div>
      </div>
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-clock-md.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key="status" data-count-value="En revisión"></span>
            <span class="summary-name fw-semibold">En revisión</span>
          </div>
          <span class="summary-text">Incidencias en seguimiento</span>
        </div>
      </div>
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-circle-check.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key="status" data-count-value="Resuelta"></span>
            <span class="summary-name fw-semibold">Resueltas</span>
          </div>
          <span class="summary-text">Atención completada</span>
        </div>
      </div>
    </div>

    <!-- Búsqueda y filtros -->
    <div class="filters-panel d-flex flex-wrap align-items-end">
      <div class="filter-search">
        <label for="alertSearch" class="control-label fw-medium">Buscar producto o incidencia</label>
        <div class="control-box d-flex align-items-center">
          <img src="../imagenes/icon-search-sm.svg" width="18" height="18" alt="">
          <input type="search" id="alertSearch" placeholder="Buscar por nombre, código de producto o folio de incidencia">
        </div>
      </div>
      <div class="filter-medium">
        <label for="filterType" class="control-label fw-medium">Tipo de incidencia</label>
        <select id="filterType" class="form-select control-select" data-filter="type">
          <option value="">Todos los tipos</option>
          <?php foreach ($types as $type): ?>
          <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-medium">
        <label for="filterPriority" class="control-label fw-medium">Prioridad</label>
        <select id="filterPriority" class="form-select control-select" data-filter="priority">
          <option value="">Todas las prioridades</option>
          <option value="Alta">Alta</option>
          <option value="Media">Media</option>
          <option value="Baja">Baja</option>
        </select>
      </div>
      <div class="filter-small">
        <label for="alertSort" class="control-label fw-medium">Ordenar por</label>
        <select id="alertSort" class="form-select control-select">
          <option value="rank">Mayor prioridad</option>
          <option value="recent">Más recientes</option>
          <option value="name">Nombre (A–Z)</option>
        </select>
      </div>
      <button type="button" id="clearFilters" class="btn-text filter-clear fw-medium">Limpiar filtros</button>
    </div>

    <!-- Tabla de incidencias -->
    <section class="d-flex flex-column gap-3">
      <div class="table-card">
        <div class="table-tabs d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="alert-tab is-active" data-key="" data-value="">Todas<span class="tab-count" data-count-key=""></span></button>
            <button type="button" class="alert-tab" data-key="status" data-value="Pendiente">Pendientes<span class="tab-count" data-count-key="status" data-count-value="Pendiente"></span></button>
            <button type="button" class="alert-tab" data-key="status" data-value="En revisión">En revisión<span class="tab-count" data-count-key="status" data-count-value="En revisión"></span></button>
            <button type="button" class="alert-tab" data-key="status" data-value="Resuelta">Resueltas<span class="tab-count" data-count-key="status" data-count-value="Resuelta"></span></button>
          </div>
          <span class="table-note"><span data-count-key="active" data-count-value="yes"></span> incidencias activas · <span data-count-key="activeHigh" data-count-value="yes"></span> de prioridad alta</span>
        </div>
        <div class="table-responsive">
          <table class="alerts-table">
            <colgroup>
              <col>
              <col style="width: 140px">
              <col style="width: 190px">
              <col style="width: 350px">
              <col style="width: 140px">
              <col style="width: 160px">
              <col style="width: 180px">
              <col style="width: 134px">
            </colgroup>
            <thead>
              <tr>
                <th scope="col">Producto / Código</th>
                <th scope="col">Categoría</th>
                <th scope="col">Ubicación</th>
                <th scope="col">Incidencia / Folio</th>
                <th scope="col">Prioridad ↓</th>
                <th scope="col">Estado</th>
                <th scope="col">Fecha de reporte</th>
                <th scope="col">Acciones</th>
              </tr>
            </thead>
            <tbody id="alertsBody" data-rows-name="incidencias">
              <?php foreach ($incidents as $incident): ?>
              <?php $isActive = $incident['status'] !== 'Resuelta'; ?>
              <tr class="alert-row"
                  data-name="<?php echo htmlspecialchars($incident['name']); ?>"
                  data-code="<?php echo htmlspecialchars($incident['code']); ?>"
                  data-folio="<?php echo htmlspecialchars($incident['folio']); ?>"
                  data-type="<?php echo htmlspecialchars($incident['type']); ?>"
                  data-priority="<?php echo htmlspecialchars($incident['priority']); ?>"
                  data-status="<?php echo htmlspecialchars($incident['status']); ?>"
                  data-active="<?php echo $isActive ? 'yes' : 'no'; ?>"
                  data-active-high="<?php echo ($isActive && $incident['priority'] === 'Alta') ? 'yes' : 'no'; ?>"
                  data-rank="<?php echo (int) $priorityRanks[$incident['priority']]; ?>"
                  data-time="<?php echo htmlspecialchars($incident['sort']); ?>">
                <td>
                  <div class="cell-name"><?php echo htmlspecialchars($incident['name']); ?></div>
                  <div class="cell-code"><?php echo htmlspecialchars($incident['code']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($incident['category']); ?></td>
                <td><?php echo htmlspecialchars($incident['location']); ?></td>
                <td>
                  <div class="cell-incident"><?php echo htmlspecialchars($incident['type'] . ' · ' . $incident['folio']); ?></div>
                  <div class="cell-code"><?php echo htmlspecialchars($incident['detail']); ?></div>
                </td>
                <td><span class="tag <?php echo htmlspecialchars($priorityTags[$incident['priority']]); ?>"><span class="tag-dot"></span><?php echo htmlspecialchars($incident['priority']); ?></span></td>
                <td><span class="tag <?php echo htmlspecialchars($statusTags[$incident['status']]); ?>"><?php echo htmlspecialchars($incident['status']); ?></span></td>
                <td>
                  <div class="cell-date"><?php echo htmlspecialchars($incident['date']); ?></div>
                  <div class="cell-time"><?php echo htmlspecialchars($incident['time']); ?> h</div>
                </td>
                <td>
                  <a href="#" class="detail-link d-inline-flex align-items-center gap-2 text-decoration-none">
                    Ver detalle
                    <img src="../imagenes/icon-chevron-right-primary.svg" width="14" height="14" alt="">
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <tr id="emptyRow" class="d-none">
                <td colspan="8" class="text-center">No hay incidencias con esos filtros.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="table-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <span id="rangeText" class="pagination-text" role="status"></span>
            <label for="pageSize" class="visually-hidden">Incidencias por página</label>
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
          <img src="../imagenes/icon-incident-info.svg" width="16" height="16" alt="">
          Activas: pendientes y en revisión. Consulta el detalle para ver el seguimiento y las acciones realizadas.
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
