{{--
    Cajero · "Cotizaciones": las que ESTE cajero guardó con "Convertir a cotización".
    Pendientes (vigentes o vencidas) y, aparte, las que ya se cobraron.
    "Cobrar" abre Nueva venta con sus productos, cliente y descuento ya puestos
    (si trae descuento, al cobrar pide autorización).
    Lo manda Cajero\VentaController@cotizaciones.
--}}
@extends('layouts.barraSuperior')

@section('titulo', 'Cotizaciones')

@section('navegacion')
    @include('cajero.menu', ['activo' => 'cotizaciones'])
@endsection

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
@endpush

@section('pagina')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
        $puedeCobrar = true;
    @endphp

    <div class="mb-3">
        <h1 class="page-heading m-0">Cotizaciones</h1>
        <p class="page-description m-0 mt-1">Tus cotizaciones: imprímelas otra vez o cóbralas cuando el cliente regrese.</p>
    </div>

    <section class="panel">
        <div class="panel-body d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="panel-title m-0">{{ $verCobradas ? 'Cotizaciones cobradas' : 'Cotizaciones pendientes' }}</h2>
                <p class="panel-text m-0 mt-1">
                    {{ $verCobradas ? 'Las que ya se convirtieron en venta.' : 'Una cotización no aparta mercancía ni toca el inventario.' }}
                </p>
            </div>
            <form method="get" action="{{ \App\Support\Ruta::a('ventas.cotizaciones') }}" class="d-flex flex-wrap align-items-center gap-3">
                @if ($verCobradas)
                    <input type="hidden" name="ver" value="cobradas">
                @endif
                <input type="search" name="buscar" value="{{ $buscar }}" maxlength="60" class="form-control input-app"
                       placeholder="Folio, cliente o teléfono" aria-label="Buscar cotización">
                <button type="submit" class="btn-app btn-app-primary">Buscar</button>
                @if ($buscar !== '')
                    <a href="{{ \App\Support\Ruta::a('ventas.cotizaciones', $verCobradas ? ['ver' => 'cobradas'] : []) }}" class="btn-text">Limpiar</a>
                @endif
                @if ($verCobradas)
                    <a href="{{ \App\Support\Ruta::a('ventas.cotizaciones') }}" class="btn-text">Ver pendientes ({{ $totalPendientes }})</a>
                @else
                    <a href="{{ \App\Support\Ruta::a('ventas.cotizaciones', ['ver' => 'cobradas']) }}" class="btn-text">Ver cobradas ({{ $totalCobradas }})</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>{{ $verCobradas ? 'Venta' : 'Válida hasta' }}</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cotizaciones as $cotizacion)
                        <tr>
                            <td class="text-nowrap fw-semibold text-dark">{{ $cotizacion->folio }}</td>
                            <td>
                                <div class="cell-main">{{ $cotizacion->cliente?->nombre() ?? 'Público en general' }}</div>
                                @if ($cotizacion->cliente)
                                    <div class="cell-sub">{{ $cotizacion->cliente->codigo() }} · {{ $cotizacion->cliente->telefono }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <div>{{ $cotizacion->fechaPedido->format('d/m/Y') }}</div>
                                <div class="cell-sub">{{ $cotizacion->fechaPedido->format('h:i a') }}</div>
                            </td>
                            <td class="text-nowrap">
                                @if ($verCobradas)
                                    {{ $cotizacion->venta?->folio ?? '—' }}
                                @elseif ($cotizacion->estaVigente())
                                    <span class="badge-estado badge-ok">{{ $cotizacion->vigencia->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge-estado badge-danger">Venció {{ $cotizacion->vigencia?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap fw-semibold text-dark">{{ $dinero($cotizacion->total) }}</td>
                            <td>
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <a href="{{ \App\Support\Ruta::a('ventas.cotizacion', $cotizacion) }}" class="btn-app btn-app-outline btn-app-sm" data-imprimir>Imprimir</a>
                                    @if (!$verCobradas && $puedeCobrar)
                                        <a href="{{ route('cajero.inicio', ['cotizacion' => $cotizacion->idPedido]) }}" class="btn-app btn-app-primary btn-app-sm">Cobrar</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                @if ($buscar !== '')
                                    Ninguna cotización coincide con «{{ $buscar }}».
                                @else
                                    {{ $verCobradas ? 'Todavía no se ha cobrado ninguna cotización.' : 'No hay cotizaciones pendientes. Se guardan con «Convertir a cotización» en Nueva venta.' }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $cotizaciones, 'nombre' => 'cotizaciones'])
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('javascript/imprimir.js') }}"></script>
@endpush
