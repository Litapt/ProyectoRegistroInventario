{{--
    Alertas · pestaña "Incidencias" (Figma Page 2: "Alertas · Incidencias" y "Alertas de incidencias").
    Abiertas (pendientes y en revisión) y, aparte, las resueltas.
    Ventanas: "Registrar incidencia" y "Detalle de la incidencia" (en revisión / resolver).
--}}
@extends('layouts.panel')

@section('titulo', 'Incidencias')
@section('menu', 'alertas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/alertas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
@endpush

@section('pagina')
    @php
        $estados = [
            'PENDIENTE' => ['Abierta', 'badge-warning'],
            'EN_REVISION' => ['En revisión', 'badge-primary'],
            'RESUELTA' => ['Resuelta', 'badge-ok'],
        ];
        $hayFiltros = $buscar !== '' || $prioridad !== '';
        $puedeRegistrar = auth()->user()->puede('incidencias.reportar');
    @endphp

    @include('administrador.alertas.encabezado', ['activa' => 'incidencias'])

    <section class="panel mb-4">
        <form method="get" action="{{ \App\Support\Ruta::a('alertas.incidencias') }}" class="panel-body d-flex flex-wrap align-items-end gap-3">
            @if ($verResueltas)
                <input type="hidden" name="ver" value="resueltas">
            @endif
            <div class="flex-grow-1" style="min-width: 220px">
                <label for="buscarIncidencia" class="field-label-app">Buscar producto o incidencia</label>
                <input type="search" id="buscarIncidencia" name="buscar" value="{{ $buscar }}" maxlength="60"
                       class="form-control input-app" placeholder="Nombre, código de producto o folio de incidencia">
            </div>
            <div style="min-width: 180px">
                <label for="filtroPrioridad" class="field-label-app">Prioridad</label>
                <select id="filtroPrioridad" name="prioridad" class="form-select input-app">
                    <option value="">Todas las prioridades</option>
                    @foreach (\App\Models\Solicitud::PRIORIDADES as $clave => $texto)
                        <option value="{{ $clave }}" @selected($prioridad === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-app btn-app-primary">Buscar</button>
            @if ($hayFiltros)
                <a href="{{ \App\Support\Ruta::a('alertas.incidencias', $verResueltas ? ['ver' => 'resueltas'] : []) }}" class="btn-text">Limpiar filtros</a>
            @endif
            @if ($puedeRegistrar)
                <button type="button" class="btn-app btn-app-soft ms-auto" data-bs-toggle="modal" data-bs-target="#modalRegistrarIncidencia">
                    Registrar incidencia
                </button>
            @endif
        </form>
    </section>

    <section class="panel">
        <div class="panel-body d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h2 class="panel-title m-0">{{ $verResueltas ? 'Incidencias resueltas' : 'Incidencias reportadas' }}</h2>
                <p class="panel-text m-0 mt-1">
                    {{ $verResueltas ? 'Las que ya se atendieron, con cómo se resolvieron.' : 'Da seguimiento a cada reporte. Alta prioridad primero.' }}
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if ($verResueltas)
                    <a href="{{ \App\Support\Ruta::a('alertas.incidencias') }}" class="btn-text">Ver abiertas ({{ $totalIncidencias }})</a>
                @else
                    <span class="badge-estado badge-warning">{{ $totalIncidencias - $enRevision }} Abiertas</span>
                    <span class="badge-estado badge-primary">{{ $enRevision }} En revisión</span>
                    <a href="{{ \App\Support\Ruta::a('alertas.incidencias', ['ver' => 'resueltas']) }}" class="btn-text ms-2">Ver resueltas ({{ $resueltas }})</a>
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Incidencia</th>
                        <th>Producto</th>
                        <th>Descripción</th>
                        <th>Prioridad</th>
                        <th>Reportado por</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incidencias as $incidencia)
                        <tr>
                            <td class="text-nowrap">
                                <div>{{ $incidencia->fechaSolicitud->translatedFormat('d M Y') }}</div>
                                <div class="cell-sub">{{ $incidencia->fechaSolicitud->format('h:i a') }}</div>
                            </td>
                            <td>
                                <div class="cell-main">{{ $incidencia->motivoDe?->nombre }}</div>
                                <div class="cell-sub">{{ $incidencia->folio }} · {{ $incidencia->cantidad }} {{ $incidencia->cantidad === 1 ? 'pieza' : 'piezas' }}</div>
                            </td>
                            <td>
                                <div>{{ $incidencia->producto?->nombre }}</div>
                                <div class="cell-sub">{{ $incidencia->producto?->codigoInterno }}{{ $incidencia->producto?->ubicacion ? ' · ' . $incidencia->producto->ubicacion->texto() : '' }}</div>
                            </td>
                            <td class="descripcion-corta">{{ \Illuminate\Support\Str::limit($incidencia->motivo, 90) }}</td>
                            <td><span class="prioridad prioridad-{{ $incidencia->prioridad }}">{{ \App\Models\Solicitud::PRIORIDADES[$incidencia->prioridad] ?? '—' }}</span></td>
                            <td>
                                <div>{{ $incidencia->solicitante?->nombreCompleto() }}</div>
                                <div class="cell-sub">{{ $incidencia->solicitante?->rolTexto() }}</div>
                            </td>
                            <td><span class="badge-estado {{ $estados[$incidencia->estado][1] ?? 'badge-muted' }}">{{ $estados[$incidencia->estado][0] ?? $incidencia->estado }}</span></td>
                            <td class="text-end">
                                <button type="button" class="btn-app btn-app-outline btn-app-sm text-nowrap"
                                        data-bs-toggle="modal" data-bs-target="#modalIncidencia"
                                        data-incidencia="{{ json_encode($incidencia->datosIncidencia()) }}"
                                        data-revision="{{ \App\Support\Ruta::a('alertas.incidencias.revision', $incidencia) }}"
                                        data-resolver="{{ \App\Support\Ruta::a('alertas.incidencias.resolver', $incidencia) }}">
                                    Ver detalle
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                @if ($hayFiltros)
                                    Ninguna incidencia coincide con estos filtros.
                                @else
                                    {{ $verResueltas ? 'Todavía no hay incidencias resueltas.' : 'No hay incidencias abiertas. 🎉' }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $incidencias, 'nombre' => 'incidencias'])
    </section>
@endsection

@push('modales')
    @include('administrador.alertas.modalIncidencia')
    @if ($puedeRegistrar)
        @include('administrador.alertas.modalRegistrarIncidencia')
    @endif
@endpush

@push('scripts')
    <script src="{{ asset('javascript/administrador/incidencias.js') }}"></script>
@endpush
