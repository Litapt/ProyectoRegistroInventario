{{--
    Ventana de AUTORIZACIÓN para "Eliminar producto" y "Reactivar"
    (Figma: Modal · Eliminar producto con autorización).
    El producto NO se borra: se desactiva y se puede reactivar (decisión del equipo).
    Regla: desactivar o reactivar sólo con correo y contraseña de un gerente o administrador.
    productos.js cambia títulos, textos y botón según el botón que se presionó.
--}}
<div class="modal fade modal-app" id="modalDesactivar" tabindex="-1" aria-labelledby="modalDesactivarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" class="modal-content" id="formDesactivar" autocomplete="off">
            @csrf
            @method('PATCH')
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between">
                    <span class="modal-app-icon d-flex align-items-center justify-content-center" id="autorizarIcono">
                        <span class="icono" style="--icono: url('{{ asset('imagenes/icon-triangle-alert-md.svg') }}')"></span>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div>
                    <h2 class="modal-app-title m-0" id="modalDesactivarTitulo">Eliminar producto</h2>
                    <p class="panel-text fs-6 m-0 mt-2" id="modalDesactivarTexto"></p>
                </div>

                <div class="tarjeta-gris">
                    <div class="fw-semibold text-dark" id="desactivarNombre"></div>
                    <div class="small text-muted" id="desactivarDetalle"></div>
                </div>

                <div>
                    <p class="fw-semibold text-dark mb-2">Autorización del gerente o administrador</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="desactivarCorreo" class="field-label-app">Correo</label>
                            <input type="email" id="desactivarCorreo" name="correo" maxlength="120" required
                                   class="form-control input-app" placeholder="gerente@correo.com">
                        </div>
                        <div class="col-sm-6">
                            <label for="desactivarContrasena" class="field-label-app">Contraseña</label>
                            <input type="password" id="desactivarContrasena" name="contrasena" maxlength="255" required
                                   class="form-control input-app" placeholder="Ingresa la contraseña" autocomplete="new-password">
                        </div>
                    </div>
                    <p class="field-help m-0 mt-2">Sin la autorización de un gerente o administrador no se puede continuar.</p>
                </div>
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-app btn-app-danger" id="desactivarBoton">Eliminar</button>
            </div>
        </form>
    </div>
</div>
