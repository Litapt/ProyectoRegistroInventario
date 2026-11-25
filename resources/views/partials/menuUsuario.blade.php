{{--
    Botón con las iniciales del trabajador y su menú: Mi perfil y Cerrar sesión
    (Figma: "06 / Menú del administrador"). Lo usan todos los roles.
--}}
@php
    $trabajador = auth()->user();
@endphp

<div class="dropdown">
    <button type="button" class="user-button d-flex align-items-center gap-3" data-bs-toggle="dropdown" aria-expanded="false">
        @if ($trabajador->urlFoto())
            <img src="{{ $trabajador->urlFoto() }}" alt="" class="user-avatar user-avatar-foto">
        @else
            <span class="user-avatar d-flex align-items-center justify-content-center fw-semibold">{{ $trabajador->iniciales() }}</span>
        @endif
        <span class="d-none d-md-flex flex-column text-start">
            <span class="user-name fw-semibold">{{ $trabajador->nombres }} {{ $trabajador->apellidoPaterno }}</span>
            <span class="user-role">{{ $trabajador->rolTexto() }}</span>
        </span>
        <span class="icono icono-sm" style="--icono: url('{{ asset('imagenes/icon-chevron-down.svg') }}')"></span>
    </button>
    <div class="dropdown-menu dropdown-menu-end user-menu">
        <div class="dropdown-header">
            <div class="user-name fw-semibold">{{ $trabajador->nombreCompleto() }}</div>
            <div class="user-role">{{ $trabajador->rolTexto() }}</div>
        </div>
        <a href="{{ route('perfil') }}" class="dropdown-item mt-2">
            <span class="icono icono-sm" style="--icono: url('{{ asset('imagenes/icon-user-2.svg') }}')"></span>
            Mi perfil
        </a>
        <div class="dropdown-divider"></div>
        <form action="{{ route('logout') }}" method="post" class="m-0">
            @csrf
            <button type="submit" class="dropdown-item text-danger">
                <span class="icono icono-sm" style="--icono: url('{{ asset('imagenes/icon-logout.svg') }}')"></span>
                Cerrar sesión
            </button>
        </form>
    </div>
</div>
