<?php
// Pantalla del administrador para consultar piezas.
// Por ahora usa datos de ejemplo: todavía no consulta la base de datos.

// Trae las piezas de ejemplo ($pieces) y la función formatPrice()
require __DIR__ . '/datosEjemplo.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consultar piezas | Tienda</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Estilos generales, del registro de piezas, de la ventana de aviso y de esta pantalla -->
  <link href="../public/css/styles.css" rel="stylesheet">
  <link href="css/registrarPieza.css" rel="stylesheet">
  <link href="css/ajusteStock.css" rel="stylesheet">
  <link href="css/consultarPiezas.css" rel="stylesheet">
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

  <main class="register-content d-flex flex-column gap-4">

    <!-- Ruta de navegación: indica dónde estás y permite regresar -->
    <nav class="d-flex flex-wrap align-items-center gap-12" aria-label="Ruta de navegación">
      <a href="panelAdministrador.php" class="breadcrumb-link text-decoration-none">Inicio</a>
      <img src="../imagenes/icon-chevron-right.svg" width="14" height="14" alt="">
      <a href="piezasAdministrador.php" class="breadcrumb-link text-decoration-none">Existencias</a>
      <img src="../imagenes/icon-chevron-right.svg" width="14" height="14" alt="">
      <span class="breadcrumb-current fw-semibold" aria-current="page">Panel de consulta</span>
    </nav>

    <!-- Título de la pantalla y enlace para volver -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex flex-column gap-2">
        <h1 class="page-title fw-bold m-0">Consulta</h1>
        <p class="page-subtitle m-0">Ingresa uno o más criterios para encontrar las piezas que coincidan con tu búsqueda.</p>
      </div>
      <a href="piezasAdministrador.php" class="back-link d-flex align-items-center gap-2 fw-semibold text-decoration-none">
        <img src="../imagenes/icon-arrow-left.svg" width="18" height="18" alt="">
        Volver a existencias
      </a>
    </div>

    <!-- Criterios de búsqueda -->
    <form id="searchForm" class="register-form bg-white overflow-hidden">
      <section class="form-section d-flex flex-column p-4">
        <div class="d-flex flex-wrap align-items-center gap-12">
          <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="../imagenes/icon-search-md.svg" width="20" height="20" alt="">
          </span>
          <div class="d-flex flex-column gap-1 flex-grow-1">
            <h2 class="form-section-title fw-bold m-0">Datos de la pieza</h2>
            <p class="form-section-text m-0">Todos los criterios son opcionales. Se combinarán los campos que completes.</p>
          </div>
          <span class="search-tag fw-semibold">Búsqueda por criterios</span>
        </div>
        <div class="search-fields d-flex flex-column">
          <div class="field-row">
            <div>
              <label for="searchName" class="field-label fw-medium">Nombre de la pieza</label>
              <input type="search" id="searchName" class="form-control field-input search-input" placeholder="Ej. Rodamiento" autocomplete="off">
            </div>
            <div>
              <label for="searchSerial" class="field-label fw-medium">Número de serie</label>
              <input type="search" id="searchSerial" class="form-control field-input search-input" placeholder="Ej. SKF-6204-00852" autocomplete="off">
            </div>
            <div>
              <label for="searchBrand" class="field-label fw-medium">Marca</label>
              <input type="search" id="searchBrand" class="form-control field-input search-input" placeholder="Ej. SKF" autocomplete="off">
            </div>
          </div>
          <div class="field-row">
            <div class="search-grow">
              <label for="searchDescription" class="field-label fw-medium">Descripción de la pieza</label>
              <input type="search" id="searchDescription" class="form-control field-input search-input" placeholder="Escribe una característica o parte de la descripción" autocomplete="off">
            </div>
            <div>
              <!-- Elige si la tabla muestra las piezas activas o la sección de desactivadas -->
              <label for="searchStatus" class="field-label fw-medium">Estado de la pieza</label>
              <select id="searchStatus" class="form-select field-input field-select search-input search-select">
                <option value="active">Activas</option>
                <option value="inactive">Desactivadas</option>
              </select>
            </div>
          </div>
        </div>
      </section>
      <div class="search-actions d-flex flex-wrap align-items-center justify-content-between gap-3">
        <span class="search-help d-flex align-items-center gap-2">
          <img src="../imagenes/icon-info-xs.svg" width="16" height="16" alt="">
          Deja los campos vacíos para consultar todas las piezas.
        </span>
        <div class="d-flex flex-wrap gap-12">
          <button type="reset" class="btn btn-cancel btn-search d-flex align-items-center justify-content-center gap-2 fw-semibold">
            <img src="../imagenes/icon-rotate-ccw.svg" width="18" height="18" alt="">
            Limpiar búsqueda
          </button>
          <button type="submit" class="btn btn-register btn-search d-flex align-items-center justify-content-center gap-2 fw-semibold">
            <img src="../imagenes/icon-search-white.svg" width="18" height="18" alt="">
            Buscar piezas
          </button>
        </div>
      </div>
    </form>

    <!-- Resultados de búsqueda -->
    <section class="register-form bg-white overflow-hidden">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
        <div class="d-flex flex-column gap-2">
          <div class="d-flex flex-wrap align-items-center gap-12">
            <h2 class="results-title fw-bold m-0">Resultados de búsqueda</h2>
            <span id="resultsCount" class="search-tag fw-semibold" role="status"></span>
          </div>
          <p id="resultsText" class="results-text m-0"></p>
          <p id="deactivateNotice" class="deactivate-notice fw-medium m-0 d-none" role="status"></p>
        </div>
        <div id="criteriaBox" class="d-flex flex-wrap align-items-center gap-2 d-none">
          <span class="criteria-label">Criterios aplicados:</span>
          <div id="criteriaList" class="d-flex flex-wrap gap-2"></div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="pieces-table">
          <colgroup>
            <col style="width: 224px">
            <col style="width: 165px">
            <col style="width: 65px">
            <col style="width: 194px">
            <col style="width: 115px">
            <col style="width: 115px">
            <col style="width: 65px">
            <col style="width: 100px">
            <col style="width: 185px">
            <col style="width: 90px">
            <col style="width: 81px">
            <col>
          </colgroup>
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
              <th scope="col">Acciones</th>
            </tr>
          </thead>
          <tbody id="piecesBody">
            <?php foreach ($pieces as $piece): ?>
            <tr class="piece-row"
                data-status="active"
                data-url="editarPieza.php?serial=<?php echo urlencode($piece['serial']); ?>"
                data-name="<?php echo htmlspecialchars($piece['name']); ?>"
                data-serial="<?php echo htmlspecialchars($piece['serial']); ?>"
                data-brand="<?php echo htmlspecialchars($piece['brand']); ?>"
                data-description="<?php echo htmlspecialchars($piece['description'] . ' ' . $piece['size']); ?>">
              <!-- El nombre también es un enlace para poder abrir la pieza con el teclado -->
              <td class="fw-medium"><a href="editarPieza.php?serial=<?php echo urlencode($piece['serial']); ?>" class="piece-link text-decoration-none"><?php echo htmlspecialchars($piece['name']); ?></a></td>
              <td><?php echo htmlspecialchars($piece['serial']); ?></td>
              <td><?php echo htmlspecialchars($piece['brand']); ?></td>
              <td class="piece-description">
                <?php echo htmlspecialchars($piece['description']); ?><br>
                <?php echo htmlspecialchars($piece['size']); ?>
              </td>
              <td><?php echo htmlspecialchars(formatPrice($piece['purchase'])); ?></td>
              <td><?php echo htmlspecialchars(formatPrice($piece['sale'])); ?></td>
              <td class="fw-medium"><?php echo (int) $piece['stock']; ?></td>
              <td><?php echo (int) $piece['min']; ?></td>
              <td><?php echo htmlspecialchars($piece['location']); ?></td>
              <td><?php echo htmlspecialchars($piece['shelf']); ?></td>
              <td><?php echo htmlspecialchars($piece['rack']); ?></td>
              <td>
                <!-- Una pieza desactivada muestra esto en lugar de sus acciones normales -->
                <div class="deactivated-actions align-items-center gap-2">
                  <span class="deactivated-tag fw-semibold">Desactivada</span>
                  <button type="button" class="row-button js-reactivate d-flex align-items-center gap-1" aria-label="Reactivar <?php echo htmlspecialchars($piece['name']); ?>">
                    <img src="../imagenes/icon-rotate-ccw.svg" width="14" height="14" alt="">
                    Reactivar
                  </button>
                </div>
                <div class="row-actions d-flex align-items-center gap-2">
                  <a href="editarPieza.php?serial=<?php echo urlencode($piece['serial']); ?>" class="row-button d-flex align-items-center gap-1 text-decoration-none">
                    <img src="../imagenes/icon-edit-sm.svg" width="14" height="14" alt="">
                    Editar
                  </a>
                  <a href="ajusteStock.php?serial=<?php echo urlencode($piece['serial']); ?>" class="row-button row-button-primary d-flex align-items-center gap-1 text-decoration-none">
                    <img src="../imagenes/icon-arrow-right-white.svg" width="14" height="14" alt="">
                    Ajuste de stock
                  </a>
                  <button type="button" class="row-button js-deactivate d-flex align-items-center" aria-label="Eliminar <?php echo htmlspecialchars($piece['name']); ?>">
                    <img src="../imagenes/icon-trash.svg" width="14" height="14" alt="">
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <tr id="emptyRow" class="d-none">
              <td id="emptyText" colspan="12" class="text-center"></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="results-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
        <span id="rangeText" class="results-text"></span>
        <div class="d-flex align-items-center gap-3">
          <button type="button" id="previousPage" class="page-nav d-flex align-items-center gap-1">
            <img src="../imagenes/icon-chevron-left-md.svg" width="16" height="16" alt="">
            Anterior
          </button>
          <div id="pageNumbers" class="d-flex gap-2"></div>
          <button type="button" id="nextPage" class="page-nav d-flex align-items-center gap-1">
            Siguiente
            <img src="../imagenes/icon-chevron-right-md.svg" width="16" height="16" alt="">
          </button>
        </div>
      </div>
    </section>
  </main>

  <!-- Confirmación: aparece antes de desactivar o reactivar una pieza; sus textos cambian según el caso -->
  <div id="confirmOverlay" class="success-overlay d-none">
    <div class="success-card bg-white d-flex flex-column align-items-center" role="alertdialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmText">
      <span class="confirm-icon d-flex align-items-center justify-content-center">
        <img id="confirmIconDeactivate" src="../imagenes/icon-alert-triangle.svg" width="24" height="24" alt="">
        <img id="confirmIconReactivate" src="../imagenes/icon-rotate-ccw.svg" width="24" height="24" alt="">
      </span>
      <div class="d-flex flex-column gap-2 text-center">
        <h2 id="confirmTitle" class="success-title fw-bold m-0"></h2>
        <p id="confirmText" class="success-text m-0"></p>
      </div>
      <div class="d-flex flex-wrap justify-content-center gap-12">
        <button type="button" id="confirmCancel" class="btn btn-cancel adjust-button d-flex align-items-center justify-content-center fw-semibold">Cancelar</button>
        <button type="button" id="confirmAccept" class="btn btn-register adjust-button d-flex align-items-center justify-content-center fw-semibold"></button>
      </div>
    </div>
  </div>

<?php
// La página está dentro de una carpeta: la marca de agua sube un nivel para hallar su imagen.
$rootPath = '../';
require __DIR__ . '/../includes/watermark.php';
?>

  <script src="js/consultarPiezas.js"></script>
</body>
</html>
