{{--
    Pantallas CON menú lateral: administrador y gerente (Figma Page 2).
    Barra superior + menú lateral + contenido.

    Cada pantalla la usa así:
        @extends('layouts.panel')
        @section('titulo', 'Clientes')
        @section('menu', 'clientes')       ← qué opción del menú se ve marcada
        @section('pagina') ...contenido... @endsection

    Cada opción del menú sólo aparece si el rol tiene su permiso (tabla rolPermisos).
    'ruta' => null significa que esa pantalla todavía no se programa.
--}}
@extends('layouts.base')

@section('clase-body', 'admin-body')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
@endpush

@section('contenido')
    @php
        $trabajador = auth()->user();
        $rutaInicio = $trabajador->rutaInicio();
        $menuActivo = trim($__env->yieldContent('menu'));
        $opciones = [
            ['clave' => 'inicio', 'texto' => 'Inicio', 'icono' => 'icon-home.svg', 'permiso' => null, 'ruta' => $rutaInicio],
            ['clave' => 'ventas', 'texto' => 'Ventas', 'icono' => 'icon-cart-2.svg', 'permiso' => 'ventas.consultar', 'ruta' => null],
            ['clave' => 'clientes', 'texto' => 'Clientes', 'icono' => 'icon-users.svg', 'permiso' => 'clientes.gestionar', 'ruta' => null],
            ['clave' => 'productos', 'texto' => 'Productos', 'icono' => 'icon-box.svg', 'permiso' => 'productos.consultar', 'ruta' => null],
            ['clave' => 'usuarios', 'texto' => 'Usuarios', 'icono' => 'icon-user-2.svg', 'permiso' => 'usuarios.gestionar', 'ruta' => null],
            ['clave' => 'alertas', 'texto' => 'Alertas', 'icono' => 'icon-bell-2.svg', 'permiso' => 'solicitudes.autorizar', 'ruta' => null],
            ['clave' => 'configuracion', 'texto' => 'Configuración', 'icono' => 'icon-settings-2.svg', 'permiso' => 'configuracion.editar', 'ruta' => null],
        ];
    @endphp

    <header class="topbar d-flex align-items-center justify-content-between">
        <a href="{{ route($rutaInicio) }}" class="d-flex align-items-center gap-3 text-decoration-none">
            <span class="topbar-logo d-flex align-items-center justify-content-center">
                <img src="{{ asset('imagenes/logo.svg') }}" width="20" height="20" alt="">
            </span>
            <span class="topbar-brand fw-bold">{{ config('app.name') }}</span>
        </a>

        <div class="d-flex align-items-center gap-3">
            <a href="#" class="topbar-help d-none d-sm-flex align-items-center gap-2 text-decoration-none">
                <span class="icono" style="--icono: url('{{ asset('imagenes/icon-help.svg') }}')"></span>
                Ayuda
            </a>
            <span class="topbar-divider d-none d-sm-block"></span>
            @include('partials.menuUsuario')
        </div>
    </header>

    <div class="admin-shell d-flex">
        <aside class="sidebar d-flex flex-column flex-shrink-0">
            <p class="sidebar-label fw-semibold text-uppercase mb-2 sidebar-extra">Principal</p>
            <nav class="d-flex flex-column gap-1" aria-label="Menú principal">
                @foreach ($opciones as $opcion)
                    @continue($opcion['permiso'] !== null && !$trabajador->puede($opcion['permiso']))

                    @if ($opcion['ruta'] === null)
                        <span class="sidebar-link is-pending d-flex align-items-center gap-3" title="Próximamente">
                            <span class="icono" style="--icono: url('{{ asset('imagenes/' . $opcion['icono']) }}')"></span>
                            <span class="flex-grow-1">{{ $opcion['texto'] }}</span>
                            <span class="sidebar-pronto">Pronto</span>
                        </span>
                    @else
                        <a href="{{ route($opcion['ruta']) }}"
                           class="sidebar-link d-flex align-items-center gap-3 {{ $menuActivo === $opcion['clave'] ? 'is-active' : '' }}"
                           @if ($menuActivo === $opcion['clave']) aria-current="page" @endif>
                            <span class="icono" style="--icono: url('{{ asset('imagenes/' . $opcion['icono']) }}')"></span>
                            <span class="flex-grow-1">{{ $opcion['texto'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="mt-auto d-flex flex-column gap-2 sidebar-extra">
                <div class="sidebar-branch">
                    <div class="fw-semibold text-dark">Sucursal principal</div>
                    <div class="sidebar-branch-status d-flex align-items-center gap-2 mt-1">
                        <span class="status-dot"></span>
                        Sistema actualizado
                    </div>
                </div>
                <p class="sidebar-footer m-0 px-1">{{ config('app.name') }} · Panel de {{ mb_strtolower($trabajador->rolTexto()) }}</p>
            </div>
        </aside>

        <main class="admin-main flex-grow-1">
            @yield('pagina')
        </main>
    </div>
@endsection
