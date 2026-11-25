{{--
    Clientes del administrador (Figma Page 2: "Administrador · Clientes").
    Todo lo manda Administrador\ClienteController@index.
    Ventanas: modalCliente.blade.php (registrar / editar) y partials/modalAutorizacion.
--}}
@extends('layouts.panel')

@section('titulo', 'Clientes')
@section('menu', 'clientes')

@section('pagina')
    @php
        $puedeGestionar = auth()->user()->puede('clientes.gestionar');
        $puedeDesactivar = auth()->user()->puede('clientes.desactivar');
    @endphp

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-heading m-0">Clientes</h1>
            <p class="page-description m-0 mt-1">Consulta y administra los datos de tus clientes.</p>
        </div>
        @if ($puedeGestionar && !$verDesactivados)
            <button type="button" class="btn-app btn-app-primary" data-bs-toggle="modal" data-bs-target="#modalCliente" data-modo="registrar">
                Registrar cliente
            </button>
        @endif
    </div>

    <section class="panel">
        <form method="get" action="{{ \App\Support\Ruta::a('clientes.index') }}" class="panel-body d-flex flex-column gap-3">
            @if ($verDesactivados)
                <input type="hidden" name="ver" value="desactivados">
            @endif
            <div>
                <h2 class="panel-title m-0">{{ $verDesactivados ? 'Clientes desactivados' : 'Consultar' }}</h2>
                <p class="panel-text m-0 mt-1">
                    {{ $verDesactivados ? 'No aparecen al registrar ventas. Puedes reactivarlos.' : 'Encuentra a tus clientes por nombre, teléfono, correo, RFC o código.' }}
                </p>
            </div>
            <div>
                <label for="buscar" class="field-label-app">Buscar cliente</label>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <input type="search" id="buscar" name="buscar" value="{{ $buscar }}" maxlength="100"
                           class="form-control input-app filtro-buscar" placeholder="Nombre, teléfono, correo o RFC">
                    <button type="submit" class="btn-app btn-app-primary">Buscar</button>
                    @if ($buscar !== '')
                        <a href="{{ \App\Support\Ruta::a('clientes.index', $verDesactivados ? ['ver' => 'desactivados'] : []) }}" class="btn-text">Limpiar búsqueda</a>
                    @endif
                    <span class="panel-text ms-auto">
                        {{ number_format($totalActivos) }} {{ $totalActivos === 1 ? 'cliente registrado' : 'clientes registrados' }} ·
                        @if ($verDesactivados)
                            <a href="{{ \App\Support\Ruta::a('clientes.index') }}" class="btn-text">Ver activos</a>
                        @else
                            <a href="{{ \App\Support\Ruta::a('clientes.index', ['ver' => 'desactivados']) }}" class="btn-text">Ver desactivados ({{ $totalDesactivados }})</a>
                        @endif
                    </span>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>RFC</th>
                        <th>Fecha de registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        @php
                            $detalle = $cliente->codigo() . ($cliente->correo ? ' · ' . $cliente->correo : '');
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-main">{{ $cliente->nombre() }}</div>
                                <div class="cell-sub">{{ $cliente->codigo() }}</div>
                            </td>
                            <td class="text-muted text-nowrap">{{ $cliente->telefono }}</td>
                            <td class="text-muted">{{ $cliente->correo ?: 'Sin correo' }}</td>
                            <td class="text-muted small">{{ $cliente->RFC ?: 'Sin RFC' }}</td>
                            <td class="text-muted text-nowrap">{{ $cliente->fechaRegistro?->translatedFormat('d M Y') }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    @if ($verDesactivados)
                                        @if ($puedeDesactivar)
                                            <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalAutorizacion"
                                                    data-url="{{ \App\Support\Ruta::a('clientes.reactivar', $cliente) }}"
                                                    data-titulo="Reactivar cliente" data-texto="Vas a reactivar los datos del cliente:"
                                                    data-nombre="{{ $cliente->nombre() }}" data-detalle="{{ $detalle }}"
                                                    data-boton="Confirmar reactivación" data-clase="btn-app-primary">
                                                Reactivar
                                            </button>
                                        @endif
                                    @else
                                        @if ($puedeGestionar)
                                            <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalCliente" data-modo="editar"
                                                    data-url="{{ \App\Support\Ruta::a('clientes.update', $cliente) }}"
                                                    data-cliente="{{ json_encode($cliente->datosParaEditar()) }}">
                                                Editar
                                            </button>
                                        @endif
                                        @if ($puedeDesactivar)
                                            <button type="button" class="btn-app btn-app-danger btn-app-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalAutorizacion"
                                                    data-url="{{ \App\Support\Ruta::a('clientes.desactivar', $cliente) }}"
                                                    data-titulo="Confirmar eliminación"
                                                    data-texto="Vas a eliminar los datos del cliente. Se desactiva: ya no aparecerá al registrar ventas, pero sus ventas anteriores se conservan y un gerente o administrador puede reactivarlo."
                                                    data-nombre="{{ $cliente->nombre() }}" data-detalle="{{ $detalle }}"
                                                    data-boton="Confirmar eliminación" data-clase="btn-app-danger">
                                                Eliminar
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                @if ($buscar !== '')
                                    Ningún cliente coincide con «{{ $buscar }}».
                                @elseif ($verDesactivados)
                                    No hay clientes desactivados.
                                @else
                                    Todavía no hay clientes. Usa «Registrar cliente» para agregar el primero.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $clientes, 'nombre' => 'clientes'])
    </section>
@endsection

@push('modales')
    @include('administrador.clientes.modalCliente')
    @include('partials.modalAutorizacion')
@endpush

@push('scripts')
    <script src="{{ asset('javascript/administrador/clientes.js') }}"></script>
@endpush
