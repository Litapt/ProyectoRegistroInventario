<?php
// Registro de entrada de producto – Paso uno: buscar la pieza.
// Se llega aquí con el botón "Registrar entrada de producto" de panelExistencias.php.
// El usuario llena los criterios, da clic en "Buscar piezas" y abajo aparece la tabla
// con las coincidencias. Al dar clic en una fila se abre detallePieza.php con esa pieza.

// Esta misma pantalla sirve para el registro de SALIDA: registroSalida.php pone
// $flowMode = 'salida' antes de cargar este archivo. Si no viene, es entrada.

require_once __DIR__ . '/../includes/product_search.php';
require_once __DIR__ . '/../includes/format.php';
require_once __DIR__ . '/../includes/stock_flow.php';

$flow = getStockFlow($flowMode ?? 'entrada');

// Página a la que lleva cada fila de la tabla: el detalle de la pieza elegida
$nextStepPage = 'detallePieza.php';

// Cuántas piezas se muestran por página en la tabla
$perPage = 10;

// Criterios que escribió el usuario (vienen en la dirección de la página, por GET)
$criteria = [
    'part_name'     => trim($_GET['part_name'] ?? ''),
    'serial_number' => trim($_GET['serial_number'] ?? ''),
    'brand'         => trim($_GET['brand'] ?? ''),
    'description'   => trim($_GET['description'] ?? ''),
];

// Nombre que se muestra en las etiquetas de "Criterios aplicados"
$criteriaLabels = [
    'part_name'     => 'Nombre',
    'serial_number' => 'Serie',
    'brand'         => 'Marca',
    'description'   => 'Descripción',
];

// La tabla solo aparece después de dar clic en "Buscar piezas"
$hasSearched = isset($_GET['search']);
$results = [];
$totalResults = 0;
$currentPage = 1;
$totalPages = 1;

if ($hasSearched) {
    $allResults = searchProducts($criteria);
    $totalResults = count($allResults);
    $totalPages = max(1, (int) ceil($totalResults / $perPage));

    // Página actual: siempre entre 1 y la última
    $currentPage = (int) ($_GET['page'] ?? 1);
    $currentPage = min(max(1, $currentPage), $totalPages);

    $results = array_slice($allResults, ($currentPage - 1) * $perPage, $perPage);
}

// Solo los criterios que sí tienen texto
$appliedCriteria = array_filter($criteria, function ($value) {
    return $value !== '';
});

// Arma el enlace a otra página de resultados conservando los criterios
function pageUrl(string $basePage, array $criteria, int $page): string
{
    return $basePage . '?' . http_build_query($criteria + ['search' => 1, 'page' => $page]);
}

$firstShown = $totalResults > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
$lastShown = ($currentPage - 1) * $perPage + count($results);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($flow['pageTitle']) ?> | Luna headlights</title>

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

  <main class="register-main flex-grow-1 d-flex flex-column">

    <!-- Ruta de navegación (dónde estás) -->
    <nav aria-label="Ruta de navegación" class="breadcrumb-nav d-flex flex-wrap align-items-center">
      <a href="panelAdministrador.php" class="breadcrumb-link">Inicio</a>
      <img src="../public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <a href="panelExistencias.php" class="breadcrumb-link">Existencias</a>
      <img src="../public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <span class="breadcrumb-current fw-semibold" aria-current="page">Consultar inventario</span>
    </nav>

    <!-- Título y enlace para regresar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex flex-column gap-2">
        <h1 class="page-title fw-bold m-0"><?= htmlspecialchars($flow['searchTitle']) ?></h1>
        <p class="page-subtitle m-0">Ingresa uno o más criterios para encontrar las piezas que coincidan con tu búsqueda.</p>
      </div>
      <a href="panelExistencias.php" class="app-link fw-semibold d-flex align-items-center gap-2">
        <img src="../public/img/icon-arrow-left.svg" width="18" height="18" alt="">
        Volver a existencias
      </a>
    </div>

    <!-- Criterios de búsqueda: se envían por GET a esta misma página -->
    <form method="get" action="<?= htmlspecialchars($flow['searchPage']) ?>" class="register-form bg-white">
      <section class="form-section d-flex flex-column">
        <div class="d-flex flex-wrap align-items-center gap-3">
          <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="../public/img/icon-search-small.svg" width="20" height="20" alt="">
          </span>
          <div class="flex-grow-1">
            <h2 class="form-section-title fw-bold m-0">Datos de la pieza</h2>
            <p class="form-section-text m-0">Todos los criterios son opcionales. Se combinarán los campos que completes.</p>
          </div>
          <span class="status-badge fw-semibold">Búsqueda por criterios</span>
        </div>

        <div class="d-flex flex-column gap-3">
          <div class="field-grid">
            <div>
              <label for="part_name" class="form-label app-label fw-medium mb-2">Nombre de la pieza</label>
              <input type="text" id="part_name" name="part_name" class="form-control app-input search-input" placeholder="Ej. Rodamiento" value="<?= htmlspecialchars($criteria['part_name']) ?>">
            </div>
            <div>
              <label for="serial_number" class="form-label app-label fw-medium mb-2">Número de serie</label>
              <input type="text" id="serial_number" name="serial_number" class="form-control app-input search-input" placeholder="Ej. SKF-6204-00852" value="<?= htmlspecialchars($criteria['serial_number']) ?>">
            </div>
            <div>
              <label for="brand" class="form-label app-label fw-medium mb-2">Marca</label>
              <input type="text" id="brand" name="brand" class="form-control app-input search-input" placeholder="Ej. SKF" value="<?= htmlspecialchars($criteria['brand']) ?>">
            </div>
          </div>
          <div>
            <label for="description" class="form-label app-label fw-medium mb-2">Descripción de la pieza</label>
            <input type="text" id="description" name="description" class="form-control app-input search-input" placeholder="Escribe una característica o parte de la descripción" value="<?= htmlspecialchars($criteria['description']) ?>">
          </div>
        </div>
      </section>

      <!-- Barra inferior con la ayuda y los botones -->
      <div class="search-actions d-flex flex-wrap align-items-center justify-content-between gap-3">
        <p class="search-hint d-flex align-items-center gap-2 m-0">
          <img src="../public/img/icon-info.svg" width="16" height="16" alt="" class="flex-shrink-0">
          Deja los campos vacíos para consultar todas las piezas.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <!-- "Limpiar" abre la página sin criterios, así se vacían los campos y la tabla -->
          <a href="<?= htmlspecialchars($flow['searchPage']) ?>" class="btn btn-app-secondary btn-search-action fw-semibold d-flex align-items-center gap-2">
            <img src="../public/img/icon-rotate-ccw.svg" width="18" height="18" alt="">
            Limpiar búsqueda
          </a>
          <button type="submit" name="search" value="1" class="btn btn-app-primary btn-search-action fw-semibold d-flex align-items-center gap-2">
            <img src="../public/img/icon-search-white-small.svg" width="18" height="18" alt="">
            Buscar piezas
          </button>
        </div>
      </div>
    </form>

<?php if ($hasSearched): ?>
    <!-- Resultados de búsqueda -->
    <section class="results-card bg-white" aria-labelledby="resultsTitle">
      <div class="results-summary d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex flex-column gap-2">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <h2 id="resultsTitle" class="form-section-title fw-bold m-0">Resultados de búsqueda</h2>
            <span class="results-count fw-semibold"><?= $totalResults ?> <?= $totalResults === 1 ? 'pieza encontrada' : 'piezas encontradas' ?></span>
          </div>
          <p class="form-section-text m-0">Piezas que coinciden con todos los criterios ingresados.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="form-section-text">Criterios aplicados:</span>
<?php if ($appliedCriteria): ?>
<?php foreach ($appliedCriteria as $field => $value): ?>
          <span class="criteria-chip fw-medium"><?= htmlspecialchars($criteriaLabels[$field] . ': ' . $value) ?></span>
<?php endforeach; ?>
<?php else: ?>
          <span class="criteria-chip fw-medium">Todas las piezas</span>
<?php endif; ?>
        </div>
      </div>

      <!-- La tabla se desliza de lado en pantallas pequeñas -->
      <div class="table-responsive">
        <table class="table results-table mb-0">
          <thead>
            <tr>
              <th scope="col">Nombre de la pieza</th>
              <th scope="col">Número de serie</th>
              <th scope="col">Marca</th>
              <th scope="col">Descripción de la pieza</th>
              <th scope="col">Precio de compra</th>
              <th scope="col">Precio de venta</th>
              <th scope="col">Stock</th>
              <th scope="col">Stock mínimo</th>
              <th scope="col">Ubicación</th>
              <th scope="col">Anaquel</th>
              <th scope="col">Rack</th>
            </tr>
          </thead>
          <tbody>
<?php if (!$results): ?>
            <tr>
              <td colspan="11" class="results-empty text-center">No se encontraron piezas con esos criterios. Prueba con menos datos o deja los campos vacíos.</td>
            </tr>
<?php endif; ?>
<?php foreach ($results as $product): ?>
<?php // El enlace lleva el id de la pieza y la búsqueda actual, para poder regresar a la misma tabla ?>
<?php $rowHref = $nextStepPage . '?' . http_build_query(['id' => $product['id'], 'mode' => $flow['mode']] + $criteria + ['page' => $currentPage]); ?>
            <tr class="result-row" tabindex="0" data-href="<?= htmlspecialchars($rowHref) ?>" aria-label="Seleccionar <?= htmlspecialchars($product['part_name']) ?>">
              <td class="fw-medium"><?= htmlspecialchars($product['part_name']) ?></td>
              <td><?= htmlspecialchars($product['serial_number']) ?></td>
              <td><?= htmlspecialchars($product['brand']) ?></td>
              <td class="cell-description"><?= htmlspecialchars($product['description']) ?></td>
              <td><?= htmlspecialchars(formatMoney($product['purchase_price'])) ?></td>
              <td><?= htmlspecialchars(formatMoney($product['sale_price'])) ?></td>
              <td class="fw-medium"><?= htmlspecialchars((string) $product['stock']) ?></td>
              <td><?= htmlspecialchars((string) $product['min_stock']) ?></td>
              <td><?= htmlspecialchars($product['location']) ?></td>
              <td><?= htmlspecialchars($product['shelf']) ?></td>
              <td><?= htmlspecialchars($product['rack']) ?></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pie: cuántas se muestran y botones de página -->
      <div class="results-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
        <p class="form-section-text m-0">Mostrando <?= $firstShown ?>–<?= $lastShown ?> de <?= $totalResults ?> piezas</p>
        <nav aria-label="Páginas de resultados" class="d-flex align-items-center gap-3">
<?php if ($currentPage > 1): ?>
          <a href="<?= htmlspecialchars(pageUrl($flow['searchPage'], $criteria,$currentPage - 1)) ?>" class="page-step d-flex align-items-center gap-1">
<?php else: ?>
          <span class="page-step is-disabled d-flex align-items-center gap-1" aria-disabled="true">
<?php endif; ?>
            <img src="../public/img/icon-chevron-left-sm.svg" width="16" height="16" alt="">
            Anterior
          <?= $currentPage > 1 ? '</a>' : '</span>' ?>

<?php for ($page = 1; $page <= $totalPages; $page++): ?>
<?php if ($page === $currentPage): ?>
          <span class="page-number is-current fw-semibold d-flex align-items-center justify-content-center" aria-current="page"><?= $page ?></span>
<?php else: ?>
          <a href="<?= htmlspecialchars(pageUrl($flow['searchPage'], $criteria,$page)) ?>" class="page-number d-flex align-items-center justify-content-center"><?= $page ?></a>
<?php endif; ?>
<?php endfor; ?>

<?php if ($currentPage < $totalPages): ?>
          <a href="<?= htmlspecialchars(pageUrl($flow['searchPage'], $criteria,$currentPage + 1)) ?>" class="page-step d-flex align-items-center gap-1">
<?php else: ?>
          <span class="page-step is-disabled d-flex align-items-center gap-1" aria-disabled="true">
<?php endif; ?>
            Siguiente
            <img src="../public/img/icon-chevron-right-sm.svg" width="16" height="16" alt="">
          <?= $currentPage < $totalPages ? '</a>' : '</span>' ?>
        </nav>
      </div>
    </section>
<?php endif; ?>

  </main>

<?php require __DIR__ . '/../includes/watermark.php'; ?>

  <script src="../public/js/registroEntrada.js"></script>

</body>
</html>
