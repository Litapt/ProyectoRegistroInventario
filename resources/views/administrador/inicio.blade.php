{{-- Inicio del administrador. Lo manda Administrador\InicioController --}}
@extends('layouts.panel')

@section('titulo', 'Inicio')
@section('menu', 'inicio')

@section('pagina')
    @include('partials.bienvenida', [
        'subtitulo' => 'Así va tu refaccionaria hoy.',
        'pendientes' => [
            'Tablero con ventas del día, de la semana y productos por reponer',
            'Ventas: registrar, consultar, cancelar y autorizar ediciones',
            'Clientes, Productos y Usuarios',
            'Alertas de stock bajo e incidencias',
            'Configuración de la tienda (datos, IVA, folios, seguridad)',
        ],
    ])
@endsection
