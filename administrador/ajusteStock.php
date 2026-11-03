<?php
// Pantalla del administrador para registrar una entrada o un egreso de stock.
// Por ahora solo calcula y muestra el resultado: no guarda nada en la base de datos.

// Trae las piezas de ejemplo ($pieces) y la función formatPrice()
require __DIR__ . '/datosEjemplo.php';

// Busca la pieza que se eligió en la tabla de consulta
$selectedSerial = isset($_GET['serial']) ? (string) $_GET['serial'] : '';
$piece = null;
foreach ($pieces as $candidate) {
    if ($candidate['serial'] === $selectedSerial) {
        $piece = $candidate;
        break;
    }
}

// Si no llega una pieza válida, regresamos a la consulta
if ($piece === null) {
    header('Location: consultarPiezas.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ajuste de stock | Tienda</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Estilos generales, los del registro de piezas y los propios de esta pantalla -->
  <link href="../public/css/styles.css" rel="stylesheet">
  <link href="css/registrarPieza.css" rel="stylesheet">
  <link href="css/ajusteStock.css" rel="stylesheet">
</head>
<body class="bg-white min-vh-100">

  <div id="pageContent">
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

    <main class="register-content adjust-content d-flex flex-column gap-4">

      <!-- Ruta de navegación: indica dónde estás y permite regresar -->
      <nav class="d-flex flex-wrap align-items-center gap-12" aria-label="Ruta de navegación">
        <a href="panelAdministrador.php" class="breadcrumb-link text-decoration-none">Inicio</a>
        <img src="../imagenes/icon-chevron-right.svg" width="14" height="14" alt="">
        <a href="piezasAdministrador.php" class="breadcrumb-link text-decoration-none">Existencias</a>
        <img src="../imagenes/icon-chevron-right.svg" width="14" height="14" alt="">
        <a href="consultarPiezas.php" class="breadcrumb-link text-decoration-none">Panel de consulta</a>
        <img src="../imagenes/icon-chevron-right.svg" width="14" height="14" alt="">
        <span class="breadcrumb-current fw-semibold" aria-current="page">Ajuste de stock</span>
      </nav>

      <a href="consultarPiezas.php" class="back-link d-inline-flex align-items-center gap-2 fw-semibold text-decoration-none align-self-start">
        <img src="../imagenes/icon-adjust-back.svg" width="18" height="18" alt="">
        Volver al panel de consulta
      </a>

      <div class="d-flex flex-column gap-2">
        <h1 class="page-title fw-bold m-0">Registrar ajuste de stock</h1>
        <p class="page-subtitle m-0">Selecciona una entrada o un egreso, ingresa la cantidad y revisa el stock resultante antes de confirmar.</p>
      </div>

      <form id="adjustForm" class="d-flex flex-column gap-4" data-stock="<?php echo (int) $piece['stock']; ?>" data-min="<?php echo (int) $piece['min']; ?>">

        <!-- Movimiento de stock -->
        <section class="register-form bg-white overflow-hidden">
          <div class="adjust-section-header d-flex align-items-center gap-12 p-4">
            <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../imagenes/icon-movement.svg" width="20" height="20" alt="">
            </span>
            <div class="d-flex flex-column gap-1">
              <h2 class="form-section-title fw-bold m-0">Movimiento de stock</h2>
              <p class="form-section-text m-0">Elige el tipo de ajuste. La cantidad se sumará o se restará del stock actual.</p>
            </div>
          </div>

          <div class="d-flex flex-column gap-4 p-4">
            <div class="adjust-columns">
              <div class="adjust-capture d-flex flex-column gap-3">
                <fieldset class="m-0 p-0 border-0">
                  <legend class="adjust-label fw-medium">Tipo de movimiento</legend>
                  <div class="move-options">
                    <label class="move-option d-flex align-items-center gap-12" data-move="entry">
                      <input type="radio" name="movement" value="entry" class="visually-hidden">
                      <img src="../imagenes/icon-plus-option.svg" width="18" height="18" alt="" class="option-icon-idle flex-shrink-0">
                      <img src="../imagenes/icon-plus-option-active.svg" width="18" height="18" alt="" class="option-icon-active flex-shrink-0">
                      <span class="d-flex flex-column gap-1 flex-grow-1">
                        <span class="option-title fw-semibold">Entrada · Aumentar stock</span>
                        <span class="option-text">Agregar unidades al almacén</span>
                      </span>
                      <span class="option-radio flex-shrink-0"></span>
                    </label>
                    <label class="move-option d-flex align-items-center gap-12" data-move="exit">
                      <input type="radio" name="movement" value="exit" class="visually-hidden" checked>
                      <img src="../imagenes/icon-minus-option-muted.svg" width="18" height="18" alt="" class="option-icon-idle flex-shrink-0">
                      <img src="../imagenes/icon-minus-option.svg" width="18" height="18" alt="" class="option-icon-active flex-shrink-0">
                      <span class="d-flex flex-column gap-1 flex-grow-1">
                        <span class="option-title fw-semibold">Egreso · Disminuir stock</span>
                        <span class="option-text">Retirar unidades del almacén</span>
                      </span>
                      <span class="option-radio flex-shrink-0"></span>
                    </label>
                  </div>
                </fieldset>

                <div>
                  <label for="quantity" class="adjust-label fw-medium">Cantidad a ajustar</label>
                  <div class="quantity-box d-flex align-items-center gap-12">
                    <input type="number" id="quantity" class="quantity-input fw-medium" min="1" step="1" placeholder="4" required>
                    <span class="quantity-unit">unidades</span>
                  </div>
                  <p id="quantityHelp" class="quantity-help"></p>
                </div>
              </div>

              <!-- Vista previa: se recalcula mientras escribes -->
              <div class="adjust-preview d-flex flex-column" aria-live="polite">
                <p class="preview-title fw-semibold m-0">Vista previa del ajuste</p>
                <div class="d-flex flex-wrap align-items-center gap-4">
                  <div class="preview-value d-flex flex-column gap-2">
                    <span class="preview-label">Stock actual</span>
                    <span id="currentStock" class="preview-number fw-semibold"></span>
                  </div>
                  <span id="previewOperation" class="preview-operation fw-semibold"></span>
                  <div class="preview-value preview-result d-flex flex-column gap-2">
                    <span class="preview-label">Stock resultante</span>
                    <span id="resultStock" class="preview-number fw-semibold"></span>
                  </div>
                </div>
                <div class="preview-footer d-flex flex-wrap justify-content-between gap-2">
                  <span id="minStockText"></span>
                  <span id="minStockStatus"></span>
                </div>
              </div>
            </div>

            <!-- Motivo: solo se pide en los egresos -->
            <div class="reason-row">
              <div class="reason-info d-flex flex-column gap-2">
                <label for="reason" class="reason-title fw-semibold">Especificación del egreso</label>
                <p class="reason-text m-0">Indica el destino, uso o circunstancia que origina el egreso.</p>
                <p class="reason-note fw-medium m-0">Obligatorio solo para egresos</p>
              </div>
              <textarea id="reason" class="form-control reason-input" placeholder="Salida para mantenimiento de maquinaria del taller."></textarea>
            </div>
          </div>
        </section>

        <!-- Datos generales de la pieza: solo consulta -->
        <section class="register-form bg-white overflow-hidden">
          <div class="adjust-section-header d-flex flex-wrap align-items-center gap-12 p-4">
            <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../imagenes/icon-adjust-package.svg" width="20" height="20" alt="">
            </span>
            <div class="d-flex flex-column gap-1 flex-grow-1">
              <h2 class="form-section-title fw-bold m-0">Datos generales de la pieza</h2>
              <p class="form-section-text m-0">Identificación, descripción, precios y ubicación registrados.</p>
            </div>
            <span class="readonly-tag fw-semibold">Solo consulta</span>
          </div>
          <div class="data-head d-none d-lg-flex">
            <div class="data-pair"><span class="data-label">Campo</span><span class="data-value-head">Información</span></div>
            <div class="data-pair"><span class="data-label">Campo</span><span class="data-value-head">Información</span></div>
          </div>
          <div class="data-row">
            <div class="data-pair"><span class="data-label">Nombre de la pieza</span><span class="data-value fw-medium"><?php echo htmlspecialchars($piece['name']); ?></span></div>
            <div class="data-pair"><span class="data-label">Número de serie</span><span class="data-value fw-medium"><?php echo htmlspecialchars($piece['serial']); ?></span></div>
          </div>
          <div class="data-row">
            <div class="data-pair"><span class="data-label">Marca</span><span class="data-value fw-medium"><?php echo htmlspecialchars($piece['brand']); ?></span></div>
            <div class="data-pair"><span class="data-label">Descripción</span><span class="data-value fw-medium"><?php echo htmlspecialchars($piece['description'] . ' ' . $piece['size']); ?></span></div>
          </div>
          <div class="data-row">
            <div class="data-pair"><span class="data-label">Precio de compra</span><span class="data-value fw-medium"><?php echo htmlspecialchars(formatPrice($piece['purchase'])); ?></span></div>
            <div class="data-pair"><span class="data-label">Precio de venta</span><span class="data-value fw-medium"><?php echo htmlspecialchars(formatPrice($piece['sale'])); ?></span></div>
          </div>
          <div class="data-row data-row-last">
            <div class="data-pair"><span class="data-label">Ubicación</span><span class="data-value fw-medium"><?php echo htmlspecialchars($piece['location']); ?></span></div>
            <div class="data-pair"><span class="data-label">Anaquel / Rack</span><span class="data-value fw-medium"><?php echo htmlspecialchars($piece['shelf'] . ' / ' . $piece['rack']); ?></span></div>
          </div>
        </section>

        <!-- Revisión y confirmación -->
        <div class="d-flex flex-wrap align-items-center gap-4">
          <div class="confirm-summary d-flex flex-column flex-grow-1">
            <p id="summaryTitle" class="summary-title fw-semibold m-0"></p>
            <p id="summaryText" class="summary-text m-0"></p>
          </div>
          <a href="consultarPiezas.php" class="btn btn-cancel adjust-button adjust-cancel d-flex align-items-center justify-content-center fw-semibold">Cancelar</a>
          <button type="submit" class="btn btn-register adjust-button d-flex align-items-center gap-2 fw-semibold">
            <img id="confirmIconEntry" src="../imagenes/icon-plus-sm.svg" width="18" height="18" alt="">
            <img id="confirmIconExit" src="../imagenes/icon-minus-white.svg" width="18" height="18" alt="">
            <span id="confirmLabel"></span>
          </button>
        </div>
      </form>
    </main>
  </div>

  <!-- Mensaje de éxito: su texto cambia según sea entrada o egreso y ofrece dos opciones -->
  <div id="successOverlay" class="success-overlay d-none">
    <div class="success-card bg-white d-flex flex-column align-items-center" role="dialog" aria-modal="true" aria-labelledby="successTitle" aria-describedby="successText">
      <img src="../imagenes/icon-success-modal.svg" width="56" height="56" alt="">
      <div class="d-flex flex-column gap-2 text-center">
        <h2 id="successTitle" class="success-title fw-bold m-0"></h2>
        <p class="success-text m-0"><span id="successText"></span><br>¿Qué deseas hacer ahora?</p>
      </div>
      <div class="d-flex flex-wrap justify-content-center gap-12">
        <a id="moreChanges" href="ajusteStock.php?serial=<?php echo urlencode($piece['serial']); ?>" class="btn btn-cancel adjust-button d-flex align-items-center justify-content-center fw-semibold">Hacer más cambios</a>
        <a id="backToConsult" href="consultarPiezas.php" class="btn btn-register adjust-button d-flex align-items-center justify-content-center fw-semibold">Regresar a consulta</a>
      </div>
    </div>
  </div>

<?php
// La página está dentro de una carpeta: la marca de agua sube un nivel para hallar su imagen.
$rootPath = '../';
require __DIR__ . '/../includes/watermark.php';
?>

  <script src="js/ajusteStock.js"></script>
</body>
</html>
