{{--
    "Mi perfil" (menú del usuario → Mi perfil). La misma pantalla para todos los roles;
    con menú lateral (administrador, gerente) o sólo barra de arriba (los demás).
    Lo manda Perfil\PerfilController@index.
--}}
@extends($layout)

@section('titulo', 'Mi perfil')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/perfil.css') }}">
@endpush

@section('pagina')
    <div class="perfil-contenedor">
        <a href="{{ route($yo->rutaInicio()) }}" class="btn-text d-inline-flex align-items-center gap-2 mb-3">← Volver a la pantalla principal</a>

        <h1 class="page-heading mb-4">Mi perfil</h1>

        <div class="row g-4">
            {{-- Tarjeta con quién soy --}}
            <div class="col-lg-4">
                <section class="panel">
                    <div class="panel-body d-flex flex-column align-items-center text-center gap-2">
                        @if ($yo->urlFoto())
                            <img src="{{ $yo->urlFoto() }}" alt="Tu foto de perfil" class="perfil-avatar perfil-avatar-foto" id="vistaFoto">
                        @else
                            <span class="perfil-avatar" id="vistaIniciales">{{ $yo->iniciales() }}</span>
                            <img src="" alt="Tu foto de perfil" class="perfil-avatar perfil-avatar-foto d-none" id="vistaFoto">
                        @endif

                        {{-- Foto de perfil --}}
                        <form method="post" action="{{ route('perfil.foto') }}" enctype="multipart/form-data" id="formFoto" class="d-flex flex-wrap justify-content-center gap-2">
                            @csrf
                            <label for="foto" class="btn-app btn-app-outline btn-app-sm mb-0">{{ $yo->urlFoto() ? 'Cambiar foto' : 'Subir foto' }}</label>
                            <input type="file" id="foto" name="foto" accept="image/png,image/jpeg" class="d-none">
                            @if ($yo->urlFoto())
                                <button type="submit" form="formQuitarFoto" class="btn-app btn-app-outline btn-app-sm">Quitar</button>
                            @endif
                        </form>
                        @if ($yo->urlFoto())
                            <form method="post" action="{{ route('perfil.foto.quitar') }}" id="formQuitarFoto" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                        <p class="field-help m-0" id="ayudaFoto">PNG o JPG · Hasta 2 MB</p>
                        @if ($errors->foto->any())
                            <div class="alert alert-danger py-2 mb-0 w-100" role="alert">{{ $errors->foto->first() }}</div>
                        @endif

                        <div class="fw-bold text-dark fs-5">{{ $yo->nombreCompleto() }}</div>
                        <span class="badge-estado badge-primary">{{ $yo->rolTexto() }}</span>
                        <dl class="perfil-datos w-100 text-start mt-3 mb-0">
                            <dt>Usuario</dt><dd>{{ $yo->codigo() }}</dd>
                            <dt>Correo</dt><dd>{{ $yo->correoElectronico }}</dd>
                            <dt>RFC</dt><dd>{{ $yo->RFC }}</dd>
                            <dt>Teléfono</dt><dd>{{ $yo->telefono ?: '—' }}</dd>
                            <dt>Alta</dt><dd>{{ $yo->fechaCreacion?->format('d/m/Y') ?? '—' }}</dd>
                            <dt>Último acceso</dt><dd>{{ $yo->fechaUltimoAcceso?->format('d/m/Y h:i a') ?? '—' }}</dd>
                        </dl>
                        <p class="field-help m-0 mt-2">El rol y el RFC sólo los cambia el administrador.</p>
                    </div>
                </section>
            </div>

            <div class="col-lg-8 d-flex flex-column gap-4">
                {{-- Mis datos --}}
                <section class="panel" id="datos">
                    <form method="post" action="{{ route('perfil.datos') }}" class="panel-body d-flex flex-column gap-3" novalidate autocomplete="off">
                        @csrf
                        @method('PUT')
                        <div>
                            <h2 class="panel-title m-0">Mis datos</h2>
                            <p class="panel-text m-0 mt-1">Si cambias tu correo, desde ese momento inicias sesión con el nuevo.</p>
                        </div>

                        @if ($errors->datos->any())
                            <div class="alert alert-danger mb-0" role="alert">
                                @foreach ($errors->datos->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="perfilNombres" class="field-label-app">Nombre(s)</label>
                                <input type="text" id="perfilNombres" name="nombres" maxlength="80" required value="{{ old('nombres', $yo->nombres) }}"
                                       class="form-control input-app @error('nombres', 'datos') is-invalid @enderror">
                            </div>
                            <div class="col-md-4">
                                <label for="perfilPaterno" class="field-label-app">Apellido paterno</label>
                                <input type="text" id="perfilPaterno" name="apellidoPaterno" maxlength="60" required value="{{ old('apellidoPaterno', $yo->apellidoPaterno) }}"
                                       class="form-control input-app @error('apellidoPaterno', 'datos') is-invalid @enderror">
                            </div>
                            <div class="col-md-4">
                                <label for="perfilMaterno" class="field-label-app">Apellido materno <span class="field-help">(opcional)</span></label>
                                <input type="text" id="perfilMaterno" name="apellidoMaterno" maxlength="60" value="{{ old('apellidoMaterno', $yo->apellidoMaterno) }}"
                                       class="form-control input-app">
                            </div>
                            <div class="col-md-6">
                                <label for="perfilCorreo" class="field-label-app">Correo</label>
                                <input type="email" id="perfilCorreo" name="correo" maxlength="120" required value="{{ old('correo', $yo->correoElectronico) }}"
                                       data-original="{{ $yo->correoElectronico }}"
                                       class="form-control input-app @error('correo', 'datos') is-invalid @enderror">
                            </div>
                            <div class="col-md-6">
                                <label for="perfilTelefono" class="field-label-app">Teléfono <span class="field-help">(opcional)</span></label>
                                <input type="tel" id="perfilTelefono" name="telefono" maxlength="20" value="{{ old('telefono', $yo->telefono) }}"
                                       class="form-control input-app @error('telefono', 'datos') is-invalid @enderror" placeholder="613 123 4567">
                            </div>
                            <div class="col-md-6 {{ $errors->datos->has('contrasenaConfirmar') ? '' : 'd-none' }}" id="bloqueConfirmar">
                                <label for="perfilConfirmar" class="field-label-app">Tu contraseña actual</label>
                                <input type="password" id="perfilConfirmar" name="contrasenaConfirmar" maxlength="255" autocomplete="current-password"
                                       class="form-control input-app @error('contrasenaConfirmar', 'datos') is-invalid @enderror">
                                <p class="field-help m-0 mt-1">Se pide porque vas a cambiar tu correo.</p>
                            </div>
                        </div>

                        <div class="config-pie">
                            <button type="submit" class="btn-app btn-app-primary">Guardar mis datos</button>
                        </div>
                    </form>
                </section>

                {{-- Contraseña --}}
                <section class="panel" id="contrasena">
                    <form method="post" action="{{ route('perfil.contrasena') }}" class="panel-body d-flex flex-column gap-3" novalidate autocomplete="off">
                        @csrf
                        @method('PUT')
                        <div>
                            <h2 class="panel-title m-0">Cambiar contraseña</h2>
                            <p class="panel-text m-0 mt-1">Usa al menos 8 caracteres, con letras y números.</p>
                        </div>

                        @if ($errors->contrasena->any())
                            <div class="alert alert-danger mb-0" role="alert">
                                @foreach ($errors->contrasena->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="perfilActual" class="field-label-app">Contraseña actual</label>
                                <input type="password" id="perfilActual" name="contrasenaActual" maxlength="255" autocomplete="current-password"
                                       class="form-control input-app @error('contrasenaActual', 'contrasena') is-invalid @enderror">
                            </div>
                            <div class="col-md-4">
                                <label for="perfilNueva" class="field-label-app">Nueva contraseña</label>
                                <input type="password" id="perfilNueva" name="contrasenaNueva" maxlength="255" autocomplete="new-password"
                                       class="form-control input-app @error('contrasenaNueva', 'contrasena') is-invalid @enderror">
                            </div>
                            <div class="col-md-4">
                                <label for="perfilRepetir" class="field-label-app">Confirmar nueva contraseña</label>
                                <input type="password" id="perfilRepetir" name="contrasenaNueva_confirmation" maxlength="255" autocomplete="new-password"
                                       class="form-control input-app @error('contrasenaNueva', 'contrasena') is-invalid @enderror">
                            </div>
                        </div>

                        <div class="config-pie">
                            <button type="submit" class="btn-app btn-app-primary">Cambiar contraseña</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Foto: al elegirla se revisa, se ve antes y se sube sola
        (function () {
            const archivo = document.getElementById('foto');
            const ayuda = document.getElementById('ayudaFoto');
            archivo.addEventListener('change', function () {
                const elegida = archivo.files[0];
                if (!elegida) { return; }
                if (!/^image\/(png|jpeg)$/.test(elegida.type)) {
                    ayuda.textContent = 'Esa imagen no es PNG ni JPG.';
                    ayuda.classList.add('text-danger');
                    archivo.value = '';
                    return;
                }
                if (elegida.size > 2 * 1024 * 1024) {
                    ayuda.textContent = 'Pesa más de 2 MB. Elige una más ligera.';
                    ayuda.classList.add('text-danger');
                    archivo.value = '';
                    return;
                }
                const vista = document.getElementById('vistaFoto');
                vista.src = URL.createObjectURL(elegida);
                vista.classList.remove('d-none');
                const iniciales = document.getElementById('vistaIniciales');
                if (iniciales) { iniciales.classList.add('d-none'); }
                ayuda.classList.remove('text-danger');
                ayuda.textContent = 'Subiendo…';
                document.getElementById('formFoto').submit();
            });
        })();

        // Si cambia el correo, se pide la contraseña actual
        (function () {
            const correo = document.getElementById('perfilCorreo');
            const bloque = document.getElementById('bloqueConfirmar');
            function revisar() {
                const cambio = correo.value.trim().toLowerCase() !== correo.dataset.original.toLowerCase();
                bloque.classList.toggle('d-none', !cambio && !bloque.querySelector('.is-invalid'));
            }
            correo.addEventListener('input', revisar);
            revisar();
        })();
    </script>
@endpush
