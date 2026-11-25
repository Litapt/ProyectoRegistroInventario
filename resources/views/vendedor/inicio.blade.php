{{-- Inicio del vendedor. Lo manda Vendedor\InicioController --}}
@extends('layouts.barraSuperior')

@section('titulo', 'Inicio')

@section('pagina')
    @include('partials.bienvenida', [
        'subtitulo' => 'Encuentra pedidos, sus estados, sus productos y el precio de venta.',
        'pendientes' => [
            'Consultar pedidos por folio, fecha, cliente, trabajador y estado',
            'Registrar, editar y cancelar pedidos',
            'Cotizaciones',
            'Solicitar devoluciones',
        ],
    ])
@endsection
