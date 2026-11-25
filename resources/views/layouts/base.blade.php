{{--
    Estructura común de todas las páginas.
    Cada página la usa con @extends('layouts.base') y llena:
        @section('titulo')     texto de la pestaña
        @section('clase-body') clases del <body>
        @section('contenido')  el contenido
    y agrega su CSS con @push('estilos') y su JavaScript con @push('scripts').

    asset('css/...') arma la dirección correcta de un archivo de public/,
    sin importar en qué carpeta esté la vista.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo') | {{ \App\Support\Tienda::nombre() }}</title>

    <link rel="icon" href="{{ asset('imagenes/logo.svg') }}" type="image/svg+xml">

    <!-- Fuente Inter (la del diseño de Figma) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap. "integrity" hace que el navegador lo rechace si alguien lo modificó -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    @stack('estilos')
</head>
<body class="@yield('clase-body')">

    {{-- Todo lo que queda "detrás" cuando se abre una ventana de aviso --}}
    <div id="pageContent">
        {{-- Alerta que mandan los controladores con ->with('alerta', [tipo, mensaje]) --}}
        @if (session('alerta'))
            <div class="alert alert-{{ session('alerta')[0] }} text-center mb-0 rounded-0" role="alert">
                {{ session('alerta')[1] }}
            </div>
        @endif

        @yield('contenido')
    </div>

    {{-- Ventanas de aviso (confirmaciones y mensajes de éxito) con @push('modales') --}}
    @stack('modales')

    <!-- Marca de agua del equipo -->
    <img src="{{ asset('imagenes/ImagenEquipoNF-192.png') }}" class="watermark" alt="">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
