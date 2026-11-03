<?php
// Pantalla del administrador para ver y editar los datos de una pieza.
// Por ahora los cambios solo se aplican en esta pantalla: no se guardan en la base de datos.

// Trae las piezas de ejemplo ($pieces) y las ubicaciones disponibles ($locations)
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

// Dibuja un campo editable. $kind indica cómo se compara y se muestra su valor:
// text (texto), textarea (texto largo), price (precio), units (cantidad en unidades)
// o select (lista; sus opciones llegan en $options).
// $isLarge usa la letra grande y gruesa del diseño.
function renderField($id, $label, $value, $kind, $isLarge, $options = array())
{
    $inputClass = 'edit-input' . ($isLarge ? ' edit-input-large' : '');
    ?>
    <div class="edit-field" data-kind="<?php echo htmlspecialchars($kind); ?>">
      <div class="edit-label-row d-flex align-items-center justify-content-between">
        <label for="<?php echo htmlspecialchars($id); ?>" class="edit-label"><?php echo htmlspecialchars($label); ?></label>
        <span class="edit-flag fw-semibold d-none">Modificado</span>
      </div>
      <?php if ($kind === 'textarea'): ?>
      <div class="edit-box edit-box-textarea">
        <textarea id="<?php echo htmlspecialchars($id); ?>" class="<?php echo $inputClass; ?>" required><?php echo htmlspecialchars($value); ?></textarea>
      </div>
      <?php else: ?>
      <div class="edit-box d-flex align-items-center<?php echo $isLarge ? ' edit-box-large' : ''; ?>">
        <?php if ($kind === 'price'): ?>
        <span class="edit-affix">$</span>
        <input type="number" id="<?php echo htmlspecialchars($id); ?>" class="<?php echo $inputClass; ?>" value="<?php echo htmlspecialchars(number_format((float) $value, 2, '.', '')); ?>" min="0" step="0.01" required>
        <?php elseif ($kind === 'units'): ?>
        <input type="number" id="<?php echo htmlspecialchars($id); ?>" class="<?php echo $inputClass; ?>" value="<?php echo (int) $value; ?>" min="0" step="1" required>
        <span class="edit-affix">unidades</span>
        <?php elseif ($kind === 'select'): ?>
        <select id="<?php echo htmlspecialchars($id); ?>" class="edit-input edit-select" required>
          <?php foreach ($options as $option): ?>
          <option value="<?php echo htmlspecialchars($option); ?>"<?php echo $option === $value ? ' selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
          <?php endforeach; ?>
        </select>
        <?php else: ?>
        <input type="text" id="<?php echo htmlspecialchars($id); ?>" class="<?php echo $inputClass; ?>" value="<?php echo htmlspecialchars($value); ?>" required>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <p class="edit-before m-0 d-none"></p>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detalles de pieza | Tienda</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Estilos generales, del registro de piezas, del mensaje de éxito y de esta pantalla -->
  <link href="../public/css/styles.css" rel="stylesheet">
  <link href="css/registrarPieza.css" rel="stylesheet">
  <link href="css/ajusteStock.css" rel="stylesheet">
  <link href="css/editarPieza.css" rel="stylesheet">
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

    <main class="register-content edit-content">
      <form id="editForm" class="d-flex flex-column gap-4" novalidate>

        <!-- Ruta de navegación: indica dónde estás y permite regresar -->
        <nav class="d-flex flex-wrap align-items-center gap-12" aria-label="Ruta de navegación">
          <a href="panelAdministrador.php" class="breadcrumb-link text-decoration-none">Inicio</a>
          <img src="../imagenes/icon-chevron-right.svg" width="14" height="14" alt="">
          <a href="piezasAdministrador.php" class="breadcrumb-link text-decoration-none">Existencias</a>
          <img src="../imagenes/icon-chevron-right.svg" width="14" height="14" alt="">
          <a href="consultarPiezas.php" class="breadcrumb-link text-decoration-none">Panel de consulta</a>
          <img src="../imagenes/icon-chevron-right.svg" width="14" height="14" alt="">
          <span class="breadcrumb-current fw-semibold" aria-current="page">Editar pieza</span>
        </nav>

        <a href="consultarPiezas.php" class="back-link d-inline-flex align-items-center gap-2 fw-semibold text-decoration-none align-self-start">
          <img src="../imagenes/icon-adjust-back.svg" width="18" height="18" alt="">
          Volver a resultados de búsqueda
        </a>

        <!-- Título y botón para guardar todos los cambios juntos -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
          <div class="d-flex flex-column gap-2">
            <h1 class="page-title fw-bold m-0">Detalles de pieza</h1>
            <p class="page-subtitle m-0">Modifica uno o varios campos y guarda todos los cambios en una sola acción.</p>
          </div>
          <div class="save-box d-flex flex-column align-items-end gap-2">
            <button type="submit" id="saveButton" class="btn btn-register btn-save d-flex align-items-center justify-content-center gap-2 fw-semibold" disabled>
              <img id="saveIconOff" src="../imagenes/icon-save-disabled.svg" width="18" height="18" alt="">
              <img id="saveIconOn" src="../imagenes/icon-save-white.svg" width="18" height="18" alt="" class="d-none">
              Guardar cambios
            </button>
            <span id="saveStatus" class="save-status" role="status">Sin cambios pendientes</span>
          </div>
        </div>

        <!-- Datos de la pieza -->
        <section class="register-form bg-white overflow-hidden">
          <div class="edit-section-header d-flex flex-wrap align-items-center gap-12">
            <img src="../imagenes/icon-section-data.svg" width="40" height="40" alt="" class="flex-shrink-0">
            <div class="d-flex flex-column gap-1 flex-grow-1">
              <h2 class="form-section-title fw-bold m-0">Datos de la pieza</h2>
              <p class="form-section-text m-0">Información general e identificación.</p>
            </div>
            <span class="edit-mode-tag fw-semibold">Modo edición</span>
          </div>
          <div class="edit-section-body d-flex flex-column">
            <div class="edit-row edit-row-wide">
              <div class="edit-col-main"><?php renderField('name', 'Nombre de la pieza', $piece['name'], 'text', true); ?></div>
              <div class="edit-col"><?php renderField('serial', 'Número de serie', $piece['serial'], 'text', false); ?></div>
              <div class="edit-col"><?php renderField('brand', 'Marca', $piece['brand'], 'text', false); ?></div>
            </div>
            <?php renderField('description', 'Descripción de la pieza', $piece['description'] . ' ' . $piece['size'], 'textarea', false); ?>
          </div>
        </section>

        <div class="edit-cards">
          <!-- Precios -->
          <section class="register-form edit-card bg-white overflow-hidden">
            <div class="edit-section-header d-flex align-items-center gap-12">
              <img src="../imagenes/icon-section-prices.svg" width="40" height="40" alt="" class="flex-shrink-0">
              <div class="d-flex flex-column gap-1">
                <h2 class="form-section-title fw-bold m-0">Precios</h2>
                <p class="form-section-text m-0">Valores registrados de compra y venta.</p>
              </div>
            </div>
            <div class="edit-section-body edit-row">
              <div class="edit-col"><?php renderField('purchase', 'Precio de compra', $piece['purchase'], 'price', true); ?></div>
              <div class="edit-col"><?php renderField('sale', 'Precio de venta', $piece['sale'], 'price', true); ?></div>
            </div>
          </section>

          <!-- Existencias -->
          <section class="register-form edit-card bg-white overflow-hidden">
            <div class="edit-section-header d-flex align-items-center gap-12">
              <img src="../imagenes/icon-section-stock.svg" width="40" height="40" alt="" class="flex-shrink-0">
              <div class="d-flex flex-column gap-1">
                <h2 class="form-section-title fw-bold m-0">Existencias</h2>
                <p class="form-section-text m-0">Stock actual y mínimo de la pieza.</p>
              </div>
            </div>
            <div class="edit-section-body edit-row">
              <!-- El stock es solo de consulta: se cambia desde la pantalla de ajuste de stock -->
              <div class="edit-col">
                <div class="edit-label-row d-flex align-items-center">
                  <label for="stock" class="edit-label">Stock</label>
                </div>
                <div class="edit-box edit-box-large edit-box-readonly d-flex align-items-center">
                  <input type="text" id="stock" class="edit-input edit-input-large" value="<?php echo htmlspecialchars($piece['stock'] . ($piece['stock'] === 1 ? ' unidad' : ' unidades')); ?>" readonly>
                  <img src="../imagenes/icon-lock-keyhole.svg" width="16" height="16" alt="" class="flex-shrink-0">
                </div>
                <p class="edit-help m-0">Solo vista. Para cambiarlo, usa <a href="ajusteStock.php?serial=<?php echo urlencode($piece['serial']); ?>" class="edit-help-link fw-medium">Ajuste de stock</a>.</p>
              </div>
              <div class="edit-col"><?php renderField('min', 'Stock mínimo', $piece['min'], 'units', true); ?></div>
            </div>
          </section>

          <!-- Ubicación -->
          <section class="register-form edit-card bg-white overflow-hidden">
            <div class="edit-section-header d-flex align-items-center gap-12">
              <img src="../imagenes/icon-section-location.svg" width="40" height="40" alt="" class="flex-shrink-0">
              <div class="d-flex flex-column gap-1">
                <h2 class="form-section-title fw-bold m-0">Ubicación</h2>
                <p class="form-section-text m-0">Localización de la pieza en el almacén.</p>
              </div>
            </div>
            <div class="edit-section-body d-flex flex-column">
              <?php renderField('location', 'Ubicación', $piece['location'], 'select', false, $locations); ?>
              <div class="edit-row">
                <div class="edit-col"><?php renderField('shelf', 'Anaquel', $piece['shelf'], 'text', false); ?></div>
                <div class="edit-col"><?php renderField('rack', 'Rack', $piece['rack'], 'text', false); ?></div>
              </div>
            </div>
          </section>
        </div>

        <p class="edit-note d-flex align-items-center gap-2 m-0">
          <img src="../imagenes/icon-edit-info.svg" width="16" height="16" alt="" class="flex-shrink-0">
          <span id="editNote"></span>
        </p>
      </form>
    </main>
  </div>

  <!-- Notificación: aparece al guardar y avisa que los cambios se hicieron correctamente -->
  <div id="successOverlay" class="success-overlay d-none">
    <div class="success-card bg-white d-flex flex-column align-items-center" role="dialog" aria-modal="true" aria-labelledby="successTitle" aria-describedby="successText">
      <img src="../imagenes/icon-success-modal.svg" width="56" height="56" alt="">
      <div class="d-flex flex-column gap-2 text-center">
        <h2 id="successTitle" class="success-title fw-bold m-0">Cambios guardados</h2>
        <p id="successText" class="success-text m-0">Los cambios se hicieron correctamente.</p>
      </div>
      <button type="button" id="successClose" class="btn btn-register adjust-button d-flex align-items-center justify-content-center fw-semibold">Aceptar</button>
    </div>
  </div>

<?php
// La página está dentro de una carpeta: la marca de agua sube un nivel para hallar su imagen.
$rootPath = '../';
require __DIR__ . '/../includes/watermark.php';
?>

  <script src="js/editarPieza.js"></script>
</body>
</html>
