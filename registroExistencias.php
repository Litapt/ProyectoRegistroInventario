<?php
// Pantalla para registrar una pieza nueva en el inventario.
// Se llega aquí con el botón "Existencias" del panel del administrador.
// IMPORTANTE: por ahora el formulario NO guarda nada; falta la tabla de productos en MySQL.

// Lugares de almacenamiento que aparecen en la lista "Ubicación".
// Más adelante vendrán de la base de datos.
$locations = ['Almacén principal'];

// Recomendaciones de la columna derecha: ícono, título y texto
$tips = [
    ['icon' => 'icon-scan-barcode.svg', 'title' => 'Identificación única', 'text' => 'Verifica el número de serie para evitar registros duplicados.'],
    ['icon' => 'icon-layers.svg',       'title' => 'Cantidades reales',    'text' => 'Registra las unidades disponibles físicamente en el almacén.'],
    ['icon' => 'icon-map-pin.svg',      'title' => 'Ubicación precisa',    'text' => 'Confirma el almacén, el anaquel y el rack de la pieza.'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrar existencias | Luna headlights</title>

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

  <main class="register-main flex-grow-1 d-flex flex-column">

    <!-- Ruta de navegación (dónde estás) -->
    <nav aria-label="Ruta de navegación" class="breadcrumb-nav d-flex flex-wrap align-items-center">
      <a href="panelAdministrador.php" class="breadcrumb-link">Inicio</a>
      <img src="public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <span class="breadcrumb-link">Existencias</span>
      <img src="public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <span class="breadcrumb-current fw-semibold" aria-current="page">Nuevo registro</span>
    </nav>

    <!-- Título y enlace para regresar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex flex-column gap-2">
        <h1 class="page-title fw-bold m-0">Registrar existencias</h1>
        <p class="page-subtitle m-0">Agrega una pieza al inventario y define sus precios, cantidades y ubicación.</p>
      </div>
      <a href="panelAdministrador.php" class="app-link fw-semibold d-flex align-items-center gap-2">
        <img src="public/img/icon-arrow-left.svg" width="18" height="18" alt="">
        Volver a existencias
      </a>
    </div>

    <!-- Área de registro: formulario (izquierda) y apoyo (derecha) -->
    <div class="d-flex flex-column flex-xl-row align-items-start gap-4">

      <!-- Formulario de existencias -->
      <form id="stockForm" method="post" action="" class="register-form bg-white flex-grow-1 w-100">

        <!-- Sección: Datos de la pieza -->
        <section class="form-section d-flex flex-column">
          <div class="d-flex align-items-center gap-3">
            <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="public/img/icon-package-small.svg" width="20" height="20" alt="">
            </span>
            <div>
              <h2 class="form-section-title fw-bold m-0">Datos de la pieza</h2>
              <p class="form-section-text m-0">Identifica la pieza que vas a incorporar al inventario.</p>
            </div>
          </div>

          <div class="field-grid">
            <div>
              <label for="part_name" class="form-label app-label fw-medium mb-2">Nombre de la pieza <span class="required-mark">*</span></label>
              <input type="text" id="part_name" name="part_name" class="form-control app-input" placeholder="Rodamiento de bolas 6204" required>
            </div>
            <div>
              <label for="serial_number" class="form-label app-label fw-medium mb-2">Número de serie <span class="required-mark">*</span></label>
              <input type="text" id="serial_number" name="serial_number" class="form-control app-input" placeholder="SKF-6204-00852" required>
            </div>
            <div>
              <label for="brand" class="form-label app-label fw-medium mb-2">Marca <span class="required-mark">*</span></label>
              <input type="text" id="brand" name="brand" class="form-control app-input" placeholder="SKF" required>
            </div>
          </div>

          <div>
            <label for="description" class="form-label app-label fw-medium mb-2">Descripción de la pieza <span class="required-mark">*</span></label>
            <textarea id="description" name="description" class="form-control app-input app-textarea" rows="2" placeholder="Rodamiento rígido de bolas de una hilera, con sellos de goma. Medidas: 20 × 47 × 14 mm." required></textarea>
          </div>
        </section>

        <!-- Sección: Precios y existencias (fondo gris) -->
        <section class="form-section form-section-muted d-flex flex-column">
          <div class="d-flex align-items-center gap-3">
            <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="public/img/icon-sliders.svg" width="20" height="20" alt="">
            </span>
            <div>
              <h2 class="form-section-title fw-bold m-0">Precios y existencias</h2>
              <p class="form-section-text m-0">Establece los precios y el nivel inicial de stock.</p>
            </div>
          </div>

          <div class="field-grid">
            <div>
              <label for="purchase_price" class="form-label app-label fw-medium mb-2">Precio de compra <span class="required-mark">*</span></label>
              <div class="input-affix d-flex align-items-center bg-white">
                <span class="affix-text">$</span>
                <input type="number" id="purchase_price" name="purchase_price" class="form-control" min="0" step="0.01" placeholder="185.00" required>
              </div>
            </div>
            <div>
              <label for="sale_price" class="form-label app-label fw-medium mb-2">Precio de venta <span class="required-mark">*</span></label>
              <div class="input-affix d-flex align-items-center bg-white">
                <span class="affix-text">$</span>
                <input type="number" id="sale_price" name="sale_price" class="form-control" min="0" step="0.01" placeholder="280.00" required>
              </div>
            </div>
            <div>
              <label for="stock" class="form-label app-label fw-medium mb-2">Stock <span class="required-mark">*</span></label>
              <div class="input-affix d-flex align-items-center bg-white">
                <input type="number" id="stock" name="stock" class="form-control" min="0" step="1" placeholder="24" required>
                <span class="affix-text affix-small">unidades</span>
              </div>
            </div>
            <div>
              <label for="min_stock" class="form-label app-label fw-medium mb-2">Stock mínimo <span class="required-mark">*</span></label>
              <div class="input-affix d-flex align-items-center bg-white">
                <input type="number" id="min_stock" name="min_stock" class="form-control" min="0" step="1" placeholder="5" required>
                <span class="affix-text affix-small">unidades</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Sección: Ubicación -->
        <section class="form-section d-flex flex-column">
          <div class="d-flex align-items-center gap-3">
            <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="public/img/icon-map-pin.svg" width="20" height="20" alt="">
            </span>
            <div>
              <h2 class="form-section-title fw-bold m-0">Ubicación</h2>
              <p class="form-section-text m-0">Indica dónde se almacenará la pieza para encontrarla fácilmente.</p>
            </div>
          </div>

          <div class="field-grid">
            <div>
              <label for="location" class="form-label app-label fw-medium mb-2">Ubicación <span class="required-mark">*</span></label>
              <select id="location" name="location" class="form-select app-input app-select" required>
<?php foreach ($locations as $location): ?>
                <option value="<?= htmlspecialchars($location) ?>"><?= htmlspecialchars($location) ?></option>
<?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="shelf" class="form-label app-label fw-medium mb-2">Anaquel <span class="required-mark">*</span></label>
              <input type="text" id="shelf" name="shelf" class="form-control app-input" placeholder="A-03" required>
            </div>
            <div>
              <label for="rack" class="form-label app-label fw-medium mb-2">Rack <span class="required-mark">*</span></label>
              <input type="text" id="rack" name="rack" class="form-control app-input" placeholder="R-02" required>
            </div>
          </div>
        </section>

        <!-- Acciones del formulario -->
        <div class="form-actions d-flex flex-wrap align-items-center justify-content-between gap-3">
          <button type="reset" class="btn-clear fw-medium">Limpiar formulario</button>
          <div class="d-flex flex-wrap gap-3">
            <a href="panelAdministrador.php" class="btn btn-app-secondary fw-semibold d-flex align-items-center">Cancelar</a>
            <button type="submit" class="btn btn-app-primary btn-save fw-semibold d-flex align-items-center gap-2">
              <img src="public/img/icon-save.svg" width="20" height="20" alt="">
              Registrar existencia
            </button>
          </div>
        </div>
      </form>

      <!-- Columna de apoyo -->
      <aside class="register-aside d-flex flex-column gap-4 flex-shrink-0">

        <!-- Estado del registro -->
        <div class="aside-card aside-card-muted d-flex flex-column gap-4">
          <div class="d-flex align-items-center justify-content-between">
            <span class="status-icon d-flex align-items-center justify-content-center">
              <img src="public/img/icon-package-plus.svg" width="24" height="24" alt="">
            </span>
            <span class="status-badge fw-semibold">Sin guardar</span>
          </div>
          <div class="d-flex flex-column gap-2">
            <h2 class="aside-title fw-bold m-0">Nueva pieza en inventario</h2>
            <p class="aside-text m-0">Revisa los datos antes de registrar. Las existencias se actualizarán al guardar la pieza.</p>
          </div>
          <hr class="panel-divider m-0">
          <p class="aside-note m-0">Los campos marcados con <span class="required-mark fw-semibold">*</span> son obligatorios.</p>
        </div>

        <!-- Recomendaciones -->
        <div class="aside-card d-flex flex-column gap-4">
          <h2 class="tips-title fw-bold m-0">Antes de registrar</h2>
<?php foreach ($tips as $tip): ?>
          <div class="d-flex align-items-start gap-3">
            <img src="public/img/<?= htmlspecialchars($tip['icon']) ?>" width="20" height="20" alt="" class="flex-shrink-0">
            <div class="d-flex flex-column gap-1">
              <p class="tip-title fw-semibold m-0"><?= htmlspecialchars($tip['title']) ?></p>
              <p class="tip-text m-0"><?= htmlspecialchars($tip['text']) ?></p>
            </div>
          </div>
<?php endforeach; ?>
        </div>

        <!-- Aviso sobre el stock mínimo -->
        <div class="min-stock-note d-flex align-items-start gap-3">
          <img src="public/img/icon-bell.svg" width="20" height="20" alt="" class="flex-shrink-0">
          <div class="d-flex flex-column gap-1">
            <p class="note-title fw-semibold m-0">Anticípate al stock bajo</p>
            <p class="note-text m-0">El stock mínimo define cuándo necesitas reabastecer esta pieza.</p>
          </div>
        </div>

      </aside>
    </div>
  </main>

<?php require __DIR__ . '/includes/watermark.php'; ?>

</body>
</html>
