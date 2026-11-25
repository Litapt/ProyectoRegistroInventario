{{--
    Comprobante de devolución (se imprime directo con "Imprimir comprobante").
--}}
@extends('layouts.impresion')

@section('titulo', 'Devolución ' . $devolucion->folio)
@section('ancho', '80mm')

@section('documento')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
        $datos = $devolucion->datosPropuestos ?? [];
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
        <p class="titulo-doc">COMPROBANTE DE DEVOLUCIÓN</p>
        <div class="negrita">{{ $devolucion->folio }}</div>
        <div class="tenue">{{ $devolucion->fechaSolicitud->format('d/m/Y h:i a') }}</div>
    </div>

    <hr class="linea">
    <div>Venta: {{ $devolucion->venta?->folio }} ({{ $devolucion->venta?->fechaVenta?->format('d/m/Y') }})</div>
    <div>Cliente: {{ $devolucion->venta?->nombreCliente() }}</div>
    <div>Atendió: {{ $devolucion->solicitante?->nombreCompleto() }}</div>
    <div>Autorizó: {{ $devolucion->responsable?->nombreCompleto() }}</div>
    <hr class="linea">

    <table>
        <thead>
            <tr><th>Producto</th><th class="derecha">Cant.</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ $devolucion->producto?->nombre }}
                    <div class="tenue">{{ $devolucion->producto?->codigoInterno }} · {{ $dinero($datos['precioUnitario'] ?? 0) }} c/u (sin IVA)</div>
                </td>
                <td class="derecha">{{ $devolucion->cantidad }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totales" style="margin-top: 8px">
        <tr class="total"><td>REEMBOLSO</td><td class="derecha">{{ $dinero($devolucion->reembolso()) }}</td></tr>
    </table>

    <hr class="linea">
    <div>Motivo: {{ $devolucion->motivoDe?->nombre }}</div>
    @if ($devolucion->comentario)
        <div class="tenue">{{ $devolucion->comentario }}</div>
    @endif
    <div class="tenue">{{ ($datos['regresaInventario'] ?? false) ? 'Las piezas regresaron al inventario.' : 'Las piezas no regresaron al inventario (dañadas).' }}</div>

    <p class="centro" style="margin-top: 32px">______________________<br>Firma del cliente</p>
@endsection
