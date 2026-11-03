<?php
// Pantalla del administrador para registrar una pieza nueva.
// Por ahora el formulario solo envía los datos a registroExitoso.php para mostrarlos:
// todavía no guarda nada en la base de datos.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrar existencias | Tienda</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Estilos generales y estilos propios de esta pantalla -->
  <link href="../public/css/styles.css" rel="stylesheet">
  <link href="css/registrarPieza.css" rel="stylesheet">
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
      <span class="breadcrumb-current fw-semibold" aria-current="page">Nuevo registro</span>
    </nav>

    <!-- Título de la pantalla y enlace para volver -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex flex-column gap-2">
        <h1 class="page-title fw-bold m-0">Registrar existencias</h1>
        <p class="page-subtitle m-0">Agrega una pieza al inventario y define sus precios, cantidades y ubicación.</p>
      </div>
      <a href="piezasAdministrador.php" class="back-link d-flex align-items-center gap-2 fw-semibold text-decoration-none">
        <img src="../imagenes/icon-arrow-left.svg" width="18" height="18" alt="">
        Volver a existencias
      </a>
    </div>

    <div class="d-flex flex-column flex-xl-row align-items-start gap-4">

      <!-- Formulario de registro -->
      <form id="registerForm" method="post" action="registroExitoso.php" class="register-form bg-white overflow-hidden flex-grow-1 w-100">

        <section class="form-section d-flex flex-column p-4">
          <div class="d-flex align-items-center gap-12">
            <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../imagenes/icon-package-sm.svg" width="20" height="20" alt="">
            </span>
            <div class="d-flex flex-column gap-1">
              <h2 class="form-section-title fw-bold m-0">Datos de la pieza</h2>
              <p class="form-section-text m-0">Identifica la pieza que vas a incorporar al inventario.</p>
            </div>
          </div>
          <div class="field-row">
            <div>
              <label for="name" class="field-label fw-medium">Nombre de la pieza <span class="required-mark">*</span></label>
              <input type="text" id="name" name="name" class="form-control field-input" placeholder="Rodamiento de bolas 6204" required>
            </div>
            <div>
              <label for="serial_number" class="field-label fw-medium">Número de serie <span class="required-mark">*</span></label>
              <input type="text" id="serial_number" name="serial_number" class="form-control field-input" placeholder="SKF-6204-00852" required>
            </div>
            <div>
              <label for="brand" class="field-label fw-medium">Marca <span class="required-mark">*</span></label>
              <input type="text" id="brand" name="brand" class="form-control field-input" placeholder="SKF" required>
            </div>
          </div>
          <div>
            <label for="description" class="field-label fw-medium">Descripción de la pieza <span class="required-mark">*</span></label>
            <textarea id="description" name="description" class="form-control field-input field-textarea" placeholder="Rodamiento rígido de bolas de una hilera, con sellos de goma. Medidas: 20 × 47 × 14 mm." required></textarea>
          </div>
        </section>

        <hr class="form-divider m-0">

        <section class="form-section form-section-soft d-flex flex-column p-4">
          <div class="d-flex align-items-center gap-12">
            <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../imagenes/icon-sliders.svg" width="20" height="20" alt="">
            </span>
            <div class="d-flex flex-column gap-1">
              <h2 class="form-section-title fw-bold m-0">Precios y existencias</h2>
              <p class="form-section-text m-0">Establece los precios y el nivel inicial de stock.</p>
            </div>
          </div>
          <div class="field-row">
            <div>
              <label for="purchase_price" class="field-label fw-medium">Precio de compra <span class="required-mark">*</span></label>
              <div class="field-affix d-flex align-items-center gap-12 bg-white">
                <span class="affix-symbol">$</span>
                <input type="number" id="purchase_price" name="purchase_price" class="affix-input" min="0" step="0.01" placeholder="185.00" required>
              </div>
            </div>
            <div>
              <label for="sale_price" class="field-label fw-medium">Precio de venta <span class="required-mark">*</span></label>
              <div class="field-affix d-flex align-items-center gap-12 bg-white">
                <span class="affix-symbol">$</span>
                <input type="number" id="sale_price" name="sale_price" class="affix-input" min="0" step="0.01" placeholder="280.00" required>
              </div>
            </div>
            <div>
              <label for="stock" class="field-label fw-medium">Stock <span class="required-mark">*</span></label>
              <div class="field-affix d-flex align-items-center gap-12 bg-white">
                <input type="number" id="stock" name="stock" class="affix-input" min="0" step="1" placeholder="24" required>
                <span class="affix-unit">unidades</span>
              </div>
            </div>
            <div>
              <label for="min_stock" class="field-label fw-medium">Stock mínimo <span class="required-mark">*</span></label>
              <div class="field-affix d-flex align-items-center gap-12 bg-white">
                <input type="number" id="min_stock" name="min_stock" class="affix-input" min="0" step="1" placeholder="5" required>
                <span class="affix-unit">unidades</span>
              </div>
            </div>
          </div>
        </section>

        <hr class="form-divider m-0">

        <section class="form-section d-flex flex-column p-4">
          <div class="d-flex align-items-center gap-12">
            <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../imagenes/icon-map-pin.svg" width="20" height="20" alt="">
            </span>
            <div class="d-flex flex-column gap-1">
              <h2 class="form-section-title fw-bold m-0">Ubicación</h2>
              <p class="form-section-text m-0">Indica dónde se almacenará la pieza para encontrarla fácilmente.</p>
            </div>
          </div>
          <div class="field-row">
            <div>
              <label for="location" class="field-label fw-medium">Ubicación <span class="required-mark">*</span></label>
              <select id="location" name="location" class="form-select field-input field-select" required>
                <option value="Almacén principal">Almacén principal</option>
                <option value="Almacén secundario">Almacén secundario</option>
              </select>
            </div>
            <div>
              <label for="shelf" class="field-label fw-medium">Anaquel <span class="required-mark">*</span></label>
              <input type="text" id="shelf" name="shelf" class="form-control field-input" placeholder="A-03" required>
            </div>
            <div>
              <label for="rack" class="field-label fw-medium">Rack <span class="required-mark">*</span></label>
              <input type="text" id="rack" name="rack" class="form-control field-input" placeholder="R-02" required>
            </div>
          </div>
        </section>

        <!-- Acciones: limpiar, cancelar o registrar -->
        <div class="form-actions d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
          <button type="reset" class="btn-clear fw-medium">Limpiar formulario</button>
          <div class="d-flex flex-wrap gap-12">
            <a href="piezasAdministrador.php" class="btn btn-cancel d-flex align-items-center justify-content-center fw-semibold">Cancelar</a>
            <button type="submit" class="btn btn-register d-flex align-items-center justify-content-center gap-2 fw-semibold">
              <img src="../imagenes/icon-save.svg" width="20" height="20" alt="">
              Registrar existencia
            </button>
          </div>
        </div>
      </form>

      <!-- Columna de apoyo: estado del registro y recomendaciones -->
      <aside class="support-panel d-flex flex-column gap-4 flex-shrink-0">

        <div class="support-card support-card-soft d-flex flex-column gap-4 p-4">
          <div class="d-flex align-items-center justify-content-between">
            <span class="status-icon d-flex align-items-center justify-content-center">
              <img src="../imagenes/icon-package-plus.svg" width="24" height="24" alt="">
            </span>
            <span class="status-tag fw-semibold">Sin guardar</span>
          </div>
          <div class="d-flex flex-column gap-2">
            <h2 class="status-title fw-bold m-0">Nueva pieza en inventario</h2>
            <p class="status-text m-0">Revisa los datos antes de registrar. Las existencias se actualizarán al guardar la pieza.</p>
          </div>
          <hr class="form-divider m-0">
          <p class="required-note m-0">Los campos marcados con <span class="required-mark fw-semibold">*</span> son obligatorios.</p>
        </div>

        <div class="support-card d-flex flex-column gap-4 p-4">
          <h2 class="tips-title fw-bold m-0">Antes de registrar</h2>
          <div class="d-flex align-items-start gap-12">
            <img src="../imagenes/icon-scan-barcode.svg" width="20" height="20" alt="" class="flex-shrink-0">
            <div class="tip-content d-flex flex-column">
              <h3 class="tip-title fw-semibold m-0">Identificación única</h3>
              <p class="tip-text m-0">Verifica el número de serie para evitar registros duplicados.</p>
            </div>
          </div>
          <div class="d-flex align-items-start gap-12">
            <img src="../imagenes/icon-layers.svg" width="20" height="20" alt="" class="flex-shrink-0">
            <div class="tip-content d-flex flex-column">
              <h3 class="tip-title fw-semibold m-0">Cantidades reales</h3>
              <p class="tip-text m-0">Registra las unidades disponibles físicamente en el almacén.</p>
            </div>
          </div>
          <div class="d-flex align-items-start gap-12">
            <img src="../imagenes/icon-map-pin.svg" width="20" height="20" alt="" class="flex-shrink-0">
            <div class="tip-content d-flex flex-column">
              <h3 class="tip-title fw-semibold m-0">Ubicación precisa</h3>
              <p class="tip-text m-0">Confirma el almacén, el anaquel y el rack de la pieza.</p>
            </div>
          </div>
        </div>

        <div class="stock-tip d-flex align-items-start gap-12">
          <img src="../imagenes/icon-bell-sm.svg" width="20" height="20" alt="" class="flex-shrink-0">
          <div class="tip-content d-flex flex-column">
            <h3 class="stock-tip-title fw-semibold m-0">Anticípate al stock bajo</h3>
            <p class="stock-tip-text m-0">El stock mínimo define cuándo necesitas reabastecer esta pieza.</p>
          </div>
        </div>

      </aside>
    </div>
  </main>

<?php
// La página está dentro de una carpeta: la marca de agua sube un nivel para hallar su imagen.
$rootPath = '../';
require __DIR__ . '/../includes/watermark.php';
?>

</body>
</html>
