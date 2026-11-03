<?php
// Pantalla de confirmación del registro de una pieza.
// Por ahora solo muestra los datos que llegan del formulario: no guarda nada en la base de datos.

// Nombres de los campos que envía registrarPieza.php
$fieldNames = array(
    'name', 'serial_number', 'brand', 'description', 'purchase_price',
    'sale_price', 'stock', 'min_stock', 'location', 'shelf', 'rack',
);

// Copiamos cada dato quitando los espacios que sobren al inicio y al final
$piece = array();
foreach ($fieldNames as $fieldName) {
    $piece[$fieldName] = isset($_POST[$fieldName]) ? trim((string) $_POST[$fieldName]) : '';
}

// Revisa que todos los campos tengan algo y que las cantidades sean números válidos
$isComplete = !in_array('', $piece, true);
foreach (array('purchase_price', 'sale_price', 'stock', 'min_stock') as $numberField) {
    if (!is_numeric($piece[$numberField]) || (float) $piece[$numberField] < 0) {
        $isComplete = false;
    }
}

// Si alguien abre esta página sin llenar el formulario, lo regresamos a él
if (!$isComplete) {
    header('Location: registrarPieza.php');
    exit;
}

// Convierte 185 en "$185.00"
function formatPrice($amount)
{
    return '$' . number_format((float) $amount, 2);
}

// Convierte 24 en "24 unidades" y 1 en "1 unidad"
function formatUnits($quantity)
{
    $quantity = (int) $quantity;
    return $quantity . ($quantity === 1 ? ' unidad' : ' unidades');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro exitoso | Tienda</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Estilos generales, los del registro de piezas y los propios de esta pantalla -->
  <link href="../public/css/styles.css" rel="stylesheet">
  <link href="css/registrarPieza.css" rel="stylesheet">
  <link href="css/registroExitoso.css" rel="stylesheet">
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

    <!-- Mensaje de confirmación -->
    <div class="success-banner d-flex align-items-center gap-4">
      <img src="../imagenes/icon-success.svg" width="64" height="64" alt="" class="flex-shrink-0">
      <div class="d-flex flex-column gap-2">
        <h1 class="page-title fw-bold m-0">¡Existencias registradas con éxito!</h1>
        <p class="success-text m-0">La pieza se agregó al inventario. Sus precios, cantidades y ubicación se guardaron correctamente.</p>
      </div>
    </div>

    <div class="d-flex flex-column flex-xl-row align-items-start gap-4">

      <!-- Resumen de la pieza registrada -->
      <div class="register-form bg-white overflow-hidden flex-grow-1 w-100">

        <section class="summary-section d-flex flex-column gap-4 p-4">
          <div class="d-flex align-items-center gap-12">
            <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../imagenes/icon-package-sm.svg" width="20" height="20" alt="">
            </span>
            <h2 class="form-section-title fw-bold m-0">Datos de la pieza</h2>
          </div>
          <div class="summary-row">
            <div>
              <p class="summary-label">Nombre de la pieza</p>
              <p class="summary-value fw-semibold"><?php echo htmlspecialchars($piece['name']); ?></p>
            </div>
            <div>
              <p class="summary-label">Número de serie</p>
              <p class="summary-value fw-semibold"><?php echo htmlspecialchars($piece['serial_number']); ?></p>
            </div>
            <div>
              <p class="summary-label">Marca</p>
              <p class="summary-value fw-semibold"><?php echo htmlspecialchars($piece['brand']); ?></p>
            </div>
          </div>
          <div>
            <p class="summary-label">Descripción</p>
            <p class="summary-value fw-semibold"><?php echo htmlspecialchars($piece['description']); ?></p>
          </div>
        </section>

        <hr class="form-divider m-0">

        <section class="form-section form-section-soft d-flex flex-column p-4">
          <div class="d-flex align-items-center gap-12">
            <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../imagenes/icon-sliders.svg" width="20" height="20" alt="">
            </span>
            <h2 class="form-section-title fw-bold m-0">Precios y existencias</h2>
          </div>
          <div class="summary-row">
            <div>
              <p class="summary-label">Precio de compra</p>
              <p class="summary-value summary-value-large fw-semibold"><?php echo htmlspecialchars(formatPrice($piece['purchase_price'])); ?></p>
            </div>
            <div>
              <p class="summary-label">Precio de venta</p>
              <p class="summary-value summary-value-large fw-semibold"><?php echo htmlspecialchars(formatPrice($piece['sale_price'])); ?></p>
            </div>
            <div>
              <p class="summary-label">Stock</p>
              <p class="summary-value summary-value-large fw-semibold"><?php echo htmlspecialchars(formatUnits($piece['stock'])); ?></p>
            </div>
            <div>
              <p class="summary-label">Stock mínimo</p>
              <p class="summary-value summary-value-large fw-semibold"><?php echo htmlspecialchars(formatUnits($piece['min_stock'])); ?></p>
            </div>
          </div>
        </section>

        <hr class="form-divider m-0">

        <section class="form-section d-flex flex-column p-4">
          <div class="d-flex align-items-center gap-12">
            <span class="form-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../imagenes/icon-map-pin.svg" width="20" height="20" alt="">
            </span>
            <h2 class="form-section-title fw-bold m-0">Ubicación</h2>
          </div>
          <div class="summary-row">
            <div>
              <p class="summary-label">Ubicación</p>
              <p class="summary-value fw-semibold"><?php echo htmlspecialchars($piece['location']); ?></p>
            </div>
            <div>
              <p class="summary-label">Anaquel</p>
              <p class="summary-value fw-semibold"><?php echo htmlspecialchars($piece['shelf']); ?></p>
            </div>
            <div>
              <p class="summary-label">Rack</p>
              <p class="summary-value fw-semibold"><?php echo htmlspecialchars($piece['rack']); ?></p>
            </div>
          </div>
        </section>
      </div>

      <!-- Siguientes pasos -->
      <aside class="support-panel flex-shrink-0">
        <div class="support-card d-flex flex-column gap-4 p-4">
          <h2 class="tips-title fw-bold m-0">¿Qué deseas hacer ahora?</h2>
          <div class="next-step d-flex flex-column">
            <a href="registrarPieza.php" class="btn btn-register w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">
              <img src="../imagenes/icon-arrow-left-white.svg" alt="">
              Volver a registrar existencias
            </a>
            <p class="next-step-text m-0">Regresa a la pantalla de registro para agregar otra pieza.</p>
          </div>
          <hr class="form-divider m-0">
          <div class="next-step d-flex flex-column">
            <a href="piezasAdministrador.php" class="btn btn-cancel btn-next-menu w-100 fw-semibold">Ir al menú principal del almacenista</a>
            <p class="next-step-text m-0">Vuelve a las opciones de gestión de piezas y productos.</p>
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
