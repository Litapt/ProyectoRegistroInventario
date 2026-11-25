{{-- Inicio del cajero. Lo manda Cajero\InicioController --}}
@extends('layouts.barraSuperior')

@section('titulo', 'Inicio')

@section('pagina')
    @include('partials.bienvenida', [
        'subtitulo' => 'Cobra ventas y registra pagos.',
        'pendientes' => [
            'Registrar ventas y cobrar (efectivo, tarjeta, transferencia o dólares)',
            'Abonos de ventas a crédito',
            'Solicitar la edición de una venta',
            'Apertura y corte de caja (cuando se diseñe)',
        ],
    ])
@endsection
