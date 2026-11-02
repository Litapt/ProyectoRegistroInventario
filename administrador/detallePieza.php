<?php
// Detalle de la pieza seleccionada (solo consulta).
// Se llega aquí al dar clic en una fila de la tabla de registroEntrada.php o registroSalida.php.
// La dirección trae el id de la pieza y la búsqueda que se hizo, por ejemplo:
//   detallePieza.php?id=1&part_name=rodamiento&brand=skf&page=1
// Así "Volver a resultados de búsqueda" regresa a la misma tabla.

require_once __DIR__ . '/../includes/product_search.php';
require_once __DIR__ . '/../includes/format.php';
require_once __DIR__ . '/../includes/stock_flow.php';

// ¿Venimos del registro de entrada o de salida? (?mode=salida en la dirección)
$flow = getStockFlow(readFlowMode($_GET));

// Id de la pieza (si no es un número válido queda en 0 y no se encuentra nada)
$productId = (int) ($_GET['id'] ?? 0);
$product = getProductById($productId);

// Búsqueda anterior: sirve para regresar a la misma tabla
$searchParams = readSearchParams($_GET);
$backUrl = $flow['searchPage'] . '?' . http_build_query($searchParams + ['search' => 1]);

// Botón principal ("Agregar stock" o "Registrar salida"): lleva al formulario de esta pieza
$addStockUrl = $flow['movementPage'] . '?' . http_build_query(['id' => $productId] + $searchParams);

// Si la pieza no existe, avisamos al navegador con el código 404 ("no encontrado")
if ($product === null) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detalle de la pieza | Luna headlights</title>

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
      <a href="<?= htmlspecialchars($backUrl) ?>" class="breadcrumb-link">Consultar inventario</a>
      <img src="../public/img/icon-chevron-right.svg" width="14" height="14" alt="">
      <span class="breadcrumb-current fw-semibold" aria-current="page">Detalle de la pieza</span>
    </nav>

    <!-- Regresar a la tabla con la misma búsqueda -->
    <a href="<?= htmlspecialchars($backUrl) ?>" class="app-link fw-semibold d-flex align-items-center gap-2 align-self-start">
      <img src="../public/img/icon-arrow-left.svg" width="18" height="18" alt="">
      Volver a resultados de búsqueda
    </a>

<?php if ($product === null): ?>
    <!-- La pieza no existe (por ejemplo, id equivocado en la dirección) -->
    <div class="aside-card aside-card-muted d-flex flex-column gap-2">
      <h1 class="aside-title fw-bold m-0">No encontramos esta pieza</h1>
      <p class="aside-text m-0">Es posible que el enlace esté incompleto. Regresa a los resultados y elige la pieza otra vez.</p>
    </div>
<?php else: ?>
    <!-- Título y botón principal -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex flex-column gap-2">
        <h1 class="page-title fw-bold m-0">Detalle de la pieza</h1>
        <p class="page-subtitle m-0"><?= htmlspecialchars($flow['detailSubtitle']) ?></p>
      </div>
      <!-- Abre el formulario de entrada o de salida de esta pieza -->
      <a href="<?= htmlspecialchars($addStockUrl) ?>" class="btn btn-app-primary btn-search-action fw-semibold d-flex align-items-center gap-2">
        <img src="../public/img/<?= htmlspecialchars($flow['detailIcon']) ?>" width="18" height="18" alt="">
        <?= htmlspecialchars($flow['detailButton']) ?>
      </a>
    </div>

    <!-- Datos de la pieza -->
    <section class="detail-card bg-white" aria-labelledby="partDataTitle">
      <div class="detail-card-header d-flex flex-wrap align-items-center gap-3">
        <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="../public/img/icon-package-small.svg" width="20" height="20" alt="">
        </span>
        <div class="flex-grow-1">
          <h2 id="partDataTitle" class="form-section-title fw-bold m-0">Datos de la pieza</h2>
          <p class="form-section-text m-0">Información general e identificación.</p>
        </div>
        <span class="status-badge fw-semibold">Solo consulta</span>
      </div>

      <div class="detail-card-body d-flex flex-column gap-4">
        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <p class="detail-label">Nombre de la pieza</p>
            <p class="detail-value-name fw-semibold m-0"><?= htmlspecialchars($product['part_name']) ?></p>
          </div>
          <div class="col-6 col-lg-3">
            <p class="detail-label">Número de serie</p>
            <p class="detail-value fw-medium m-0"><?= htmlspecialchars($product['serial_number']) ?></p>
          </div>
          <div class="col-6 col-lg-3">
            <p class="detail-label">Marca</p>
            <p class="detail-value fw-medium m-0"><?= htmlspecialchars($product['brand']) ?></p>
          </div>
        </div>
        <div class="detail-description">
          <p class="detail-label">Descripción de la pieza</p>
          <p class="detail-value m-0"><?= htmlspecialchars($product['description']) ?></p>
        </div>
      </div>
    </section>

    <!-- Precios, existencias y ubicación (3 tarjetas; en pantallas chicas se apilan) -->
    <div class="row g-4">

      <div class="col-12 col-lg-4 d-flex">
        <section class="detail-card detail-card-tall bg-white w-100" aria-labelledby="pricesTitle">
          <div class="detail-card-header d-flex align-items-center gap-3">
            <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../public/img/icon-tag.svg" width="20" height="20" alt="">
            </span>
            <div>
              <h2 id="pricesTitle" class="form-section-title fw-bold m-0">Precios</h2>
              <p class="form-section-text m-0">Valores registrados de compra y venta.</p>
            </div>
          </div>
          <div class="detail-card-body row g-4 m-0">
            <div class="col-6 p-0">
              <p class="detail-label">Precio de compra</p>
              <p class="detail-value-big fw-semibold m-0"><?= htmlspecialchars(formatMoney($product['purchase_price'])) ?></p>
            </div>
            <div class="col-6 p-0">
              <p class="detail-label">Precio de venta</p>
              <p class="detail-value-big fw-semibold m-0"><?= htmlspecialchars(formatMoney($product['sale_price'])) ?></p>
            </div>
          </div>
        </section>
      </div>

      <div class="col-12 col-lg-4 d-flex">
        <section class="detail-card detail-card-tall bg-white w-100" aria-labelledby="stockTitle">
          <div class="detail-card-header d-flex align-items-center gap-3">
            <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../public/img/icon-boxes.svg" width="20" height="20" alt="">
            </span>
            <div>
              <h2 id="stockTitle" class="form-section-title fw-bold m-0">Existencias</h2>
              <p class="form-section-text m-0">Stock actual y mínimo de la pieza.</p>
            </div>
          </div>
          <div class="detail-card-body row g-4 m-0">
            <div class="col-6 p-0">
              <p class="detail-label">Stock</p>
              <p class="detail-value-big fw-semibold m-0"><?= htmlspecialchars((string) $product['stock']) ?> unidades</p>
            </div>
            <div class="col-6 p-0">
              <p class="detail-label">Stock mínimo</p>
              <p class="detail-value-big fw-semibold m-0"><?= htmlspecialchars((string) $product['min_stock']) ?> unidades</p>
            </div>
          </div>
        </section>
      </div>

      <div class="col-12 col-lg-4 d-flex">
        <section class="detail-card detail-card-tall bg-white w-100" aria-labelledby="locationTitle">
          <div class="detail-card-header d-flex align-items-center gap-3">
            <span class="section-symbol d-flex align-items-center justify-content-center flex-shrink-0">
              <img src="../public/img/icon-map-pin.svg" width="20" height="20" alt="">
            </span>
            <div>
              <h2 id="locationTitle" class="form-section-title fw-bold m-0">Ubicación</h2>
              <p class="form-section-text m-0">Localización de la pieza en el almacén.</p>
            </div>
          </div>
          <div class="detail-card-body d-flex flex-column gap-4">
            <div>
              <p class="detail-label">Ubicación</p>
              <p class="detail-value-mid fw-semibold m-0"><?= htmlspecialchars($product['location']) ?></p>
            </div>
            <div class="row g-4 m-0">
              <div class="col-6 p-0">
                <p class="detail-label">Anaquel</p>
                <p class="detail-value-mid fw-semibold m-0"><?= htmlspecialchars($product['shelf']) ?></p>
              </div>
              <div class="col-6 p-0">
                <p class="detail-label">Rack</p>
                <p class="detail-value-mid fw-semibold m-0"><?= htmlspecialchars($product['rack']) ?></p>
              </div>
            </div>
          </div>
        </section>
      </div>

    </div>

    <!-- Nota final -->
    <p class="search-hint d-flex align-items-center gap-2 m-0 px-1">
      <img src="../public/img/icon-info.svg" width="16" height="16" alt="" class="flex-shrink-0">
      <?= htmlspecialchars($flow['detailNote']) ?>
    </p>
<?php endif; ?>

  </main>

<?php require __DIR__ . '/../includes/watermark.php'; ?>

</body>
</html>
