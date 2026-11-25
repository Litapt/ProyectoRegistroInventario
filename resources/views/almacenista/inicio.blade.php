{{-- Inicio del almacenista. Lo manda Almacenista\InicioController --}}
@extends('layouts.barraSuperior')

@section('titulo', 'Inicio')

@section('pagina')
    @include('partials.bienvenida', [
        'subtitulo' => 'Encuentra piezas y consulta sus existencias, precios y ubicación.',
        'pendientes' => [
            'Inventario: buscar por código, nombre, marca, ubicación, precios y stock',
            'Registrar productos nuevos',
            'Editar o desactivar productos (con autorización del gerente)',
            'Ajustes de stock: entradas, salidas y conteos',
            'Reportar incidencias',
        ],
    ])
@endsection
