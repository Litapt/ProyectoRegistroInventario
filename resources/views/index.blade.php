{{--
    Inicio de sesión. Es la página principal "/".
    Manda correoElectronico y contrasena a LoginController@login.
    Es el mismo inicio de sesión para los 5 roles.
    {{ }} escapa el texto automáticamente, igual que htmlspecialchars().
--}}
@extends('layouts.base')

@section('titulo', 'Inicio de sesión')
@section('clase-body', 'd-flex flex-column min-vh-100 bg-white')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endpush

@section('contenido')
    <header class="app-header d-flex align-items-center justify-content-between px-4 py-3 bg-white">
        <a href="{{ route('login') }}" class="d-flex align-items-center gap-12 text-decoration-none">
            <span class="logo-box d-flex align-items-center justify-content-center">
                <img src="{{ \App\Support\Tienda::logo() }}" width="20" height="20" alt="" class="logo-tienda">
            </span>
            <span class="brand-name fw-bold">{{ \App\Support\Tienda::nombre() }}</span>
        </a>
        <a href="#" class="header-link fw-medium text-decoration-none">Ayuda</a>
    </header>

    <main class="login-main flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="login-card bg-white d-flex flex-column">

            <div class="d-flex flex-column align-items-center gap-12 text-center">
                <span class="login-icon d-flex align-items-center justify-content-center">
                    <img src="{{ asset('imagenes/lock.svg') }}" width="28" height="28" alt="">
                </span>
                <div class="d-flex flex-column gap-2">
                    <h1 class="login-title fw-bold m-0">Iniciar sesión</h1>
                    <p class="login-subtitle m-0">Ingresa tus datos para continuar en tu cuenta.</p>
                </div>
            </div>

            <form action="{{ route('login.entrar') }}" method="post" class="login-form d-flex flex-column">
                {{-- Igual que csrf_campo(): sin este campo Laravel rechaza el formulario --}}
                @csrf

                <div class="login-fields d-flex flex-column">
                    <div>
                        <label for="correo" class="form-label login-label fw-medium mb-2">Correo electrónico</label>
                        <input type="email" id="correo" name="correoElectronico" value="{{ old('correoElectronico') }}"
                               class="form-control login-input" placeholder="tu@email.com"
                               maxlength="120" autocomplete="username" required autofocus>
                    </div>

                    <div>
                        <label for="password" class="form-label login-label fw-medium mb-2">Contraseña</label>
                        <div class="position-relative">
                            <input type="password" id="password" name="contrasena"
                                   class="form-control login-input login-input-password" placeholder="••••••••"
                                   maxlength="255" autocomplete="current-password" required>
                            <button type="button" id="togglePassword" class="btn-eye position-absolute top-50 translate-middle-y" aria-label="Mostrar contraseña">
                                <img src="{{ asset('imagenes/eye.svg') }}" width="20" height="20" alt="">
                            </button>
                        </div>
                    </div>

                    {{-- Por ahora el administrador restablece las contraseñas desde "Usuarios" --}}
                    <div class="text-end">
                        <button type="button" class="login-link btn btn-link p-0 fw-medium text-decoration-none" data-bs-toggle="modal" data-bs-target="#olvideModal">¿Olvidaste tu contraseña?</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100 fw-semibold">Iniciar sesión</button>

                {{-- No hay "Regístrate": las cuentas las crea el administrador --}}
            </form>

        </div>
    </main>
@endsection

@push('modales')
    {{-- Ventana de Bootstrap que se abre con "¿Olvidaste tu contraseña?" --}}
    <div class="modal fade" id="olvideModal" tabindex="-1" aria-labelledby="olvideTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5 fw-bold" id="olvideTitulo">¿Olvidaste tu contraseña?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    Pide al administrador de la refaccionaria que restablezca tu contraseña desde la sección
                    <strong>Usuarios</strong>. Te dará una nueva para que vuelvas a entrar.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-login fw-semibold" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script src="{{ asset('javascript/login.js') }}"></script>
@endpush
