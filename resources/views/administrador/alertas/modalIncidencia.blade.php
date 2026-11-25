{{--
    Ventana "Detalle de la incidencia". La llena incidencias.js con los datos del botón "Ver detalle".
    Abierta → "Marcar en revisión" o "Resolver". En revisión → "Resolver". Resuelta → sólo se ve.
    Al resolver se pueden dar de baja del inventario las piezas que ya no sirven.
--}}
<div class="modal fade modal-app" id="modalIncidencia" tabindex="-1" aria-labelledby="incidenciaTitulo" aria-hidden="true"
     @if ($incidenciaReabierta)
         data-reabrir="{{ json_encode([
             'datos' => $incidenciaReabierta->datosIncidencia(),
             'revision' => route('administrador.alertas.incidencias.revision', $incidenciaReabierta),
             'resolver' => route('administrador.alertas.incidencias.resolver', $incidenciaReabierta),
         ]) }}"
     @endif>
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0" id="incidenciaTitulo">Incidencia <span id="incFolio"></span></h2>
                        <p class="panel-text fs-6 m-0 mt-1" id="incSubtitulo"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                @if ($incidenciaReabierta && $errors->resolver->any())
                    <div class="alert alert-danger mb-0" role="alert" id="incErrores">
                        @foreach ($errors->resolver->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-sm-6"><div class="field-help">Producto</div><div class="fw-semibold text-dark" id="incProducto"></div></div>
                    <div class="col-sm-3"><div class="field-help">Piezas afectadas</div><div class="fw-semibold text-dark" id="incCantidad"></div></div>
                    <div class="col-sm-3"><div class="field-help">Prioridad</div><div class="fw-semibold text-dark" id="incPrioridad"></div></div>
                    <div class="col-sm-6"><div class="field-help">Reportado por</div><div class="text-dark" id="incReporto"></div></div>
                    <div class="col-sm-6"><div class="field-help">Estado</div><div class="text-dark" id="incEstado"></div></div>
                    <div class="col-12"><div class="field-help">Descripción</div><div class="tarjeta-gris text-dark" id="incDescripcion"></div></div>
                </div>

                <div class="notice notice-ok d-none" id="incResuelta">
                    <div class="notice-title">Resuelta</div>
                    <div class="notice-text" id="incResolucion"></div>
                </div>

                {{-- Resolver --}}
                <form method="post" action="" id="formResolver" class="d-flex flex-column gap-3 pt-3 border-top" novalidate>
                    @csrf
                    @method('PATCH')
                    <h3 class="panel-title m-0">Resolver incidencia</h3>
                    <div>
                        <label for="incComentario" class="field-label-app">¿Cómo se resolvió?</label>
                        <textarea id="incComentario" name="comentario" rows="2" maxlength="500" class="form-control input-app"
                                  placeholder="Ej. se mandó a garantía con el proveedor y se sacaron del estante">{{ $incidenciaReabierta ? old('comentario') : '' }}</textarea>
                    </div>
                    <div style="max-width: 320px">
                        <label for="incBaja" class="field-label-app">Piezas a dar de baja del inventario</label>
                        <input type="number" id="incBaja" name="piezasDeBaja" min="0" step="1" class="form-control input-app"
                               value="{{ $incidenciaReabierta ? old('piezasDeBaja', 0) : 0 }}">
                        <p class="field-help m-0 mt-1" id="incBajaAyuda">Pon 0 si las piezas siguen sirviendo.</p>
                    </div>
                </form>
            </div>

            <div class="modal-footer justify-content-end gap-2 flex-wrap">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cerrar</button>
                <form method="post" action="" id="formRevision" class="m-0">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-app btn-app-soft" id="incBotonRevision">Marcar en revisión</button>
                </form>
                <button type="submit" form="formResolver" class="btn-app btn-app-primary" id="incBotonResolver">Resolver</button>
            </div>
        </div>
    </div>
</div>
