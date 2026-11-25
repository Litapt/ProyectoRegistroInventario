{{--
    Cotización para imprimir o mandar al cliente.
    - "Mostrar cotización": $folio = null → es una vista previa, NO se guardó.
    - "Convertir a cotización": ya se guardó en pedidos con su folio (COT-2026-0001).
    Una cotización no descuenta inventario.
--}}
@extends('layouts.impresion')

@section('titulo', $folio ? 'Cotización ' . $folio : 'Vista previa de cotización')

@section('documento')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
    @endphp

    @if (!$folio)
        <p class="no-imprimir centro tenue" style="margin-top: 0">
            Vista previa: esta cotización no se guardó. Usa «Convertir a cotización» para guardarla con folio.
        </p>
    @endif

    <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap">
        <div>
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
        </div>
        <div class="derecha">
            <p class="titulo-doc" style="margin-top: 0">COTIZACIÓN</p>
            <div class="negrita">{{ $folio ?? 'Sin folio (vista previa)' }}</div>
            <div class="tenue">Fecha: {{ $fecha->format('d/m/Y') }}</div>
            <div class="tenue">Válida hasta: {{ $vigencia->format('d/m/Y') }}</div>
        </div>
    </div>

    <hr class="linea">
    <div>Cliente: <span class="negrita">{{ $cliente ?? 'Público en general' }}</span></div>
    <div class="tenue">Atendió: {{ $atendio }}</div>
    <hr class="linea">

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="derecha">Cantidad</th>
                <th class="derecha">Precio unitario</th>
                <th class="derecha">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cuentas['renglones'] as $renglon)
                <tr>
                    <td class="tenue">{{ $renglon['producto']?->codigoInterno }}</td>
                    <td>{{ $renglon['producto']?->nombre }}</td>
                    <td class="derecha">{{ $renglon['cantidad'] }}</td>
                    <td class="derecha">{{ $dinero($renglon['precio']) }}</td>
                    <td class="derecha">{{ $dinero($renglon['subtotal']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales" style="width: 280px; margin: 12px 0 0 auto">
        <tr><td>Subtotal</td><td class="derecha">{{ $dinero($cuentas['subtotal']) }}</td></tr>
        @if ($cuentas['descuento'] > 0)
            <tr><td>Descuento</td><td class="derecha">−{{ $dinero($cuentas['descuento']) }}</td></tr>
        @endif
        <tr><td>IVA</td><td class="derecha">{{ $dinero($cuentas['iva']) }}</td></tr>
        <tr class="total"><td>TOTAL</td><td class="derecha">{{ $dinero($cuentas['total']) }}</td></tr>
    </table>

    <hr class="linea">
    <p class="tenue">
        Precios en pesos mexicanos (MXN). Sujetos a existencias al momento de la compra.
        Esta cotización no aparta mercancía.
    </p>

    @if ($folio)
        <p class="no-imprimir centro"><a href="{{ route('administrador.ventas.index') }}">Regresar a Ventas</a></p>
    @endif
@endsection
