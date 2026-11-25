{{--
    Alertas · pestaña "Stock" (Figma Page 2: "Alertas · Stock").
    Productos activos con existencia en o bajo su stock mínimo; agotados primero.
    "Ajustar stock" lleva a la pantalla de ajuste de ese producto.
--}}
@extends('layouts.panel')

@section('titulo', 'Alertas de stock')
@section('menu', 'alertas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/alertas.css') }}">
@endpush

@section('pagina')
    @include('administrador.alertas.encabezado', ['activa' => 'stock'])

    @if (!$alertasActivadas)
        <div class="notice notice-warning mb-4">
            <div class="notice-title">Las alertas de stock bajo están desactivadas</div>
            <div class="notice-text">La lista se sigue mostrando aquí. Puedes activarlas en Configuración → Notificaciones.</div>
        </div>
    @endif

    <section class="panel">
        <div class="panel-body d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h2 class="panel-title m-0">Productos que requieren atención</h2>
                <p class="panel-text m-0 mt-1">
                    {{ $totalStock }} {{ $totalStock === 1 ? 'producto' : 'productos' }} por reponer · Agotados primero.
                </p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge-estado badge-warning">{{ $totalStock - $agotados }} Bajo</span>
                <span class="badge-estado badge-danger">{{ $agotados }} Agotado</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        @php
                            $agotado = $producto->existencia === 0;
                            $vehiculo = $producto->compatibilidades->first();
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-main">{{ $producto->nombre }}{{ $producto->marca ? ' ' . $producto->marca->nombre : '' }}</div>
                                <div class="cell-sub">{{ $producto->codigoInterno }}{{ $vehiculo ? ' · ' . $vehiculo->vehiculo() : '' }}</div>
                            </td>
                            <td><span class="piezas-alerta {{ $agotado ? '' : 'es-bajo' }}">{{ $producto->existencia }} pzas</span></td>
                            <td class="text-muted">{{ $producto->stockMinimoEfectivo() }} pzas</td>
                            <td>
                                @if ($agotado)
                                    <span class="badge-estado badge-danger">Agotado</span>
                                @else
                                    <span class="badge-estado badge-warning">Bajo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (auth()->user()->puede('inventario.ajustar'))
                                    <a href="{{ \App\Support\Ruta::a('productos.ajuste', $producto) }}" class="btn-app btn-app-outline btn-app-sm">Ajustar stock</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Todo en orden: ningún producto está en su mínimo. 🎉</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $productos, 'nombre' => 'alertas de stock'])
    </section>
@endsection
