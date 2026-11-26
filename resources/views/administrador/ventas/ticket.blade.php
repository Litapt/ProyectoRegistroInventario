{{--
    Ticket de una venta (se abre en otra pestaña con "Imprimir ticket").
    Los precios y el IVA son los que se guardaron en la venta, no los de hoy.
    Los datos de la tienda salen de Configuración (tienda.nombreComercial, tienda.rfc...).
--}}
@extends('layouts.impresion')

@section('titulo', 'Ticket ' . $venta->folio)
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
        @if ($tienda['telefono'])
            <div class="tenue">Tel. {{ $tienda['telefono'] }}</div>
        @endif
        <p class="titulo-doc">TICKET DE VENTA</p>
        <div class="negrita">{{ $venta->folio }}</div>
        <div class="tenue">{{ $venta->fechaVenta->format('d/m/Y h:i a') }}</div>
        @if ($venta->estaCancelada())
            <div class="sello">CANCELADA</div>
            <div class="tenue">{{ $venta->fechaCancelacion?->format('d/m/Y h:i a') }}</div>
        @endif
    </div>

    <hr class="linea">
    <div>Cliente: {{ $venta->nombreCliente() }}</div>
    <div>Atendió: {{ $venta->trabajador?->nombreCompleto() }}</div>
    <hr class="linea">

    <table>
        <thead>
            <tr><th>Producto</th><th class="derecha">Cant.</th><th class="derecha">Importe</th></tr>
        </thead>
        <tbody>
            @foreach ($venta->detalles as $detalle)
                <tr>
                    <td>
                        {{ $detalle->producto?->nombre }}
                        <div class="tenue">{{ $detalle->producto?->codigoInterno }} · {{ $dinero($detalle->precioUnitarioVenta) }} c/u</div>
                    </td>
                    <td class="derecha">{{ $detalle->cantidad }}</td>
                    <td class="derecha">{{ $dinero($detalle->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales" style="margin-top: 8px">
        <tr><td>Subtotal</td><td class="derecha">{{ $dinero($venta->subtotal) }}</td></tr>
        @if ((float) $venta->descuentoTotal > 0)
            <tr><td>Descuento</td><td class="derecha">−{{ $dinero($venta->descuentoTotal) }}</td></tr>
        @endif
        <tr><td>IVA</td><td class="derecha">{{ $dinero($venta->impuestosTotal) }}</td></tr>
        <tr class="total"><td>TOTAL</td><td class="derecha">{{ $dinero($venta->total) }}</td></tr>
        @foreach ($venta->pagos as $pago)
            <tr>
                <td class="tenue">{{ $pago->observaciones === 'Abono' ? 'Abono ' . $pago->fechaPago?->format('d/m') : 'Pago' }} · {{ $pago->metodo?->nombre }}</td>
                <td class="derecha tenue">{{ $dinero($pago->monto) }}</td>
            </tr>
        @endforeach
        @if ($venta->esCredito())
            <tr class="total"><td>{{ $venta->tieneSaldo() ? 'SALDO PENDIENTE' : 'LIQUIDADA' }}</td><td class="derecha">{{ $venta->tieneSaldo() ? $dinero($venta->saldo()) : '' }}</td></tr>
        @endif
    </table>

    <hr class="linea">
    <div class="centro tenue">{{ $venta->piezas() }} {{ $venta->piezas() === 1 ? 'pieza' : 'piezas' }}</div>
    @if ($venta->estaCancelada())
        <p class="centro">Esta venta fue cancelada por el motivo:<br>«{{ $venta->textoCancelacion() }}»</p>
    @else
        <p class="centro">{{ $tienda['pie'] }}</p>
    @endif
@endsection
