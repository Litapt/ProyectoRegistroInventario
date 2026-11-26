{{--
    Ventana "Registrar cliente" / "Editar cliente" (Figma: Modal · Registrar cliente y Modal · Editar cliente).
    Una sola ventana: clientes.js la cambia a "Editar" y la llena.
    Al editar, "Guardar cambios" abre la ventana de autorización (Figma: "Confirmar edición").
    Si el servidor regresó errores, se vuelve a abrir sola con lo que se había escrito.
--}}
@php
    $modo = session('modalCliente');                   // null, 'registrar' o 'editar'
    $editando = $modo === 'editar' && $clienteReabierto;
@endphp

<div class="modal fade modal-app" id="modalCliente" tabindex="-1" aria-labelledby="modalClienteTitulo" aria-hidden="true"
     data-reabrir="{{ $modo && ($modo === 'registrar' || $editando) ? $modo : '' }}"
     data-url-registrar="{{ \App\Support\Ruta::a('clientes.store') }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" class="modal-content" id="formCliente" novalidate
              action="{{ $editando ? \App\Support\Ruta::a('clientes.update', $clienteReabierto) : \App\Support\Ruta::a('clientes.store') }}"
              data-nombre-original="{{ $editando ? $clienteReabierto->nombre() : '' }}"
              data-codigo="{{ $editando ? $clienteReabierto->codigo() : '' }}">
            @csrf
            <input type="hidden" name="_method" value="{{ $editando ? 'PUT' : 'POST' }}" id="clienteMetodo">

            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0" id="modalClienteTitulo">{{ $editando ? 'Editar cliente' : 'Registrar cliente' }}</h2>
                        <p class="panel-text fs-6 m-0 mt-1" id="modalClienteSubtitulo">
                            {{ $editando ? 'Actualiza la información de ' . $clienteReabierto->nombre() . '.' : 'Agrega los datos del nuevo cliente.' }}
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                @if ($modo)
                    @include('partials.errores')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="clienteNombre" class="field-label-app">Nombre completo</label>
                        <input type="text" id="clienteNombre" name="nombre" value="{{ old('nombre') }}" maxlength="80" required
                               class="form-control input-app @error('nombre') is-invalid @enderror" placeholder="Nombre y apellidos">
                    </div>
                    <div class="col-md-6">
                        <label for="clienteTelefono" class="field-label-app">Teléfono</label>
                        <input type="tel" id="clienteTelefono" name="telefono" value="{{ old('telefono') }}" maxlength="20" required
                               class="form-control input-app @error('telefono') is-invalid @enderror" placeholder="Ej. 613 123 4567">
                    </div>
                    <div class="col-md-6">
                        <label for="clienteCorreo" class="field-label-app">Correo <span class="field-help">(opcional)</span></label>
                        <input type="email" id="clienteCorreo" name="correo" value="{{ old('correo') }}" maxlength="120"
                               class="form-control input-app @error('correo') is-invalid @enderror" placeholder="nombre@correo.com">
                    </div>
                    <div class="col-md-6">
                        <label for="clienteRfc" class="field-label-app">RFC <span class="field-help">(opcional)</span></label>
                        <input type="text" id="clienteRfc" name="rfc" value="{{ old('rfc') }}" maxlength="13"
                               class="form-control input-app mayusculas @error('rfc') is-invalid @enderror" placeholder="Ingresa el RFC">
                    </div>
                    <div class="col-md-6">
                        <label for="clienteDireccion" class="field-label-app">Dirección <span class="field-help">(opcional)</span></label>
                        <textarea id="clienteDireccion" name="direccion" rows="3" maxlength="255"
                                  class="form-control input-app @error('direccion') is-invalid @enderror" placeholder="Calle, número, colonia, ciudad y C.P.">{{ old('direccion') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="clienteNotas" class="field-label-app">Notas <span class="field-help">(opcional)</span></label>
                        <textarea id="clienteNotas" name="notas" rows="3" maxlength="500"
                                  class="form-control input-app @error('notas') is-invalid @enderror" placeholder="Agrega información relevante del cliente.">{{ old('notas') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-between gap-3">
                <span class="field-help" id="clienteCodigo">{{ $editando ? 'Cliente ' . $clienteReabierto->codigo() : '' }}</span>
                <div class="d-flex gap-3">
                    <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-app btn-app-primary" id="clienteGuardar">{{ $editando ? 'Guardar cambios' : 'Guardar' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
