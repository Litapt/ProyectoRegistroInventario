{{--
    "Ventas del día" de un trabajador, para entregarlo con el corte de caja.
    Lo usan el administrador, el gerente y el cajero (App\Support\ReporteVentas).
    Se imprime sin abrir otra pestaña (javascript/imprimir.js).
--}}
@extends('layouts.impresion')

@section('titulo', 'Ventas del ' . $dia->format('d/m/Y'))
@section('ancho', '80mm')

@section('documento')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
    @endphp

    <div class="centro">
        <p class="tienda">{{ $tienda['nombre'] }}</p>
        @if ($tienda['rfc'])
            <div class="tenue">RFC {{ $tienda['rfc'] }}</div>
        @endif
        <p class="titulo-doc">VENTAS DEL DÍA</p>
        <div class="negrita">{{ $dia->format('d/m/Y') }}</div>
    </div>

    <hr class="linea">
    <div>Trabajador: {{ $trabajador->nombreCompleto() }} ({{ $trabajador->rolTexto() }})</div>
    @foreach ($turnos as $turno)
        <div class="tenue">
            {{ $turno->nombre() }} · abrió {{ $turno->fechaApertura->format('h:i a') }} con {{ $dinero($turno->fondoInicial) }}
            @if ($turno->fechaCierre) · cerró {{ $turno->fechaCierre->format('h:i a') }} @endif
        </div>
    @endforeach
    <div class="tenue">Impreso: {{ now()->format('d/m/Y h:i a') }} · Autorizó: {{ $autorizo->nombreCompleto() }}</div>
    <hr class="linea">

    @include('compartido.listasVentas')

    <hr class="linea">

    {{-- Resumen --}}
    <table class="totales">
        <tr><td>Total vendido</td><td class="derecha">{{ $dinero($totalVendido) }}</td></tr>
        @if ($pendiente > 0)
            <tr><td class="tenue">Quedó pendiente (crédito)</td><td class="derecha tenue">{{ $dinero($pendiente) }}</td></tr>
        @endif
        @if ($totalCancelado > 0)
            <tr><td class="tenue">Canceladas (no cuentan)</td><td class="derecha tenue">{{ $dinero($totalCancelado) }}</td></tr>
        @endif
    </table>

    <p class="negrita" style="margin: 12px 0 4px">Cobrado por método de pago</p>
    <table class="totales">
        @forelse ($porMetodo as $metodo => $monto)
            <tr><td>{{ $metodo }}</td><td class="derecha">{{ $dinero($monto) }}</td></tr>
        @empty
            <tr><td class="tenue">Sin cobros</td><td class="derecha tenue">{{ $dinero(0) }}</td></tr>
        @endforelse
        @if ($reembolsado > 0)
            <tr><td>Devoluciones (reembolso)</td><td class="derecha">−{{ $dinero($reembolsado) }}</td></tr>
        @endif
        <tr class="total"><td>TOTAL COBRADO</td><td class="derecha">{{ $dinero($totalCobrado - $reembolsado) }}</td></tr>
        <tr><td class="tenue">Efectivo a entregar</td><td class="derecha negrita">{{ $dinero($efectivoNeto) }}</td></tr>
    </table>
    @if ($turnos->isNotEmpty())
        <p class="tenue" style="margin: 4px 0 0">El efectivo a entregar no incluye el fondo inicial de la caja.</p>
    @endif

    <div style="margin-top: 36px; display: flex; gap: 16px">
        <div class="centro" style="flex: 1; border-top: 1px solid #111827; padding-top: 4px">Entregó</div>
        <div class="centro" style="flex: 1; border-top: 1px solid #111827; padding-top: 4px">Recibió</div>
    </div>
@endsection
