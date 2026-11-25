{{--
    Ventana "Editar venta" (Figma: "Modal · Editar venta").
    Cambia cliente, productos y cantidades. NO se aplica sola: se guarda como solicitud.
      - "Autorizar y guardar": un gerente o administrador pone sus datos → se aplica ya.
      - "Enviar solicitud": queda pendiente en Ventas → Solicitudes.
    La llena javascript/administrador/editarVenta.js con los datos del botón "Editar"
    (Venta::datosParaEditar()). Si el servidor regresa un error, se vuelve a abrir con lo escrito.
--}}
<div class="modal fade modal-app" id="modalEditarVenta" tabindex="-1" aria-labelledby="editarVentaTitulo" aria-hidden="true"
     data-buscar-clientes="{{ route('administrador.ventas.buscarClientes') }}"
     data-buscar-productos="{{ route('administrador.ventas.buscarProductos') }}"
     data-icono-quitar="{{ asset('imagenes/icon-trash.svg') }}"
     @if ($edicionReabierta)
         data-reabrir="{{ json_encode($edicionReabierta) }}"
     @endif>
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form method="post" action="" class="modal-content" id="formEditarVenta" autocomplete="off" novalidate>
            @csrf
            @method('PUT')
            <div id="editarOcultos"></div>

            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0" id="editarVentaTitulo">Editar venta</h2>
                        <p class="panel-text fs-6 m-0 mt-1">Actualiza el cliente, los productos y las cantidades.</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="rol-chip" id="editarFolio"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                </div>

                @if ($edicionReabierta && $errors->has('editar'))
                    <div class="alert alert-danger mb-0" role="alert" id="editarErrores">
                        @foreach ($errors->get('editar') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                {{-- Cliente --}}
                <div>
                    <div class="field-label-app">Cliente</div>
                    <div class="cliente-elegido" id="editarClienteElegido">
                        <div>
                            <div class="result-name" id="editarClienteNombre"></div>
                            <div class="result-detail" id="editarClienteDetalle"></div>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn-text" id="editarClienteCambiar">Cambiar</button>
                            <button type="button" class="btn-text" id="editarClienteQuitar">Quitar</button>
                        </div>
                    </div>
                    <div class="d-none" id="editarClienteBuscador">
                        <input type="search" id="editarBuscarCliente" maxlength="60" class="form-control input-app"
                               placeholder="Busca por nombre o teléfono (Enter)">
                        <div class="results-list mt-2 d-none" id="editarResultadosClientes"></div>
                    </div>
                </div>

                {{-- Productos --}}
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <h3 class="panel-title m-0">Productos</h3>
                        <span class="field-help">Los que ya estaban conservan su precio de venta</span>
                    </div>
                    <div>
                        <input type="search" id="editarBuscarProducto" maxlength="60" class="form-control input-app"
                               placeholder="Agregar producto: nombre, código o marca (Enter)">
                        <div class="results-list mt-2 d-none" id="editarResultadosProductos"></div>
                    </div>
                    <div class="tarjeta-gris d-flex flex-column gap-2" id="editarRenglones"></div>
                </div>

                {{-- Descuento --}}
                <div class="d-flex flex-wrap align-items-end gap-3">
                    <div style="max-width: 200px">
                        <label for="editarDescuentoInput" class="field-label-app">Descuento sobre el total</label>
                        <div class="input-group">
                            <input type="number" id="editarDescuentoInput" min="0" max="100" step="0.01" inputmode="decimal"
                                   class="form-control input-app" placeholder="0">
                            <span class="input-group-text input-suffix">%</span>
                        </div>
                    </div>
                    <p class="field-help m-0 pb-2" id="editarDescuentoAyuda"></p>
                </div>

                {{-- Totales --}}
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 pt-3 border-top">
                    <div class="small text-muted">
                        <div>Subtotal: <span id="editarSubtotal"></span></div>
                        <div id="editarDescuentoFila">Descuento (<span id="editarPorcentaje"></span> %): <span id="editarDescuento"></span></div>
                        <div>IVA: <span id="editarIva"></span></div>
                        <div>Antes: <span id="editarTotalAntes"></span></div>
                    </div>
                    <div class="d-flex align-items-baseline gap-3">
                        <span class="text-muted">Total</span>
                        <span class="summary-total" id="editarTotal"></span>
                    </div>
                </div>

                <div>
                    <label for="editarMotivo" class="field-label-app">¿Por qué se edita?</label>
                    <textarea id="editarMotivo" name="motivo" rows="2" maxlength="500" required class="form-control input-app"
                              placeholder="Ej. el cliente cambió las balatas por otra marca">{{ $edicionReabierta ? old('motivo') : '' }}</textarea>
                </div>

                <div class="tarjeta-gris">
                    <p class="fw-semibold text-dark mb-1">Autorización del gerente o administrador</p>
                    <p class="field-help mb-2">
                        Si está aquí, que ponga sus datos y el cambio se aplica ya. Si no, usa «Enviar solicitud»:
                        la venta cambiará cuando la acepten en Ventas → Solicitudes.
                    </p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="editarCorreo" class="field-label-app">Correo</label>
                            <input type="email" id="editarCorreo" name="correo_autoriza" maxlength="120"
                                   value="{{ $edicionReabierta ? old('correo_autoriza') : '' }}"
                                   class="form-control input-app" placeholder="gerente@correo.com">
                        </div>
                        <div class="col-sm-6">
                            <label for="editarContrasena" class="field-label-app">Contraseña</label>
                            <input type="password" id="editarContrasena" name="contrasena_autoriza" maxlength="255"
                                   class="form-control input-app" placeholder="Ingresa la contraseña" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer flex-wrap justify-content-end gap-2">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-app btn-app-soft" value="solicitud" id="editarEnviarSolicitud">Enviar solicitud</button>
                <button type="submit" class="btn-app btn-app-primary" value="autorizar" id="editarAutorizar">Autorizar y guardar</button>
            </div>
        </form>
    </div>
</div>
