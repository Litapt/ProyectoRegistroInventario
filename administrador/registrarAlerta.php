<?php
// Pantalla del administrador para registrar alertas de stock bajo.
// Por ahora usa datos de ejemplo: las alertas nuevas solo se agregan a la tabla
// de esta pantalla y no se guardan en la base de datos.

// Fecha de "Última actualización" en español, con la hora de Baja California Sur
date_default_timezone_set('America/Mazatlan');
$monthNames = array('ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
$lastUpdate = date('d') . ' ' . $monthNames[(int) date('n') - 1] . ' ' . date('Y') . ', ' . date('H:i') . ' h';

// Datos de ejemplo tomados del diseño; más adelante vendrán de la base de datos
$alerts = array(
    array('name' => 'Batería automotriz 12 V · 60 Ah', 'code' => 'PZA-0097', 'category' => 'Sistema eléctrico', 'location' => 'Estante F · Nivel 1', 'stock' => 2, 'min' => 8, 'unit' => 'unidades', 'priority' => 'Alta', 'status' => 'Pendiente', 'date' => '03 nov 2026', 'time' => '09:25', 'sort' => '202611030925'),
    array('name' => 'Balatas delanteras', 'code' => 'PZA-0025', 'category' => 'Frenos', 'location' => 'Estante A · Nivel 3', 'stock' => 3, 'min' => 10, 'unit' => 'juegos', 'priority' => 'Alta', 'status' => 'Pendiente', 'date' => '03 nov 2026', 'time' => '09:10', 'sort' => '202611030910'),
    array('name' => 'Aceite de motor 5W-30 · 1 L', 'code' => 'PZA-0031', 'category' => 'Lubricantes', 'location' => 'Estante E · Nivel 1', 'stock' => 6, 'min' => 20, 'unit' => 'envases', 'priority' => 'Alta', 'status' => 'En revisión', 'date' => '03 nov 2026', 'time' => '08:45', 'sort' => '202611030845'),
    array('name' => 'Bujía de encendido estándar', 'code' => 'PZA-0042', 'category' => 'Encendido', 'location' => 'Estante B · Nivel 1', 'stock' => 8, 'min' => 24, 'unit' => 'piezas', 'priority' => 'Media', 'status' => 'Pendiente', 'date' => '02 nov 2026', 'time' => '17:20', 'sort' => '202611021720'),
    array('name' => 'Correa de distribución', 'code' => 'PZA-0076', 'category' => 'Motor', 'location' => 'Estante C · Nivel 3', 'stock' => 2, 'min' => 6, 'unit' => 'piezas', 'priority' => 'Media', 'status' => 'En revisión', 'date' => '02 nov 2026', 'time' => '15:40', 'sort' => '202611021540'),
    array('name' => 'Filtro de aceite universal', 'code' => 'PZA-0018', 'category' => 'Filtros', 'location' => 'Estante A · Nivel 2', 'stock' => 5, 'min' => 15, 'unit' => 'unidades', 'priority' => 'Media', 'status' => 'Pendiente', 'date' => '02 nov 2026', 'time' => '12:15', 'sort' => '202611021215'),
    array('name' => 'Terminal de dirección', 'code' => 'PZA-0089', 'category' => 'Suspensión', 'location' => 'Estante D · Nivel 1', 'stock' => 3, 'min' => 8, 'unit' => 'piezas', 'priority' => 'Baja', 'status' => 'En revisión', 'date' => '01 nov 2026', 'time' => '16:30', 'sort' => '202611011630'),
    array('name' => 'Limpiaparabrisas de 22 pulgadas', 'code' => 'PZA-0064', 'category' => 'Accesorios', 'location' => 'Estante B · Nivel 2', 'stock' => 4, 'min' => 10, 'unit' => 'unidades', 'priority' => 'Baja', 'status' => 'Pendiente', 'date' => '01 nov 2026', 'time' => '10:05', 'sort' => '202611011005'),
);

// Equivalencias para mostrar cada prioridad y estado con su etiqueta
$priorityTags = array('Alta' => 'tag-high', 'Media' => 'tag-medium', 'Baja' => 'tag-low');
$priorityRanks = array('Alta' => 1, 'Media' => 2, 'Baja' => 3);
$statusTags = array('Pendiente' => 'tag-low', 'En revisión' => 'tag-review');

// Lista de productos del formulario: el del diseño y los que aparecen en la tabla
$products = array(
    array('name' => 'Filtro de aire para motor', 'code' => 'PZA-0053', 'category' => 'Filtros', 'location' => 'Estante A · Nivel 1', 'stock' => 4, 'min' => 10, 'unit' => 'unidades'),
);
foreach ($alerts as $alert) {
    $products[] = array(
        'name' => $alert['name'], 'code' => $alert['code'], 'category' => $alert['category'],
        'location' => $alert['location'], 'stock' => $alert['stock'], 'min' => $alert['min'], 'unit' => $alert['unit'],
    );
}

// Categorías sin repetir para la lista de filtros
$categories = array();
foreach ($products as $product) {
    $categories[$product['category']] = true;
}
$categories = array_keys($categories);
sort($categories);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrar alertas | Tienda</title>

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
          <img src="../imagenes/icon-package-minus.svg" width="24" height="24" alt="">
        </span>
        <div class="d-flex flex-column gap-2">
          <h1 class="page-title fw-bold m-0">Registrar alertas de stock bajo</h1>
          <p class="page-subtitle m-0">Registra productos por debajo del stock mínimo y da seguimiento a su reposición desde el panel de alertas de stock bajo.</p>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-12">
        <a href="registrarAlerta.php" class="btn btn-soft d-flex align-items-center gap-2 fw-semibold">
          <img src="../imagenes/icon-refresh.svg" width="18" height="18" alt="">
          Actualizar
        </a>
        <a href="alertasStockBajo.php" class="btn btn-soft d-flex align-items-center gap-2 fw-semibold">
          <img src="../imagenes/icon-arrow-up-right.svg" width="18" height="18" alt="">
          Ver panel de alertas
        </a>
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
            <span class="summary-name fw-semibold">Alertas registradas</span>
          </div>
          <span class="summary-text">Productos por debajo del mínimo</span>
        </div>
      </div>
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-circle-alert.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key="status" data-count-value="Pendiente"></span>
            <span class="summary-name fw-semibold">Pendientes</span>
          </div>
          <span class="summary-text">En espera de reposición</span>
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
          <span class="summary-text">Reposición en seguimiento</span>
        </div>
      </div>
      <div class="summary-card d-flex align-items-center gap-3">
        <span class="summary-icon d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../imagenes/icon-flag.svg" width="22" height="22" alt="">
        </span>
        <div class="summary-info d-flex flex-column">
          <div class="d-flex align-items-center gap-12">
            <span class="summary-value fw-bold" data-count-key="priority" data-count-value="Alta"></span>
            <span class="summary-name fw-semibold">Prioridad alta</span>
          </div>
          <span class="summary-text">Requieren atención preferente</span>
        </div>
      </div>
    </div>

    <!-- Formulario para crear una alerta -->
    <form id="alertForm" class="alert-form d-flex flex-column" data-products="<?php echo htmlspecialchars(json_encode($products)); ?>">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex flex-column gap-2">
          <h2 class="form-title fw-bold m-0">Crear alerta de stock bajo</h2>
          <p class="form-subtitle m-0">Selecciona un producto, define el mínimo y registra la alerta para dar seguimiento a su reposición.</p>
        </div>
        <span class="destination-tag fw-semibold">Destino: panel de alertas de stock bajo</span>
      </div>

      <div class="form-row">
        <div class="col-grow">
          <label for="product" class="control-label fw-medium">Producto · buscar por nombre o código *</label>
          <div class="control-box d-flex align-items-center">
            <img src="../imagenes/icon-search-sm.svg" width="18" height="18" alt="">
            <input type="text" id="product" list="productList" placeholder="Filtro de aire para motor · PZA-0053" autocomplete="off" required>
            <img src="../imagenes/icon-chevron-down.svg" width="16" height="16" alt="">
          </div>
          <datalist id="productList">
            <?php foreach ($products as $product): ?>
            <option value="<?php echo htmlspecialchars($product['name'] . ' · ' . $product['code']); ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-280">
          <label for="productCategory" class="control-label fw-medium">Categoría</label>
          <div class="control-box is-readonly d-flex align-items-center">
            <input type="text" id="productCategory" placeholder="Sin producto" readonly>
            <img src="../imagenes/icon-lock-keyhole.svg" width="16" height="16" alt="">
          </div>
        </div>
        <div class="col-320">
          <label for="productLocation" class="control-label fw-medium">Ubicación del producto</label>
          <div class="control-box is-readonly d-flex align-items-center">
            <input type="text" id="productLocation" placeholder="Sin producto" readonly>
            <img src="../imagenes/icon-map-pin-xs.svg" width="16" height="16" alt="">
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="col-310">
          <label for="currentStock" class="control-label fw-medium">Stock actual · solo lectura</label>
          <div class="control-box is-readonly d-flex align-items-center">
            <input type="text" id="currentStock" placeholder="Sin producto" readonly>
            <img src="../imagenes/icon-lock-keyhole.svg" width="16" height="16" alt="">
          </div>
          <p class="control-help">Existencias del inventario. No editable.</p>
        </div>
        <div class="col-310">
          <label for="threshold" class="control-label fw-medium">Umbral mínimo de stock *</label>
          <div class="control-box d-flex align-items-center">
            <input type="number" id="threshold" min="1" step="1" placeholder="10" required>
            <button type="button" id="thresholdStepper" class="stepper-button" aria-label="Subir o bajar el umbral">
              <img src="../imagenes/icon-chevrons-up-down.svg" width="16" height="16" alt="">
            </button>
          </div>
          <p id="thresholdUnit" class="control-help">Unidad de medida: unidades</p>
        </div>
        <div class="col-260">
          <label for="priority" class="control-label fw-medium">Prioridad *</label>
          <select id="priority" class="form-select control-select" required>
            <option value="Alta">Alta</option>
            <option value="Media">Media</option>
            <option value="Baja">Baja</option>
          </select>
          <p id="priorityHelp" class="control-help">Atención preferente para reposición.</p>
        </div>
        <div class="col-grow condition-col">
          <span class="control-label fw-medium">Condición detectada</span>
          <div class="condition-box d-flex align-items-center">
            <img src="../imagenes/icon-triangle-alert-sm.svg" width="18" height="18" alt="">
            <span id="conditionText" class="condition-text" role="status"></span>
          </div>
          <p class="control-help">La alerta se registrará con estado Pendiente.</p>
        </div>
      </div>

      <div>
        <div class="d-flex flex-wrap justify-content-between gap-2">
          <label for="notes" class="control-label fw-medium">Observaciones</label>
          <span class="optional-text">Opcional · <span id="notesCount">0</span>/500 caracteres</span>
        </div>
        <textarea id="notes" class="form-control control-textarea" maxlength="500" placeholder="Solicitar reposición al proveedor habitual. Priorizar este producto por su alta rotación."></textarea>
      </div>

      <div class="form-footer d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <img src="../imagenes/icon-info-sm.svg" width="18" height="18" alt="" class="flex-shrink-0">
          <div>
            <p class="form-note-title m-0">Registrar una alerta no modifica el stock ni crea una entrada de inventario.</p>
            <p class="form-note-text">Podrás consultar la alerta registrada en el panel de alertas de stock bajo.</p>
            <p id="alertNotice" class="form-notice mt-2 mb-0 d-none" role="status">Alerta agregada a la tabla de esta pantalla. Todavía no se guarda: se perderá al recargar la página.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-12">
          <button type="reset" id="clearForm" class="btn-text fw-medium">Limpiar</button>
          <a href="panelAdministrador.php" class="btn btn-soft d-flex align-items-center fw-semibold">Cancelar</a>
          <button type="submit" class="btn btn-solid d-flex align-items-center gap-2 fw-semibold">
            <img src="../imagenes/icon-plus-sm.svg" width="18" height="18" alt="">
            Registrar alerta
          </button>
        </div>
      </div>
    </form>

    <!-- Alertas registradas -->
    <section class="d-flex flex-column gap-3">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <h2 class="section-title fw-bold m-0">Alertas de stock bajo registradas</h2>
          <p class="section-subtitle mb-0">Consulta las alertas existentes y el estado de su reposición.</p>
        </div>
        <button type="button" class="btn btn-soft d-flex align-items-center gap-2 fw-semibold">
          <img src="../imagenes/icon-download.svg" width="18" height="18" alt="">
          Exportar alertas
        </button>
      </div>

      <!-- Búsqueda y filtros -->
      <div class="filters-panel d-flex flex-wrap align-items-end">
        <div class="filter-search">
          <label for="alertSearch" class="control-label fw-medium">Buscar producto</label>
          <div class="control-box d-flex align-items-center">
            <img src="../imagenes/icon-search-sm.svg" width="18" height="18" alt="">
            <input type="search" id="alertSearch" placeholder="Buscar por nombre o código de producto">
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

      <!-- Tabla de alertas -->
      <div class="table-card">
        <div class="table-tabs d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="alert-tab is-active" data-key="" data-value="">Todas<span class="tab-count" data-count-key=""></span></button>
            <button type="button" class="alert-tab" data-key="status" data-value="Pendiente">Pendientes<span class="tab-count" data-count-key="status" data-count-value="Pendiente"></span></button>
            <button type="button" class="alert-tab" data-key="status" data-value="En revisión">En revisión<span class="tab-count" data-count-key="status" data-count-value="En revisión"></span></button>
          </div>
          <span class="table-note"><span data-count-key=""></span> alertas activas · <span data-count-key="priority" data-count-value="Alta"></span> de prioridad alta</span>
        </div>
        <div class="table-responsive">
          <table class="alerts-table is-tall">
            <colgroup>
              <col>
              <col style="width: 150px">
              <col style="width: 210px">
              <col style="width: 130px">
              <col style="width: 130px">
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
                <th scope="col">Stock actual</th>
                <th scope="col">Mínimo</th>
                <th scope="col">Prioridad ↓</th>
                <th scope="col">Estado</th>
                <th scope="col">Fecha de registro</th>
                <th scope="col">Acciones</th>
              </tr>
            </thead>
            <tbody id="alertsBody">
              <?php foreach ($alerts as $alert): ?>
              <tr class="alert-row"
                  data-name="<?php echo htmlspecialchars($alert['name']); ?>"
                  data-code="<?php echo htmlspecialchars($alert['code']); ?>"
                  data-category="<?php echo htmlspecialchars($alert['category']); ?>"
                  data-priority="<?php echo htmlspecialchars($alert['priority']); ?>"
                  data-status="<?php echo htmlspecialchars($alert['status']); ?>"
                  data-rank="<?php echo (int) $priorityRanks[$alert['priority']]; ?>"
                  data-time="<?php echo htmlspecialchars($alert['sort']); ?>">
                <td>
                  <div class="cell-name js-name"><?php echo htmlspecialchars($alert['name']); ?></div>
                  <div class="cell-code js-code"><?php echo htmlspecialchars($alert['code']); ?></div>
                </td>
                <td class="js-category"><?php echo htmlspecialchars($alert['category']); ?></td>
                <td class="js-location"><?php echo htmlspecialchars($alert['location']); ?></td>
                <td class="cell-stock js-stock"><?php echo htmlspecialchars($alert['stock'] . ' ' . $alert['unit']); ?></td>
                <td class="cell-dark js-min"><?php echo htmlspecialchars($alert['min'] . ' ' . $alert['unit']); ?></td>
                <td><span class="tag js-priority <?php echo htmlspecialchars($priorityTags[$alert['priority']]); ?>">• <?php echo htmlspecialchars($alert['priority']); ?></span></td>
                <td><span class="tag js-status <?php echo htmlspecialchars($statusTags[$alert['status']]); ?>"><?php echo htmlspecialchars($alert['status']); ?></span></td>
                <td>
                  <div class="cell-date js-date"><?php echo htmlspecialchars($alert['date']); ?></div>
                  <div class="cell-time js-time"><?php echo htmlspecialchars($alert['time']); ?> h</div>
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
          <div class="d-flex align-items-center gap-3">
            <button type="button" id="previousPage" class="page-nav">Anterior</button>
            <div id="pageNumbers" class="d-flex gap-2"></div>
            <button type="button" id="nextPage" class="page-nav">Siguiente</button>
          </div>
        </div>
      </div>

      <div class="alerts-footnote d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="d-flex align-items-center gap-2">
          <img src="../imagenes/icon-info-xs.svg" width="16" height="16" alt="">
          Activas: pendientes y en revisión. La reposición se gestiona desde inventario.
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
  <script src="js/registrarAlerta.js"></script>
</body>
</html>
