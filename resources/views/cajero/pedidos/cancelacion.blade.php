{{--
    Comprobante de un pedido cancelado (Figma: "Cajero - Re Impresión Cancelación").
    Se imprime sin abrir otra pestaña (javascript/imprimir.js).
--}}
@extends('layouts.impresion')

@section('titulo', 'Cancelación ' . $pedido->folio)
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
        @if ($tienda['direccion'])
            <div class="tenue">{{ $tienda['direccion'] }}</div>
        @endif
        <p class="titulo-doc">CANCELACIÓN DE PEDIDO</p>
        <div class="negrita">{{ $pedido->folio }}</div>
        <div class="tenue">Pedido del {{ $pedido->fechaPedido->format('d/m/Y h:i a') }}</div>
        <div class="sello">CANCELADO</div>
        @if ($fechaCancelacion)
            <div class="tenue">{{ $fechaCancelacion->format('d/m/Y h:i a') }}</div>
        @endif
    </div>

    <hr class="linea">
    <div>Cliente: {{ $pedido->nombreCliente() }}</div>
    <div>Lo registró: {{ $pedido->trabajador?->nombreCompleto() }}</div>
    @if ($cancelo)
        <div>Lo canceló: {{ $cancelo }}</div>
    @endif
    <hr class="linea">

    <table>
        <thead>
            <tr><th>Producto</th><th class="derecha">Cant.</th><th class="derecha">Importe</th></tr>
        </thead>
        <tbody>
            @foreach ($pedido->detalles as $detalle)
                <tr>
                    <td>
                        {{ $detalle->producto?->nombre }}
                        <div class="tenue">{{ $detalle->producto?->codigoInterno }} · {{ $dinero($detalle->precioUnitario) }} c/u</div>
                    </td>
                    <td class="derecha">{{ $detalle->cantidad }}</td>
                    <td class="derecha">{{ $dinero($detalle->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales" style="margin-top: 8px">
        <tr><td>Subtotal</td><td class="derecha">{{ $dinero($pedido->subtotal) }}</td></tr>
        @if ((float) $pedido->descuentoTotal > 0)
            <tr><td>Descuento</td><td class="derecha">−{{ $dinero($pedido->descuentoTotal) }}</td></tr>
        @endif
        <tr><td>IVA</td><td class="derecha">{{ $dinero($pedido->impuestosTotal) }}</td></tr>
        <tr class="total"><td>TOTAL</td><td class="derecha">{{ $dinero($pedido->total) }}</td></tr>
        <tr><td class="tenue">Pagado</td><td class="derecha tenue">No aplica</td></tr>
    </table>

    <hr class="linea">
    <p class="centro">Este pedido fue cancelado por el motivo:<br>«{{ $pedido->observaciones }}»</p>
@endsection
