<?php
// Registrar entrada de stock de una pieza.
// Se llega aquí con el botón "Agregar stock" de detallePieza.php.
//
// Cómo funciona:
// 1. Se muestra el stock actual y un campo para escribir cuántas unidades entran.
// 2. Al dar clic en "Aceptar" el formulario se envía (POST) a esta misma página.
// 3. PHP revisa que la cantidad sea un número entero mayor que 0.
//    - Si está mal, se vuelve a mostrar el formulario con el error.
//    - Si está bien, se redirige a esta página con "saved" en la dirección y aparece
//      la ventana de éxito. (Redirigir evita que al recargar se registre dos veces).
//
// IMPORTANTE: todavía NO se guarda en MySQL porque falta la tabla de productos.
// Cuando exista, en el paso 3 irá una consulta con PDO, por ejemplo:
//   UPDATE products SET stock = stock + :quantity WHERE id = :id

require_once __DIR__ . '/../includes/product_search.php';
require_once __DIR__ . '/../includes/format.php';

// Límite de unidades por entrada, para evitar errores de dedo (por ejemplo 10000 en vez de 100)
const MAX_ENTRY_QUANTITY = 9999;

// Los datos llegan por GET (al abrir la página) o por POST (al dar clic en "Aceptar")
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

$productId = (int) ($input['id'] ?? 0);
$product = getProductById($productId);
$searchParams = readSearchParams($input);

// Enlaces para regresar al detalle de la pieza (con la misma búsqueda)
$detailUrl = 'detallePieza.php?' . http_build_query(['id' => $productId] + $searchParams);

$quantityValue = '';   // lo que escribió el usuario, para volver a mostrarlo si hay error
$errorMessage = '';
$saved = isset($_GET['saved']);

if ($product !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantityValue = trim($_POST['quantity'] ?? '');

    // ctype_digit revisa que solo tenga números (sin punto, signo ni letras)
    if ($quantityValue === '' || !ctype_digit($quantityValue)) {
        $errorMessage = 'Escribe una cantidad en números enteros, por ejemplo 10.';
    } elseif ((int) $quantityValue < 1) {
        $errorMessage = 'La cantidad debe ser mayor que 0.';
    } elseif ((int) $quantityValue > MAX_ENTRY_QUANTITY) {
        $errorMessage = 'La cantidad no puede ser mayor que ' . MAX_ENTRY_QUANTITY . ' unidades por entrada.';
    } else {
        // TODO: guardar en la base de datos cuando exista la tabla de productos.

        // Redirigir a esta misma página para mostrar la ventana de éxito
        $successUrl = 'entradaStock.php?' . http_build_query(['id' => $productId] + $searchParams + ['saved' => 1]);
        header('Location: ' . $successUrl);
        exit;
    }
}

// Si la pieza no existe, avisamos al navegador con el código 404 ("no encontrado")
if ($product === null) {
    http_response_code(404);
}

// Datos generales de la pieza para la tabla (dos datos por fila)
$generalRows = [];
if ($product !== null) {
    $generalRows = [
        [['Nombre de la pieza', $product['part_name']], ['Número de serie', $product['serial_number']]],
        [['Marca', $product['brand']], ['Descripción', $product['description']]],
        [['Precio de compra', formatMoney($product['purchase_price'])], ['Precio de venta', formatMoney($product['sale_price'])]],
        [['Ubicación', $product['location']], ['Anaquel / Rack', $product['shelf'] . ' / ' . $product['rack']]],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrada de stock | Luna headlights</title>

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

  <main class="register-main detail-main flex-grow-1 d-flex flex-column">

    <!-- Ruta de navegación (dónde estás) -->
    <nav aria-label="Ruta de navegación" class="breadcrumb-nav d-flex flex-wrap align-items-center">
      <a href="panelAdministrador.php" class="breadcrumb-link">Inicio</a>
      <img src="../public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <a href="panelExistencias.php" class="breadcrumb-link">Existencias</a>
      <img src="../public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <a href="<?= htmlspecialchars('registroEntrada.php?' . http_build_query($searchParams + ['search' => 1])) ?>" class="breadcrumb-link">Consultar inventario</a>
      <img src="../public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <a href="<?= htmlspecialchars($detailUrl) ?>" class="breadcrumb-link">Detalle de la pieza</a>
      <img src="../public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <span class="breadcrumb-current fw-semibold" aria-current="page">Entrada de stock</span>
    </nav>

    <!-- Regresar al detalle -->
    <a href="<?= htmlspecialchars($detailUrl) ?>" class="app-link fw-semibold d-flex align-items-center gap-2 align-self-start">
      <img src="../public/img/icon-arrow-left.svg" width="18" height="18" alt="">
      Volver al detalle de la pieza
    </a>

<?php if ($product === null): ?>
    <!-- La pieza no existe (por ejemplo, id equivocado en la dirección) -->
    <div class="aside-card aside-card-muted d-flex flex-column gap-2">
      <h1 class="aside-title fw-bold m-0">No encontramos esta pieza</h1>
      <p class="aside-text m-0">Es posible que el enlace esté incompleto. Regresa a los resultados y elige la pieza otra vez.</p>
    </div>
<?php else: ?>
    <!-- Título -->
    <div class="d-flex flex-column gap-2">
      <h1 class="page-title fw-bold m-0">Registrar entrada de stock</h1>
      <p class="page-subtitle m-0">Ingresa la cantidad de entrada y revisa los datos de la pieza antes de aceptar.</p>
    </div>

    <form id="stockEntryForm" method="post" action="entradaStock.php" class="d-flex flex-column gap-4" novalidate data-stock-form data-direction="in" data-preview-label="Stock después de la entrada">
      <!-- Datos ocultos: qué pieza es y la búsqueda anterior -->
      <input type="hidden" name="id" value="<?= htmlspecialchars((string) $productId) ?>">
<?php foreach ($searchParams as $name => $value): ?>
      <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string) $value) ?>">
<?php endforeach; ?>

      <!-- Entrada de stock -->
      <section class="detail-card bg-white" aria-labelledby="entryTitle">
        <div class="detail-card-header d-flex align-items-center gap-3">
          <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="../public/img/icon-boxes.svg" width="20" height="20" alt="">
          </span>
          <div>
            <h2 id="entryTitle" class="form-section-title fw-bold m-0">Entrada de stock</h2>
            <p class="form-section-text m-0">Consulta las existencias y agrega la cantidad que ingresa al almacén.</p>
          </div>
        </div>

        <div class="detail-card-body row g-4 m-0 align-items-center">
          <!-- Existencias actuales -->
          <div class="col-12 col-lg-7 p-0">
            <div class="row g-4 m-0">
              <div class="col-6 p-0">
                <p class="detail-label">Stock actual</p>
                <p class="detail-value-big fw-semibold m-0"><span id="currentStock" data-stock="<?= htmlspecialchars((string) $product['stock']) ?>"><?= htmlspecialchars((string) $product['stock']) ?></span> unidades</p>
              </div>
              <div class="col-6 p-0">
                <p class="detail-label">Stock mínimo</p>
                <p class="detail-value-big fw-semibold m-0"><?= htmlspecialchars((string) $product['min_stock']) ?> unidades</p>
              </div>
            </div>
          </div>

          <!-- Cantidad que entra -->
          <div class="col-12 col-lg-5 p-0">
            <label for="quantity" class="form-label app-label fw-medium mb-2 entry-label">Entrada de stock</label>
            <div class="d-flex gap-3">
              <div class="input-affix entry-affix d-flex align-items-center bg-white flex-grow-1<?= $errorMessage !== '' ? ' is-invalid' : '' ?>">
                <input type="number" id="quantity" name="quantity" class="form-control" min="1" max="<?= MAX_ENTRY_QUANTITY ?>" step="1" inputmode="numeric" placeholder="Ingresa la cantidad" value="<?= htmlspecialchars($quantityValue) ?>" aria-describedby="quantityHelp" data-max-message="La cantidad no puede ser mayor que <?= MAX_ENTRY_QUANTITY ?> unidades por entrada." required>
                <span class="affix-text affix-small">unidades</span>
              </div>
              <button type="button" id="previewButton" class="btn btn-app-primary btn-search-action fw-semibold d-flex align-items-center gap-2 flex-shrink-0">
                <img src="../public/img/icon-plus-white-small.svg" width="18" height="18" alt="">
                Agregar
              </button>
            </div>
            <!-- Ayuda / error / vista previa del nuevo stock (lo cambia el JavaScript) -->
            <p id="quantityHelp" class="entry-help m-0<?= $errorMessage !== '' ? ' is-error' : '' ?>" aria-live="polite"><?= $errorMessage !== '' ? htmlspecialchars($errorMessage) : 'Introduce la cantidad en unidades.' ?></p>
          </div>
        </div>
      </section>

      <!-- Datos generales de la pieza -->
      <section class="detail-card bg-white" aria-labelledby="generalTitle">
        <div class="detail-card-header general-header d-flex flex-wrap align-items-center gap-3">
          <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
            <img src="../public/img/icon-package-small.svg" width="20" height="20" alt="">
          </span>
          <div class="flex-grow-1">
            <h2 id="generalTitle" class="form-section-title fw-bold m-0">Datos generales de la pieza</h2>
            <p class="form-section-text m-0">Identificación, descripción, precios y ubicación registrados.</p>
          </div>
          <span class="status-badge fw-semibold">Solo consulta</span>
        </div>

        <div class="table-responsive">
          <table class="table general-table mb-0">
            <thead>
              <tr>
                <th scope="col">Campo</th>
                <th scope="col">Información</th>
                <th scope="col">Campo</th>
                <th scope="col">Información</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($generalRows as $row): ?>
              <tr>
<?php foreach ($row as [$label, $value]): ?>
                <th scope="row"><?= htmlspecialchars($label) ?></th>
                <td class="fw-medium"><?= htmlspecialchars($value) ?></td>
<?php endforeach; ?>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Botones -->
      <div class="d-flex flex-wrap justify-content-end gap-3">
        <button type="submit" class="btn btn-app-primary btn-search-action btn-entry fw-semibold">Aceptar</button>
        <a href="<?= htmlspecialchars($detailUrl) ?>" class="btn btn-app-secondary btn-search-action btn-entry fw-semibold d-flex align-items-center justify-content-center">Cancelar</a>
      </div>
    </form>
<?php endif; ?>

  </main>

<?php if ($saved && $product !== null): ?>
  <!-- Ventana de éxito: se abre sola al cargar la página después de aceptar -->
  <div class="modal fade" id="entrySuccessModal" tabindex="-1" aria-labelledby="entrySuccessTitle" aria-hidden="true" data-auto-show>
    <div class="modal-dialog modal-dialog-centered entry-success-dialog">
      <div class="modal-content success-modal entry-success d-flex flex-column align-items-center text-center">
        <img src="../public/img/icon-success.svg" width="56" height="56" alt="">
        <div class="d-flex flex-column gap-2">
          <h2 id="entrySuccessTitle" class="entry-success-title fw-bold m-0">Entrada de stock registrada</h2>
          <p class="entry-success-text m-0">La entrada de stock se guardó correctamente.</p>
        </div>
        <a href="<?= htmlspecialchars($detailUrl) ?>" class="btn btn-app-primary btn-search-action btn-entry fw-semibold d-flex align-items-center justify-content-center">Aceptar</a>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/watermark.php'; ?>

  <!-- JavaScript de Bootstrap (para la ventana de éxito) y nuestro script -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../public/js/entradaStock.js"></script>

</body>
</html>
