{{--
    "Registrar nuevo cliente" desde Ventas: sólo lo básico (nombre, teléfono, correo).
    Se guarda sin salir de la venta (ventas.js lo manda a Administrador\ClienteController@store
    en JSON) y el cliente queda elegido. Los demás datos se completan en Clientes.
--}}
<div class="modal fade modal-app" id="modalClienteRapido" tabindex="-1" aria-labelledby="clienteRapidoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formClienteRapido" novalidate>
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0" id="clienteRapidoTitulo">Registrar nuevo cliente</h2>
                        <p class="panel-text fs-6 m-0 mt-1">Queda elegido para esta venta.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="alert alert-danger mb-0 d-none" id="clienteRapidoErrores" role="alert"></div>

                <div>
                    <label for="clienteRapidoNombre" class="field-label-app">Nombre completo</label>
                    <input type="text" id="clienteRapidoNombre" name="nombre" maxlength="80" required
                           class="form-control input-app" placeholder="Nombre y apellidos">
                </div>
                <div>
                    <label for="clienteRapidoTelefono" class="field-label-app">Teléfono</label>
                    <input type="tel" id="clienteRapidoTelefono" name="telefono" maxlength="20" required
                           class="form-control input-app" placeholder="Ej. 613 123 4567">
                </div>
                <div>
                    <label for="clienteRapidoCorreo" class="field-label-app">Correo <span class="field-help">(opcional)</span></label>
                    <input type="email" id="clienteRapidoCorreo" name="correo" maxlength="120"
                           class="form-control input-app" placeholder="nombre@correo.com">
                </div>
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-app btn-app-primary" id="clienteRapidoGuardar">Guardar cliente</button>
            </div>
        </form>
    </div>
</div>
