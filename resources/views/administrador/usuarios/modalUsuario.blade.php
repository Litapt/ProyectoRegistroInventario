{{--
    Ventana "Registrar usuario" / "Editar usuario" (Figma: Modal · Registrar usuario y Modal · Editar usuario).
    Una sola ventana: usuarios.js la cambia a "Editar" y la llena.
    Diferencia con el Figma: el nombre va en 3 campos y se pide el RFC,
    porque la tabla trabajadores (plan del equipo) los necesita.
    Si el servidor regresó errores, se vuelve a abrir sola con lo que se había escrito
    (las contraseñas nunca se regresan, hay que escribirlas otra vez).
--}}
@php
    $modo = session('modalUsuario');                   // null, 'registrar' o 'editar'
    $editando = $modo === 'editar' && $usuarioReabierto;
    $rolElegido = (string) old('rol', $editando ? $usuarioReabierto->fk_idRol : '');
    $esYo = $editando && (int) $usuarioReabierto->idTrabajador === (int) auth()->id();
@endphp

<div class="modal fade modal-app" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioTitulo" aria-hidden="true"
     data-reabrir="{{ $modo && ($modo === 'registrar' || $editando) ? $modo : '' }}"
     data-url-registrar="{{ route('administrador.usuarios.store') }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" class="modal-content" id="formUsuario" novalidate autocomplete="off"
              action="{{ $editando ? route('administrador.usuarios.update', $usuarioReabierto) : route('administrador.usuarios.store') }}">
            @csrf
            <input type="hidden" name="_method" value="{{ $editando ? 'PUT' : 'POST' }}" id="usuarioMetodo">

            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0" id="modalUsuarioTitulo">{{ $editando ? 'Editar usuario' : 'Registrar usuario' }}</h2>
                        <p class="panel-text fs-6 m-0 mt-1" id="modalUsuarioSubtitulo">
                            {{ $editando ? 'Actualiza la información de ' . $usuarioReabierto->nombreCompleto() . '.' : 'Agrega a una persona que podrá entrar al sistema.' }}
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                @if ($modo)
                    @include('partials.errores')
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="usuarioNombres" class="field-label-app">Nombre(s)</label>
                        <input type="text" id="usuarioNombres" name="nombres" value="{{ old('nombres') }}" maxlength="80" required
                               class="form-control input-app @error('nombres') is-invalid @enderror" placeholder="Ej. Carlos">
                    </div>
                    <div class="col-md-4">
                        <label for="usuarioApellidoPaterno" class="field-label-app">Apellido paterno</label>
                        <input type="text" id="usuarioApellidoPaterno" name="apellidoPaterno" value="{{ old('apellidoPaterno') }}" maxlength="60" required
                               class="form-control input-app @error('apellidoPaterno') is-invalid @enderror" placeholder="Ej. Méndez">
                    </div>
                    <div class="col-md-4">
                        <label for="usuarioApellidoMaterno" class="field-label-app">Apellido materno <span class="field-help">(opcional)</span></label>
                        <input type="text" id="usuarioApellidoMaterno" name="apellidoMaterno" value="{{ old('apellidoMaterno') }}" maxlength="60"
                               class="form-control input-app @error('apellidoMaterno') is-invalid @enderror" placeholder="Ej. Ruiz">
                    </div>

                    <div class="col-md-6">
                        <label for="usuarioCorreo" class="field-label-app">Correo</label>
                        <input type="email" id="usuarioCorreo" name="correo" value="{{ old('correo') }}" maxlength="120" required autocomplete="off"
                               class="form-control input-app @error('correo') is-invalid @enderror" placeholder="nombre@correo.com">
                        <p class="field-help m-0 mt-1">Con este correo inicia sesión.</p>
                    </div>
                    <div class="col-md-6">
                        <label for="usuarioTelefono" class="field-label-app">Teléfono <span class="field-help">(opcional)</span></label>
                        <input type="tel" id="usuarioTelefono" name="telefono" value="{{ old('telefono') }}" maxlength="20"
                               class="form-control input-app @error('telefono') is-invalid @enderror" placeholder="Ej. 613 123 4567">
                    </div>

                    <div class="col-md-6">
                        <label for="usuarioRfc" class="field-label-app">RFC</label>
                        <input type="text" id="usuarioRfc" name="rfc" value="{{ old('rfc') }}" maxlength="13" required
                               class="form-control input-app mayusculas @error('rfc') is-invalid @enderror" placeholder="13 caracteres">
                    </div>
                    <div class="col-md-6">
                        <label for="usuarioRol" class="field-label-app">Rol</label>
                        <select id="usuarioRol" name="rol" required class="form-select input-app @error('rol') is-invalid @enderror" @disabled($esYo)>
                            <option value="" @selected($rolElegido === '')>Selecciona un rol</option>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->idRol }}" @selected($rolElegido === (string) $rol->idRol)>{{ ucfirst(mb_strtolower($rol->nombre)) }}</option>
                            @endforeach
                        </select>
                        {{-- Un select desactivado no se envía: se manda el rol con este campo oculto --}}
                        <input type="hidden" name="rol" id="usuarioRolFijo" value="{{ $rolElegido }}" @disabled(!$esYo)>
                        <p class="field-help m-0 mt-1" id="usuarioRolAyuda">{{ $esYo ? 'No puedes cambiar tu propio rol.' : 'Define qué pantallas y acciones puede usar.' }}</p>
                    </div>

                    <div class="col-12">
                        <p class="fw-semibold text-dark m-0" id="usuarioContrasenaTitulo">{{ $editando ? 'Cambiar contraseña' : 'Contraseña' }}</p>
                        <p class="field-help m-0" id="usuarioContrasenaAyuda">
                            {{ $editando ? 'Déjalas vacías para conservar la contraseña actual.' : 'Usa al menos 8 caracteres, con letras y números.' }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label for="usuarioContrasena" class="field-label-app" id="usuarioContrasenaEtiqueta">{{ $editando ? 'Nueva contraseña' : 'Contraseña' }}</label>
                        <input type="password" id="usuarioContrasena" name="contrasena" maxlength="255" autocomplete="new-password"
                               class="form-control input-app @error('contrasena') is-invalid @enderror" placeholder="Mínimo 8 caracteres">
                    </div>
                    <div class="col-md-6">
                        <label for="usuarioContrasenaConfirmar" class="field-label-app">Confirmar contraseña</label>
                        <input type="password" id="usuarioContrasenaConfirmar" name="contrasena_confirmation" maxlength="255" autocomplete="new-password"
                               class="form-control input-app @error('contrasena') is-invalid @enderror" placeholder="Escríbela otra vez">
                    </div>

                </div>
            </div>

            <div class="modal-footer justify-content-between gap-3">
                <span class="field-help" id="usuarioCodigo">{{ $editando ? 'Usuario ' . $usuarioReabierto->codigo() : '' }}</span>
                <div class="d-flex gap-3">
                    <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-app btn-app-primary" id="usuarioGuardar">{{ $editando ? 'Guardar cambios' : 'Registrar usuario' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
