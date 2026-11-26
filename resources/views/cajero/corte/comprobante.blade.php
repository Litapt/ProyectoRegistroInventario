{{--
    Comprobante del corte de caja: conteo, esperado, diferencia y todas las ventas del turno.
    Se imprime solo al guardar el corte (y se puede reimprimir). Sin abrir otra pestaña.
--}}
@extends('layouts.impresion')

@section('titulo', 'Corte ' . $turno->nombre())
@section('ancho', '80mm')

@section('documento')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
        $cierre = $turno->conteoCierre ?? [];
        $otros = collect($cierre['otros'] ?? []);
        $totalContado = (float) $turno->efectivoContado + $otros->sum('contado');
        $totalEsperado = (float) $turno->efectivoEsperado + $otros->sum('esperado');
        $diferencia = (float) $turno->diferencia;
    @endphp

    <div class="centro">
        <p class="tienda">{{ $tienda['nombre'] }}</p>
        @if ($tienda['rfc'])
            <div class="tenue">RFC {{ $tienda['rfc'] }}</div>
        @endif
        <p class="titulo-doc">CORTE DE CAJA</p>
        <div class="negrita">{{ $turno->nombre() }}</div>
    </div>

    <hr class="linea">
    <div>Cajero: {{ $turno->trabajador?->nombreCompleto() }}</div>
    <div>Abrió: {{ $turno->fechaApertura->format('d/m/Y h:i a') }} con {{ $dinero($turno->fondoInicial) }}</div>
    <div>Cerró: {{ $turno->fechaCierre?->format('d/m/Y h:i a') }}</div>
    @if ($turno->autorizoCierre)
        <div>Autorizó: {{ $turno->autorizoCierre->nombreCompleto() }}</div>
    @endif
    <hr class="linea">

    {{-- Conteo de efectivo --}}
    <p class="negrita" style="margin: 0 0 4px">Conteo de efectivo</p>
    <table class="totales">
        @foreach (($cierre['billetes'] ?? []) as $billete => $piezas)
            @if ($piezas > 0)
                <tr><td>{{ $piezas }} × ${{ $billete }}</td><td class="derecha">{{ $dinero($piezas * $billete) }}</td></tr>
            @endif
        @endforeach
        @if (($cierre['monedas'] ?? 0) > 0)
            <tr><td>Monedas</td><td class="derecha">{{ $dinero($cierre['monedas']) }}</td></tr>
        @endif
        <tr class="negrita"><td>Efectivo contado</td><td class="derecha">{{ $dinero($turno->efectivoContado) }}</td></tr>
    </table>

    <hr class="linea">

    {{-- Contado contra esperado (dos columnas para que quepa en 80 mm) --}}
    <table>
        <thead>
            <tr><th>Método</th><th class="derecha">Contado / esperado</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Efectivo <div class="tenue">Fondo {{ $dinero($turno->fondoInicial) }} + cobrado {{ $dinero($cierre['cobradoEfectivo'] ?? 0) }}@if (($cierre['reembolsos'] ?? 0) > 0) − devol. {{ $dinero($cierre['reembolsos']) }}@endif</div></td>
                <td class="derecha">{{ $dinero($turno->efectivoContado) }}<div class="tenue">{{ $dinero($turno->efectivoEsperado) }}</div></td>
            </tr>
            @foreach ($otros as $metodo)
                <tr>
                    <td>{{ $metodo['nombre'] }}</td>
                    <td class="derecha">{{ $dinero($metodo['contado']) }}<div class="tenue">{{ $dinero($metodo['esperado']) }}</div></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales" style="margin-top: 8px">
        <tr><td>Corte total</td><td class="derecha">{{ $dinero($totalContado) }}</td></tr>
        <tr><td>Corte esperado</td><td class="derecha">{{ $dinero($totalEsperado) }}</td></tr>
        <tr class="total">
            <td>{{ abs($diferencia) < 0.005 ? 'CUADRA' : ($diferencia < 0 ? 'FALTAN' : 'SOBRAN') }}</td>
            <td class="derecha">{{ $dinero(abs($diferencia)) }}</td>
        </tr>
    </table>
    @if ($turno->observacionesCierre)
        <p class="tenue" style="margin: 4px 0 0">Observaciones: {{ $turno->observacionesCierre }}</p>
    @endif

    <hr class="linea">

    {{-- Las ventas del turno --}}
    @include('compartido.listasVentas')

    <table class="totales" style="margin-top: 8px">
        <tr><td>Total vendido</td><td class="derecha">{{ $dinero($totalVendido) }}</td></tr>
        @if ($pendiente > 0)
            <tr><td class="tenue">Quedó pendiente (crédito)</td><td class="derecha tenue">{{ $dinero($pendiente) }}</td></tr>
        @endif
    </table>

    <div style="margin-top: 36px; display: flex; gap: 16px">
        <div class="centro" style="flex: 1; border-top: 1px solid #111827; padding-top: 4px">Entregó</div>
        <div class="centro" style="flex: 1; border-top: 1px solid #111827; padding-top: 4px">Recibió</div>
    </div>
@endsection
