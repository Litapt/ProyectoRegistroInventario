<?php
// Página de inicio de sesión de la refaccionaria.
// Por ahora solo muestra el formulario; todavía no revisa usuarios en la base de datos.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión | Luna headlights</title>

  <!-- Fuente Inter (la que usa el diseño de Figma) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap desde internet (CDN), sin instalar nada -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Nuestros estilos propios (colores y medidas del diseño) -->
  <link href="public/css/styles.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- Barra de menú superior -->
  <header class="app-header d-flex align-items-center justify-content-between border-bottom bg-white">
    <a href="index.php" class="d-flex align-items-center gap-3 text-decoration-none">
      <span class="logo-box d-flex align-items-center justify-content-center">
        <img src="public/img/logo.svg" width="20" height="20" alt="">
      </span>
      <span class="brand-name fw-bold">Luna headlights</span>
    </a>
    <a href="#" class="header-link fw-medium text-decoration-none">Ayuda</a>
  </header>

  <!-- Contenido principal: tarjeta centrada -->
  <main class="flex-grow-1 d-flex align-items-center justify-content-center px-3 py-5">
    <div class="login-card bg-white d-flex flex-column">

      <!-- Encabezado: candado, título y descripción -->
      <div class="d-flex flex-column align-items-center gap-3 text-center">
        <span class="icon-box d-flex align-items-center justify-content-center">
          <img src="public/img/lock.svg" width="28" height="28" alt="">
        </span>
        <div class="d-flex flex-column gap-2">
          <h1 class="login-title fw-bold m-0">Iniciar sesión</h1>
          <p class="login-subtitle m-0">Ingresa tus datos para continuar en tu cuenta.</p>
        </div>
      </div>

      <!-- Formulario: por ahora solo lleva al panel, sin revisar usuario ni contraseña -->
      <form id="loginForm" method="post" action="administrador/panelAdministrador.php" class="d-flex flex-column gap-login">
        <div class="d-flex flex-column gap-field">
          <div>
            <label for="email" class="form-label app-label fw-medium mb-2">Correo electrónico</label>
            <input type="email" id="email" name="email" class="form-control app-input" placeholder="tu@email.com" required>
          </div>

          <div>
            <label for="password" class="form-label app-label fw-medium mb-2">Contraseña</label>
            <div class="position-relative">
              <input type="password" id="password" name="password" class="form-control app-input pe-5" placeholder="••••••••" required>
              <!-- Botón del ojo: muestra u oculta la contraseña -->
              <button type="button" id="togglePassword" class="btn-eye position-absolute top-50 translate-middle-y" aria-label="Mostrar contraseña">
                <img src="public/img/eye.svg" width="20" height="20" alt="">
              </button>
            </div>
          </div>

          <div class="text-end">
            <a href="#" class="app-link fw-medium">¿Olvidaste tu contraseña?</a>
          </div>
        </div>

        <!-- Acciones: botón principal y registro -->
        <div class="d-flex flex-column gap-3">
          <button type="submit" class="btn btn-app-primary w-100 fw-semibold">Iniciar sesión</button>
          <p class="register-text text-center m-0">
            ¿No tienes cuenta? <a href="#" class="app-link fw-semibold ms-1">Regístrate</a>
          </p>
        </div>
      </form>

    </div>
  </main>

<?php require __DIR__ . '/includes/watermark.php'; ?>

  <script src="public/js/login.js"></script>
</body>
</html>
