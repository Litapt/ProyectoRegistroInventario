{{--
    Ventana "Cancelar venta": motivo + autorización de un gerente o administrador.
    Al cancelar, las piezas regresan al inventario y la venta queda CANCELADA (no se borra).
    Si algo falla, se vuelve a abrir sola con el motivo elegido (la contraseña no se regresa).
--}}
@php
    $reabrir = $ventaACancelar && !$ventaACancelar->estaCancelada();
@endphp

<div class="modal fade modal-app" id="modalCancelarVenta" tabindex="-1" aria-labelledby="cancelarVentaTitulo" aria-hidden="true"
     @if ($reabrir)
         data-reabrir="1"
         data-url="{{ route('administrador.ventas.cancelar', $ventaACancelar) }}"
         data-folio="{{ $ventaACancelar->folio }}"
         data-detalle="{{ $ventaACancelar->nombreCliente() }} · {{ \App\Models\Producto::formatoPrecio($ventaACancelar->total) }} · {{ $ventaACancelar->fechaVenta->format('d/m/Y h:i a') }}"
     @endif>
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" class="modal-content" id="formCancelarVenta" autocomplete="off" novalidate>
            @csrf
            @method('PATCH')
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between">
                    <span class="modal-app-icon d-flex align-items-center justify-content-center">
                        <span class="icono" style="--icono: url('{{ asset('imagenes/icon-triangle-alert-md.svg') }}')"></span>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div>
                    <h2 class="modal-app-title m-0" id="cancelarVentaTitulo">Cancelar venta <span id="cancelarFolio"></span></h2>
                    <p class="panel-text fs-6 m-0 mt-2">
                        Las piezas regresan al inventario y la venta queda como «Cancelada». No se borra: sigue en el historial.
                    </p>
                </div>

                <div class="tarjeta-gris small text-muted" id="cancelarDetalle"></div>

                @if ($reabrir && $errors->has('cancelar'))
                    <div class="alert alert-danger mb-0" role="alert">
                        @foreach ($errors->get('cancelar') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label for="cancelarMotivo" class="field-label-app">Motivo</label>
                    <select id="cancelarMotivo" name="motivo" required class="form-select input-app">
                        <option value="">Selecciona el motivo</option>
                        @foreach ($motivosCancelacion as $motivo)
                            <option value="{{ $motivo->idMotivo }}" @selected($reabrir && (string) old('motivo') === (string) $motivo->idMotivo)>{{ $motivo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="cancelarComentario" class="field-label-app">Comentario <span class="field-help">(opcional)</span></label>
                    <textarea id="cancelarComentario" name="comentario" rows="2" maxlength="500" class="form-control input-app"
                              placeholder="Ej. el cliente se llevó otra marca">{{ $reabrir ? old('comentario') : '' }}</textarea>
                </div>

                <div>
                    <p class="fw-semibold text-dark mb-2">Autorización del gerente o administrador</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="cancelarCorreo" class="field-label-app">Correo</label>
                            <input type="email" id="cancelarCorreo" name="correo_autoriza" maxlength="120" required
                                   value="{{ $reabrir ? old('correo_autoriza') : '' }}"
                                   class="form-control input-app" placeholder="gerente@correo.com">
                        </div>
                        <div class="col-sm-6">
                            <label for="cancelarContrasena" class="field-label-app">Contraseña</label>
                            <input type="password" id="cancelarContrasena" name="contrasena_autoriza" maxlength="255" required
                                   class="form-control input-app" placeholder="Ingresa la contraseña" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">No cancelar</button>
                <button type="submit" class="btn-app btn-app-danger" id="cancelarBoton">Cancelar venta</button>
            </div>
        </form>
    </div>
</div>
