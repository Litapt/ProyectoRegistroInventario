{{--
    Ventana "Registrar devolución" (no hay pantalla en el Figma; sigue el estilo de "Cancelar venta").
    Un producto por devolución. Pide motivo y la autorización de un gerente o administrador.
    La llena consultarVentas.js con los datos del botón (Venta::datosParaDevolucion()).
    Si el servidor regresa un error, se vuelve a abrir con lo elegido (sin la contraseña).
--}}
@php
    $reabrirDevolucion = $ventaADevolver && !$ventaADevolver->estaCancelada();
@endphp

<div class="modal fade modal-app" id="modalDevolucion" tabindex="-1" aria-labelledby="devolucionTitulo" aria-hidden="true"
     @if ($reabrirDevolucion)
         data-reabrir="{{ json_encode([
             'url' => route('administrador.ventas.devolucion', $ventaADevolver),
             'datos' => $ventaADevolver->datosParaDevolucion(),
             'producto' => old('producto'),
             'cantidad' => old('cantidad'),
             'motivo' => old('motivo'),
             'regresaInventario' => (bool) old('regresaInventario'),
         ]) }}"
     @endif>
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" class="modal-content" id="formDevolucion" autocomplete="off" novalidate>
            @csrf
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <h2 class="modal-app-title m-0" id="devolucionTitulo">Registrar devolución</h2>
                        <p class="panel-text fs-6 m-0 mt-1" id="devolucionSubtitulo"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                @if ($reabrirDevolucion && $errors->has('devolucion'))
                    <div class="alert alert-danger mb-0" role="alert" id="devolucionErrores">
                        @foreach ($errors->get('devolucion') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-sm-8">
                        <label for="devolucionProducto" class="field-label-app">Producto</label>
                        <select id="devolucionProducto" name="producto" class="form-select input-app"></select>
                    </div>
                    <div class="col-sm-4">
                        <label for="devolucionCantidad" class="field-label-app">Piezas</label>
                        <input type="number" id="devolucionCantidad" name="cantidad" min="1" step="1" value="1" class="form-control input-app">
                        <p class="field-help m-0 mt-1" id="devolucionMaximo"></p>
                    </div>
                </div>

                <div>
                    <label for="devolucionMotivo" class="field-label-app">Motivo</label>
                    <select id="devolucionMotivo" name="motivo" class="form-select input-app">
                        <option value="">Selecciona el motivo</option>
                        @php
                            // Motivos de piezas que no se deben volver a vender (quitan la palomita de inventario)
                            $noRevender = ['dañad', 'defectuos', 'falla', 'garantía', 'garantia', 'incomplet'];
                        @endphp
                        @foreach ($motivosDevolucion as $motivo)
                            <option value="{{ $motivo->idMotivo }}"
                                    data-danado="{{ \Illuminate\Support\Str::contains(mb_strtolower($motivo->nombre), $noRevender) ? '1' : '' }}">{{ $motivo->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="devolucionInventario" name="regresaInventario" value="1" checked>
                    <label class="form-check-label" for="devolucionInventario">
                        Las piezas están en buen estado: <strong>regresan al inventario</strong>
                    </label>
                    <div class="field-help">Si llegaron dañadas, quita la palomita: no se suman al inventario.</div>
                </div>

                <div>
                    <label for="devolucionComentario" class="field-label-app">Comentario <span class="field-help">(opcional)</span></label>
                    <textarea id="devolucionComentario" name="comentario" rows="2" maxlength="500" class="form-control input-app"
                              placeholder="Ej. era para Jetta 2015, no 2018">{{ $reabrirDevolucion ? old('comentario') : '' }}</textarea>
                </div>

                <div class="tarjeta-gris d-flex align-items-center justify-content-between">
                    <span class="fw-semibold text-dark">Reembolso al cliente</span>
                    <span class="summary-total fs-4" id="devolucionReembolso">$0.00</span>
                </div>

                <div>
                    <p class="fw-semibold text-dark mb-2">Autorización del gerente o administrador</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="devolucionCorreo" class="field-label-app">Correo</label>
                            <input type="email" id="devolucionCorreo" name="correo_autoriza" maxlength="120"
                                   value="{{ $reabrirDevolucion ? old('correo_autoriza') : '' }}"
                                   class="form-control input-app" placeholder="gerente@correo.com">
                        </div>
                        <div class="col-sm-6">
                            <label for="devolucionContrasena" class="field-label-app">Contraseña</label>
                            <input type="password" id="devolucionContrasena" name="contrasena_autoriza" maxlength="255"
                                   class="form-control input-app" placeholder="Ingresa la contraseña" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-app btn-app-primary" id="devolucionBoton">Registrar devolución</button>
            </div>
        </form>
    </div>
</div>
