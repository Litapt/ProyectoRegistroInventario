{{--
    Hojas para imprimir (ticket de venta, cotización). Sin menú ni barra:
    sólo el documento y un botón "Imprimir" que no sale en el papel.
        @extends('layouts.impresion')
        @section('titulo', 'Ticket V-2026-0001')
        @section('ancho', '80mm')        ← ticket angosto; si no se pone, hoja carta
        @section('documento') ... @endsection
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo') | {{ \App\Support\Tienda::nombre() }}</title>
    <link rel="icon" href="{{ asset('imagenes/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/impresion.css') }}">
</head>
<body style="--ancho-documento: @yield('ancho', '210mm')">
    <div class="acciones no-imprimir">
        <button type="button" onclick="window.print()">Imprimir</button>
        <button type="button" class="secundario" onclick="window.close()">Cerrar</button>
    </div>

    <main class="documento">
        @yield('documento')
    </main>
</body>
</html>
