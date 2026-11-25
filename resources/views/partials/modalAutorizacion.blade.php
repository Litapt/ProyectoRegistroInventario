{{--
    Ventana de AUTORIZACIÓN reutilizable (Figma: "Confirmar edición" / "Confirmar eliminación").
    Pide correo y contraseña de un gerente o administrador (App\Support\Autorizacion).
    Los campos se llaman correo_autoriza y contrasena_autoriza.

    Dos formas de usarla (javascript/autorizacion.js):
    1) Un botón con data-bs-toggle="modal" data-bs-target="#modalAutorizacion" y
       data-url, data-titulo, data-texto, data-nombre, data-detalle, data-boton, data-clase
       → la ventana manda su propio formulario (PATCH) a data-url.
    2) Desde JavaScript: Autorizacion.abrir({ ..., formulario: elFormulario })
       → copia correo y contraseña a ese formulario y lo envía (así se confirma "Editar").
--}}
<div class="modal fade modal-app" id="modalAutorizacion" tabindex="-1" aria-labelledby="autorizacionTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" class="modal-content" id="formAutorizacion" autocomplete="off">
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
                    <h2 class="modal-app-title m-0" id="autorizacionTitulo"></h2>
                    <p class="panel-text fs-6 m-0 mt-2" id="autorizacionTexto"></p>
                </div>

                <div class="tarjeta-gris">
                    <div class="fw-semibold text-dark" id="autorizacionNombre"></div>
                    <div class="small text-muted" id="autorizacionDetalle"></div>
                </div>

                <div>
                    <p class="fw-semibold text-dark mb-2">Autorización del gerente o administrador</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="autorizacionCorreo" class="field-label-app">Correo</label>
                            <input type="email" id="autorizacionCorreo" name="correo_autoriza" maxlength="120" required
                                   class="form-control input-app" placeholder="gerente@correo.com">
                        </div>
                        <div class="col-sm-6">
                            <label for="autorizacionContrasena" class="field-label-app">Contraseña</label>
                            <input type="password" id="autorizacionContrasena" name="contrasena_autoriza" maxlength="255" required
                                   class="form-control input-app" placeholder="Ingresa la contraseña" autocomplete="new-password">
                        </div>
                    </div>
                    <p class="field-help m-0 mt-2">Sin la autorización de un gerente o administrador no se puede continuar.</p>
                </div>
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-app btn-app-danger" id="autorizacionBoton">Confirmar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('javascript/autorizacion.js') }}"></script>
@endpush
