{{--
    Ventas · pestaña "Solicitudes" (Figma: "Solicitudes pendientes").
    Ediciones de venta que se mandaron sin autorización en el momento.
    Aceptar = se aplica el cambio (inventario, renglones, total). Rechazar = la venta no cambia.
    Todo lo manda Administrador\SolicitudVentaController@index.
--}}
@extends('layouts.panel')

@section('titulo', 'Solicitudes de ventas')
@section('menu', 'ventas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
@endpush

@section('pagina')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
        $nombreCliente = fn ($id) => $id ? ($clientes[$id]?->nombre() ?? 'Cliente #' . $id) : 'Público en general';
    @endphp

    <div class="mb-3">
        <h1 class="page-heading m-0">Ventas</h1>
        <p class="page-description m-0 mt-1">Cuando alguien edita una venta sin la autorización de un gerente o administrador, el cambio no se aplica todavía: queda aquí hasta que lo acepten o lo rechacen.</p>
    </div>

    @include('administrador.ventas.pestanas', ['activa' => 'solicitudes'])

    <section class="panel">
        <div class="panel-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h2 class="panel-title m-0">{{ $verResueltas ? 'Solicitudes resueltas' : 'Solicitudes pendientes' }}</h2>
                <p class="panel-text m-0 mt-1">
                    {{ $verResueltas ? 'Las que ya se aceptaron o rechazaron.' : 'Revisa los cambios antes de aceptar: al aceptar, la venta y el inventario cambian.' }}
                </p>
            </div>
            @if ($verResueltas)
                <a href="{{ \App\Support\Ruta::a('ventas.solicitudes') }}" class="btn-text">Ver pendientes ({{ $totalPendientes }})</a>
            @else
                <a href="{{ \App\Support\Ruta::a('ventas.solicitudes', ['ver' => 'resueltas']) }}" class="btn-text">Ver resueltas</a>
            @endif
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Venta</th>
                        <th>Solicitado por</th>
                        <th>Fecha</th>
                        <th>Motivo</th>
                        <th class="text-end">Total</th>
                        <th>Estado</th>
                        <th class="text-end">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitudes as $solicitud)
                        @php
                            $p = $solicitud->datosPropuestos ?? [];
                            $antes = collect($p['antes']['productos'] ?? [])->pluck('cantidad', 'id');
                            $despues = collect($p['productos'] ?? [])->pluck('cantidad', 'id');
                            $ids = $antes->keys()->merge($despues->keys())->unique();
                            $cambioCliente = (int) ($p['cliente'] ?? 0) !== (int) ($p['antes']['cliente'] ?? 0);
                        @endphp
                        <tr>
                            <td class="text-nowrap fw-semibold text-dark">{{ $solicitud->folio }}</td>
                            <td class="text-nowrap">
                                <div>{{ $solicitud->venta?->folio }}</div>
                                <div class="cell-sub">{{ $solicitud->venta?->nombreCliente() }}</div>
                            </td>
                            <td>
                                <div>{{ $solicitud->solicitante?->nombreCompleto() }}</div>
                                <div class="cell-sub">{{ $solicitud->solicitante?->rolTexto() }}</div>
                            </td>
                            <td class="text-nowrap">
                                <div>{{ $solicitud->fechaSolicitud->format('d/m/Y') }}</div>
                                <div class="cell-sub">{{ $solicitud->fechaSolicitud->format('h:i a') }}</div>
                            </td>
                            <td class="text-muted" style="max-width: 240px">{{ $solicitud->motivo }}</td>
                            <td class="text-end text-nowrap">
                                <div class="cell-sub">{{ $dinero($p['antes']['total'] ?? 0) }} →</div>
                                <div class="fw-semibold text-dark">{{ $dinero($p['total'] ?? 0) }}</div>
                            </td>
                            <td>
                                @if ($solicitud->estado === \App\Models\Solicitud::PENDIENTE)
                                    <span class="badge-estado badge-warning">Pendiente</span>
                                @elseif ($solicitud->estado === \App\Models\Solicitud::APROBADA)
                                    <span class="badge-estado badge-ok">Aceptada</span>
                                @else
                                    <span class="badge-estado badge-danger">Rechazada</span>
                                @endif
                                @if ($solicitud->responsable)
                                    <div class="cell-sub mt-1">{{ $solicitud->responsable->nombreCompleto() }} · {{ $solicitud->fechaResolucion?->format('d/m/Y h:i a') }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <button type="button" class="btn-app btn-app-outline btn-app-sm" data-bs-toggle="collapse"
                                            data-bs-target="#cambios{{ $solicitud->idSolicitud }}" aria-expanded="false">
                                        Ver cambios
                                    </button>
                                    @if ($solicitud->estaPendiente())
                                        <button type="button" class="btn-app btn-app-primary btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalConfirmar"
                                                data-confirmar-url="{{ \App\Support\Ruta::a('ventas.solicitudes.aprobar', $solicitud) }}"
                                                data-confirmar-metodo="PATCH"
                                                data-confirmar-titulo="¿Aceptar {{ $solicitud->folio }}?"
                                                data-confirmar-texto="La venta {{ $solicitud->venta?->folio }} y el inventario cambiarán como dice «Ver cambios»."
                                                data-confirmar-boton="Aceptar solicitud">
                                            Aceptar
                                        </button>
                                        <button type="button" class="btn-app btn-app-danger btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalRechazar"
                                                data-url="{{ \App\Support\Ruta::a('ventas.solicitudes.rechazar', $solicitud) }}"
                                                data-folio="{{ $solicitud->folio }}">
                                            Rechazar
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr class="collapse" id="cambios{{ $solicitud->idSolicitud }}">
                            <td colspan="8" class="bg-light">
                                <div class="d-flex flex-column gap-2 py-2">
                                    <div>
                                        <span class="field-help">Cliente:</span>
                                        @if ($cambioCliente)
                                            <span class="cambio-menos">{{ $nombreCliente($p['antes']['cliente'] ?? null) }}</span> →
                                            <span class="cambio-mas">{{ $nombreCliente($p['cliente'] ?? null) }}</span>
                                        @else
                                            {{ $nombreCliente($p['cliente'] ?? null) }} (sin cambio)
                                        @endif
                                    </div>
                                    @if (isset($p['descuento']))
                                        <div>
                                            <span class="field-help">Descuento:</span>
                                            @if (abs((float) $p['descuento'] - (float) ($p['antes']['descuento'] ?? 0)) >= 0.005)
                                                <span class="cambio-menos">{{ (float) ($p['antes']['descuento'] ?? 0) }} %</span> →
                                                <span class="cambio-mas">{{ (float) $p['descuento'] }} %</span>
                                            @else
                                                {{ (float) $p['descuento'] }} % (sin cambio)
                                            @endif
                                        </div>
                                    @endif
                                    <table class="table table-sm mb-0" style="max-width: 640px">
                                        <thead>
                                            <tr><th>Producto</th><th class="text-end">Antes</th><th class="text-end">Después</th><th></th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($ids as $id)
                                                @php
                                                    $a = (int) ($antes[$id] ?? 0);
                                                    $d = (int) ($despues[$id] ?? 0);
                                                @endphp
                                                <tr>
                                                    <td>{{ $productos[$id]?->codigoInterno }} · {{ $productos[$id]?->nombre ?? 'Producto #' . $id }}</td>
                                                    <td class="text-end">{{ $a }}</td>
                                                    <td class="text-end">{{ $d }}</td>
                                                    <td class="text-nowrap">
                                                        @if ($a === 0)
                                                            <span class="cambio-mas">Se agrega</span>
                                                        @elseif ($d === 0)
                                                            <span class="cambio-menos">Se quita</span>
                                                        @elseif ($d > $a)
                                                            <span class="cambio-mas">+{{ $d - $a }}</span>
                                                        @elseif ($d < $a)
                                                            <span class="cambio-menos">−{{ $a - $d }}</span>
                                                        @else
                                                            <span class="text-muted">Igual</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if ($solicitud->comentario)
                                        <div><span class="field-help">Comentario al rechazar:</span> {{ $solicitud->comentario }}</div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                {{ $verResueltas ? 'Todavía no hay solicitudes resueltas.' : 'No hay solicitudes pendientes. 🎉' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $solicitudes, 'nombre' => 'solicitudes'])
    </section>
@endsection

@push('modales')
    @include('partials.modalConfirmar')
    <div class="modal fade modal-app" id="modalRechazar" tabindex="-1" aria-labelledby="rechazarTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="" class="modal-content" id="formRechazar">
                @csrf
                @method('PATCH')
                <div class="modal-body d-flex flex-column gap-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <h2 class="modal-app-title m-0" id="rechazarTitulo">Rechazar <span id="rechazarFolio"></span></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <p class="panel-text fs-6 m-0">La venta se queda como está. Puedes dejar un comentario para quien la pidió.</p>
                    <div>
                        <label for="rechazarComentario" class="field-label-app">Comentario <span class="field-help">(opcional)</span></label>
                        <textarea id="rechazarComentario" name="comentario" rows="2" maxlength="500" class="form-control input-app"
                                  placeholder="Ej. el cliente no lo pidió así"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-end gap-3">
                    <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn-app btn-app-danger">Rechazar solicitud</button>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script src="{{ asset('javascript/confirmar.js') }}"></script>
    <script>
        // Rechazar: la ventana toma la dirección y el folio del botón
        document.getElementById('modalRechazar').addEventListener('show.bs.modal', function (event) {
            const boton = event.relatedTarget;
            document.getElementById('formRechazar').action = boton.dataset.url;
            document.getElementById('rechazarFolio').textContent = boton.dataset.folio;
            document.getElementById('rechazarComentario').value = '';
        });
    </script>
@endpush
