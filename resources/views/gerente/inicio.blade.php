{{-- Inicio del gerente. Lo manda Gerente\InicioController --}}
@extends('layouts.panel')

@section('titulo', 'Inicio')
@section('menu', 'inicio')

@section('pagina')
    @include('partials.bienvenida', [
        'subtitulo' => 'Revisa la operación de la tienda y autoriza solicitudes.',
        'pendientes' => [
            'Tablero con ventas del día y productos por reponer',
            'Solicitudes por autorizar: ediciones de venta, cambios de productos, ajustes e incidencias',
            'Ventas, Clientes y Productos',
            'Alertas de stock bajo e incidencias',
        ],
    ])
@endsection
