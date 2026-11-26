{{--
    Cajero · Pedidos (Figma: "Cajero - Principal", "Completados" y "Cancelados").
    Lo manda Cajero\PedidoController@index. Los pedidos los arman los vendedores.

    - Pendientes de cobrar (los ve cualquier cajero; el que llegó primero va arriba).
    - Cobrados y cancelados: sólo los que cobró o canceló ESTE cajero.
    - "Ver" abre el detalle; "Cobrar" lleva a la pantalla de cobro;
      "Cancelar" pide el motivo y la autorización de un gerente o administrador.
--}}
@extends('layouts.barraSuperior')

@section('titulo', 'Pedidos')

@section('navegacion')
    @include('cajero.menu', ['activo' => 'pedidos'])
@endsection

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cajero/cajero.css') }}">
@endpush

@section('pagina')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
        $otrosFiltros = array_filter(request()->only(['folio', 'cliente', 'trabajador']), fn ($v) => $v !== null && $v !== '');
        $vistas = [
            'pendientes' => 'Pendientes de cobrar (' . $totales['pendientes'] . ')',
            'cobrados' => 'Cobrados (' . $totales['cobrados'] . ')',
            'cancelados' => 'Cancelados (' . $totales['cancelados'] . ')',
        ];
        $titulos = ['pendientes' => 'Pedidos pendientes de cobrar', 'cobrados' => 'Pedidos cobrados', 'cancelados' => 'Pedidos cancelados'];
    @endphp

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-heading m-0">{{ $titulos[$vista] }}</h1>
            <p class="page-description m-0 mt-1">Pedidos que arman los vendedores para cobrarlos en caja · {{ $turno->nombre() }}</p>
        </div>
        <a href="{{ route('cajero.inicio') }}" class="btn-app btn-app-outline">Nueva venta</a>
    </div>

    @if ($ventaCobrada)
        <div class="notice notice-ok d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="notice-title">Venta {{ $ventaCobrada->folio }} · {{ $dinero($ventaCobrada->total) }}</div>
                <div class="notice-text">
                    {{ $ventaCobrada->nombreCliente() }}
                    @if ($ventaCobrada->tieneSaldo())
                        · Pagó {{ $dinero($ventaCobrada->pagado()) }}, debe <strong>{{ $dinero($ventaCobrada->saldo()) }}</strong>
                    @endif
                    · El ticket se manda a imprimir solo.
                </div>
            </div>
            {{-- data-imprimir-auto: se imprime al llegar, sin abrir otra pestaña (javascript/imprimir.js) --}}
            <a href="{{ route('cajero.ventas.ticket', $ventaCobrada) }}" class="btn-app btn-app-outline" data-imprimir data-imprimir-auto>
                Imprimir ticket
            </a>
        </div>
    @endif

    @if ($pedidoCancelado)
        <div class="notice notice-ok d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="notice-title">Pedido {{ $pedidoCancelado->folio }} cancelado</div>
                <div class="notice-text">Motivo: {{ $pedidoCancelado->observaciones }}</div>
            </div>
            <a href="{{ route('cajero.pedidos.cancelacion', $pedidoCancelado) }}" class="btn-app btn-app-outline" data-imprimir>Imprimir comprobante</a>
        </div>
    @endif

    {{-- ---------- Filtros ---------- --}}
    <section class="panel mb-4">
        <form method="get" action="{{ route('cajero.pedidos.index') }}" class="panel-body d-flex flex-column gap-3">
            @if ($vista !== 'pendientes')
                <input type="hidden" name="ver" value="{{ $vista }}">
            @endif
            <p class="panel-text m-0">Busca por uno o varios datos del pedido.</p>
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-3">
                    <label for="filtroFolio" class="field-label-app">Folio</label>
                    <input type="search" id="filtroFolio" name="folio" value="{{ $filtros['folio'] }}" maxlength="30"
                           class="form-control input-app mayusculas" placeholder="Escribe el folio del pedido">
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
                                {{ $trabajador->nombreCompleto() }} · {{ $trabajador->rolTexto() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-3 d-flex flex-wrap gap-2 justify-content-xl-end">
                    @if ($hayFiltros)
                        <a href="{{ route('cajero.pedidos.index', $vista !== 'pendientes' ? ['ver' => $vista] : []) }}" class="btn-app btn-app-soft">Limpiar filtros</a>
                    @endif
                    <button type="submit" class="btn-app btn-app-primary">Buscar pedido</button>
                </div>
            </div>
        </form>
    </section>

    {{-- ---------- Lista ---------- --}}
    <section class="panel">
        <div class="panel-body pb-0 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <nav class="vista-ventas" aria-label="Qué pedidos ver">
                @foreach ($vistas as $clave => $texto)
                    <a href="{{ route('cajero.pedidos.index', $otrosFiltros + ($clave === 'pendientes' ? [] : ['ver' => $clave])) }}"
                       class="vista-ventas-opcion {{ $vista === $clave ? 'is-active' : '' }}" @if ($vista === $clave) aria-current="page" @endif>{{ $texto }}</a>
                @endforeach
            </nav>
            <a href="{{ request()->fullUrl() }}" class="btn-text">Recargar</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Trabajador</th>
                        <th>{{ $vista === 'pendientes' ? 'Llegó' : 'Fecha' }}</th>
                        <th class="text-end">Piezas</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr>
                            <td class="text-nowrap fw-semibold text-dark">
                                {{ $pedido->folio }}
                                @if ($pedido->venta)
                                    <div class="cell-sub">Venta {{ $pedido->venta->folio }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="cell-main">{{ $pedido->nombreCliente() }}</div>
                                @if ($pedido->cliente)
                                    <div class="cell-sub">{{ $pedido->cliente->codigo() }} · {{ $pedido->cliente->telefono }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $pedido->trabajador?->nombreCompleto() }}</div>
                                <div class="cell-sub">{{ $pedido->trabajador?->rolTexto() }}</div>
                            </td>
                            <td class="text-nowrap">
                                <div>{{ $pedido->fechaPedido->format('d/m/Y') }}</div>
                                <div class="cell-sub">{{ $pedido->fechaPedido->format('h:i a') }}</div>
                            </td>
                            <td class="text-end">{{ $pedido->piezas() }}</td>
                            <td class="text-end text-nowrap fw-semibold text-dark">
                                {{ $dinero($pedido->venta?->total ?? $pedido->total) }}
                                @if ($pedido->estadoPedido === \App\Models\Pedido::CANCELADO)
                                    <div class="cell-sub fw-normal text-wrap">{{ \Illuminate\Support\Str::limit($pedido->observaciones, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                            data-bs-toggle="modal" data-bs-target="#modalDetallePedido"
                                            data-pedido="{{ json_encode($pedido->datosParaDetalle()) }}"
                                            @if ($vista === 'pendientes')
                                                data-cobrar="{{ route('cajero.pedidos.cobrar', $pedido) }}"
                                                data-editar="{{ route('cajero.pedidos.edit', $pedido) }}"
                                            @endif>
                                        Ver
                                    </button>
                                    @if ($vista === 'pendientes')
                                        <a href="{{ route('cajero.pedidos.cobrar', $pedido) }}" class="btn-app btn-app-primary btn-app-sm">Cobrar</a>
                                        <button type="button" class="btn-app btn-app-danger btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalCancelarPedido"
                                                data-url="{{ route('cajero.pedidos.cancelar', $pedido) }}"
                                                data-folio="{{ $pedido->folio }}"
                                                data-detalle="{{ $pedido->nombreCliente() }} · {{ $dinero($pedido->total) }} · {{ $pedido->piezas() }} piezas">
                                            Cancelar
                                        </button>
                                    @elseif ($vista === 'cobrados' && $pedido->venta)
                                        <a href="{{ route('cajero.ventas.ticket', $pedido->venta) }}" class="btn-app btn-app-outline btn-app-sm" data-imprimir>Ticket</a>
                                    @elseif ($vista === 'cancelados')
                                        <a href="{{ route('cajero.pedidos.cancelacion', $pedido) }}" class="btn-app btn-app-outline btn-app-sm" data-imprimir>Comprobante</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                @if ($hayFiltros)
                                    Ningún pedido coincide con estos filtros.
                                @elseif ($vista === 'pendientes')
                                    No hay pedidos por cobrar. Cuando un vendedor mande uno, aparece aquí.
                                @else
                                    {{ $vista === 'cobrados' ? 'Todavía no hay pedidos cobrados.' : 'No hay pedidos cancelados.' }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $pedidos, 'nombre' => 'pedidos'])
    </section>
@endsection

@push('modales')
    @include('cajero.pedidos.modalDetalle')
    @include('cajero.pedidos.modalCancelar')
@endpush

@push('scripts')
    <script src="{{ asset('javascript/imprimir.js') }}"></script>
    <script src="{{ asset('javascript/cajero/inicio.js') }}"></script>
@endpush
