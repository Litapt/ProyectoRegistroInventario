{{--
    Ventana "Registrar incidencia" (Figma: "Crear alerta nueva").
    El producto se busca con el mismo buscador de Ventas.
    Si el servidor regresa errores, se vuelve a abrir con lo escrito.
--}}
@php
    $reabrirRegistro = $errors->registrar->any();
@endphp
<div class="modal fade modal-app" id="modalRegistrarIncidencia" tabindex="-1" aria-labelledby="registrarIncidenciaTitulo" aria-hidden="true"
     data-buscar-productos="{{ \App\Support\Ruta::a('ventas.buscarProductos') }}"
     data-reabrir="{{ $reabrirRegistro ? '1' : '' }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" action="{{ \App\Support\Ruta::a('alertas.incidencias.store') }}" class="modal-content" id="formRegistrarIncidencia" novalidate autocomplete="off">
            @csrf
            <input type="hidden" name="producto" id="regProducto" value="{{ old('producto') }}">
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0" id="registrarIncidenciaTitulo">Registrar incidencia</h2>
                        <p class="panel-text fs-6 m-0 mt-1">Quedará en el historial y la verán el gerente y el administrador.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                @if ($reabrirRegistro)
                    <div class="alert alert-danger mb-0" role="alert">
                        @foreach ($errors->registrar->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div>
                    <span class="field-label-app d-block">Producto</span>
                    <div class="cliente-elegido {{ $productoAnterior ? '' : 'd-none' }}" id="regElegido">
                        <div>
                            <div class="result-name" id="regElegidoNombre">{{ $productoAnterior?->nombre }}</div>
                            <div class="result-detail" id="regElegidoDetalle">{{ $productoAnterior ? $productoAnterior->codigoInterno . ' · ' . $productoAnterior->existencia . ' en existencia' : '' }}</div>
                        </div>
                        <button type="button" class="btn-text" id="regCambiar">Cambiar</button>
                    </div>
                    <div class="{{ $productoAnterior ? 'd-none' : '' }}" id="regBuscador">
                        <input type="search" id="regBuscar" maxlength="60" class="form-control input-app" placeholder="Busca por nombre, código o marca (Enter)">
                        <div class="results-list mt-2 d-none" id="regResultados"></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="regTipo" class="field-label-app">Tipo de incidencia</label>
                        <select id="regTipo" name="tipo" class="form-select input-app">
                            <option value="">Selecciona el tipo</option>
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo->idMotivo }}" @selected((string) old('tipo') === (string) $tipo->idMotivo)>{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="regCantidad" class="field-label-app">Piezas</label>
                        <input type="number" id="regCantidad" name="cantidad" min="1" step="1" value="{{ old('cantidad', 1) }}" class="form-control input-app">
                    </div>
                    <div class="col-md-3">
                        <label for="regPrioridad" class="field-label-app">Prioridad</label>
                        <select id="regPrioridad" name="prioridad" class="form-select input-app">
                            @foreach (\App\Models\Solicitud::PRIORIDADES as $clave => $texto)
                                <option value="{{ $clave }}" @selected(old('prioridad', 'MEDIA') === $clave)>{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="regDescripcion" class="field-label-app">Descripción de la incidencia</label>
                        <textarea id="regDescripcion" name="descripcion" rows="3" maxlength="500" class="form-control input-app"
                                  placeholder="Describe el problema, el impacto y cualquier observación para el seguimiento.">{{ old('descripcion') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-app btn-app-primary" id="regGuardar">Registrar incidencia</button>
            </div>
        </form>
    </div>
</div>
