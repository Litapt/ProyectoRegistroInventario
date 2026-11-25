{{--
    Ventas del administrador · pestaña "Consultar ventas" (Figma Page 2: "Ventas · Consultar ventas").
    Todo lo manda Administrador\VentaController@consultar.
    Ventanas: detalle de la venta y "Cancelar venta" (con motivo y autorización).
    "Editar" (por solicitud) y "Registrar devolución" se programan en partes siguientes.
--}}
@extends('layouts.panel')

@section('titulo', 'Consultar ventas')
@section('menu', 'ventas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
@endpush

@section('pagina')
    @php
        $puedeCancelar = auth()->user()->puede('ventas.cancelar');
        $puedeEditar = auth()->user()->puede('ventas.solicitarEdicion');
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
    @endphp

    <div class="mb-3">
        <h1 class="page-heading m-0">Ventas</h1>
        <p class="page-description m-0 mt-1">Consulta y administra las ventas de tu refaccionaria.</p>
    </div>

    @include('administrador.ventas.pestanas', ['activa' => 'consultar'])

    @if ($devolucionRegistrada)
        <div class="notice notice-ok d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="notice-title">Devolución {{ $devolucionRegistrada->folio }} · venta {{ $devolucionRegistrada->venta?->folio }}</div>
                <div class="notice-text">
                    {{ $devolucionRegistrada->cantidad }} × {{ $devolucionRegistrada->producto?->nombre }} ·
                    Reembolso {{ $dinero($devolucionRegistrada->reembolso()) }}
                </div>
            </div>
            <a href="{{ \App\Support\Ruta::a('ventas.devolucion.comprobante', $devolucionRegistrada) }}" class="btn-app btn-app-outline" data-imprimir>
                Imprimir comprobante
            </a>
        </div>
    @endif

    <section class="panel mb-4">
        <form method="get" action="{{ \App\Support\Ruta::a('ventas.consultar') }}" class="panel-body d-flex flex-column gap-3" id="filtrosVentas">
            <div>
                <h2 class="panel-title m-0">Consultar</h2>
                <p class="panel-text m-0 mt-1">Puedes combinar los filtros para afinar tu consulta.</p>
            </div>

            @if ($verCanceladas)
                <input type="hidden" name="ver" value="canceladas">
            @endif

            <div class="row g-3">
                <div class="col-md-6 col-xl-2">
                    <label for="filtroFolio" class="field-label-app">Folio</label>
                    <input type="search" id="filtroFolio" name="folio" value="{{ $filtros['folio'] }}" maxlength="30"
                           class="form-control input-app mayusculas" placeholder="Ej. V-2026-…">
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="filtroCliente" class="field-label-app">Cliente</label>
                    <input type="search" id="filtroCliente" name="cliente" value="{{ $filtros['cliente'] }}" maxlength="60"
                           class="form-control input-app" placeholder="Nombre, teléfono o «público»">
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="filtroTrabajador" class="field-label-app">Trabajador</label>
                    <select id="filtroTrabajador" name="trabajador" class="form-select input-app">
                        <option value="">Todos los trabajadores</option>
                        @foreach ($trabajadores as $trabajador)
                            <option value="{{ $trabajador->idTrabajador }}" @selected($filtros['trabajador'] === (int) $trabajador->idTrabajador)>
                                {{ $trabajador->nombreCompleto() }}{{ $trabajador->activo ? '' : ' (inactivo)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="filtroDesde" class="field-label-app">Desde</label>
                    <input type="date" id="filtroDesde" name="desde" value="{{ $filtros['desde']?->format('Y-m-d') }}" class="form-control input-app">
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="filtroHasta" class="field-label-app">Hasta</label>
                    <input type="date" id="filtroHasta" name="hasta" value="{{ $filtros['hasta']?->format('Y-m-d') }}" class="form-control input-app">
                </div>
                <div class="col-12 d-flex flex-wrap align-items-end justify-content-end gap-3">
                    <button type="button" class="btn-text" id="filtroHoy">Sólo hoy</button>
                    @if ($hayFiltros)
                        <a href="{{ \App\Support\Ruta::a('ventas.consultar', $verCanceladas ? ['ver' => 'canceladas'] : []) }}" class="btn-app btn-app-soft">Limpiar filtros</a>
                    @endif
                    <button type="submit" class="btn-app btn-app-primary">Buscar ventas</button>
                </div>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-body d-flex flex-wrap align-items-center justify-content-between gap-2 pb-0">
            @php
                // Los filtros escritos se conservan al cambiar entre completadas y canceladas
                $otrosFiltros = array_filter(request()->only(['folio', 'cliente', 'trabajador', 'desde', 'hasta']), fn ($v) => $v !== null && $v !== '');
            @endphp
            <span class="panel-text">
                @if ($verCanceladas)
                    <strong class="text-dark">Ventas canceladas</strong> ·
                @endif
                {{ number_format($ventas->total()) }} {{ $ventas->total() === 1 ? 'venta' : 'ventas' }}{{ $hayFiltros ? ' con estos filtros' : '' }}
            </span>
            <span class="panel-text d-flex flex-wrap align-items-center gap-2">
                @if ($verCanceladas)
                    <a href="{{ \App\Support\Ruta::a('ventas.consultar', $otrosFiltros) }}" class="btn-text">Ver completadas</a>
                @else
                    Vendido: <strong class="text-dark">{{ $dinero($totalVendido) }}</strong> ·
                    <a href="{{ \App\Support\Ruta::a('ventas.consultar', $otrosFiltros + ['ver' => 'canceladas']) }}" class="btn-text">Ver canceladas ({{ $totalCanceladas }})</a>
                @endif
            </span>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Trabajador</th>
                        <th>Fecha</th>
                        <th>Pago</th>
                        <th class="text-end">Total</th>
                        <th>Estado</th>
                        <th class="text-end">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $venta)
                        <tr>
                            <td class="text-nowrap fw-semibold text-dark">{{ $venta->folio }}</td>
                            <td>
                                <div class="cell-main">{{ $venta->nombreCliente() }}</div>
                                @if ($venta->cliente)
                                    <div class="cell-sub">{{ $venta->cliente->codigo() }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $venta->trabajador?->nombreCompleto() }}</div>
                                <div class="cell-sub">{{ $venta->trabajador?->rolTexto() }}</div>
                            </td>
                            <td class="text-nowrap">
                                <div>{{ $venta->fechaVenta->format('d/m/Y') }}</div>
                                <div class="cell-sub">{{ $venta->fechaVenta->format('h:i a') }}</div>
                            </td>
                            <td class="text-muted">{{ $venta->metodosPagoTexto() }}</td>
                            <td class="text-end text-nowrap fw-semibold text-dark {{ $venta->estaCancelada() ? 'text-decoration-line-through' : '' }}">
                                {{ $dinero($venta->total) }}
                            </td>
                            <td>
                                @if ($venta->estaCancelada())
                                    <span class="badge-estado badge-danger">Cancelada</span>
                                @else
                                    <span class="badge-estado badge-ok">Completada</span>
                                    @if ($venta->solicitudPendiente)
                                        <div class="cell-sub mt-1" title="Solicitud {{ $venta->solicitudPendiente->folio }}">Edición pendiente</div>
                                    @endif
                                @endif
                                @if ($venta->devoluciones->isNotEmpty())
                                    <div class="cell-sub mt-1">Con devolución ({{ $dinero($venta->devoluciones->sum(fn ($d) => $d->reembolso())) }})</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                            data-bs-toggle="modal" data-bs-target="#modalDetalleVenta"
                                            data-venta="{{ json_encode($venta->datosParaDetalle()) }}"
                                            data-ticket="{{ \App\Support\Ruta::a('ventas.ticket', $venta) }}">
                                        Ver
                                    </button>
                                    <a href="{{ \App\Support\Ruta::a('ventas.ticket', $venta) }}" class="btn-app btn-app-outline btn-app-sm" data-imprimir>
                                        Ticket
                                    </a>
                                    @php
                                        $paraDevolver = $puedeCancelar && !$venta->estaCancelada() ? $venta->datosParaDevolucion() : null;
                                    @endphp
                                    @if ($paraDevolver && count($paraDevolver['renglones']) > 0)
                                        <button type="button" class="btn-app btn-app-soft btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalDevolucion"
                                                data-url="{{ \App\Support\Ruta::a('ventas.devolucion', $venta) }}"
                                                data-devolucion="{{ json_encode($paraDevolver) }}">
                                            Devolución
                                        </button>
                                    @endif
                                    @if ($puedeEditar && !$venta->estaCancelada() && !$venta->solicitudPendiente && $venta->devoluciones->isEmpty())
                                        <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarVenta"
                                                data-url="{{ \App\Support\Ruta::a('ventas.editar', $venta) }}"
                                                data-editar="{{ json_encode($venta->datosParaEditar()) }}">
                                            Editar
                                        </button>
                                    @endif
                                    @if ($puedeCancelar && !$venta->estaCancelada())
                                        <button type="button" class="btn-app btn-app-danger btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalCancelarVenta"
                                                data-url="{{ \App\Support\Ruta::a('ventas.cancelar', $venta) }}"
                                                data-folio="{{ $venta->folio }}"
                                                data-detalle="{{ $venta->nombreCliente() }} · {{ $dinero($venta->total) }} · {{ $venta->fechaVenta->format('d/m/Y h:i a') }}">
                                            Cancelar
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                @if ($hayFiltros)
                                    Ninguna venta {{ $verCanceladas ? 'cancelada ' : '' }}coincide con estos filtros.
                                @else
                                    {{ $verCanceladas ? 'No hay ventas canceladas.' : 'Todavía no hay ventas registradas.' }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $ventas, 'nombre' => 'ventas'])
    </section>
@endsection

@push('modales')
    @include('administrador.ventas.modalDetalleVenta')
    @if ($puedeEditar)
        @include('administrador.ventas.modalEditarVenta')
    @endif
    @if ($puedeCancelar)
        @include('administrador.ventas.modalCancelarVenta')
        @include('administrador.ventas.modalDevolucion')
    @endif
@endpush

@push('scripts')
    <script src="{{ asset('javascript/imprimir.js') }}"></script>
    <script src="{{ asset('javascript/administrador/consultarVentas.js') }}"></script>
    <script src="{{ asset('javascript/administrador/editarVenta.js') }}"></script>
@endpush
