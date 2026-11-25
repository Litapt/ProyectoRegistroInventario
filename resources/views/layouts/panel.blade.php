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
        // "administrador" o "gerente": cada rol usa las rutas de su propia carpeta
        $carpeta = strtok($rutaInicio, '.');
        // Nombre completo de la ruta si ya está programada para este rol; si no, null ("Pronto")
        $rutaDe = fn (string $nombre) => \Illuminate\Support\Facades\Route::has("{$carpeta}.{$nombre}") ? "{$carpeta}.{$nombre}" : null;
        $opciones = [
            ['clave' => 'inicio', 'texto' => 'Inicio', 'icono' => 'icon-home.svg', 'permiso' => null, 'ruta' => $rutaInicio],
            ['clave' => 'ventas', 'texto' => 'Ventas', 'icono' => 'icon-cart-2.svg', 'permiso' => 'ventas.registrar', 'ruta' => $rutaDe('ventas.index')],
            ['clave' => 'clientes', 'texto' => 'Clientes', 'icono' => 'icon-users.svg', 'permiso' => 'clientes.gestionar', 'ruta' => $rutaDe('clientes.index')],
            ['clave' => 'productos', 'texto' => 'Productos', 'icono' => 'icon-box.svg', 'permiso' => 'productos.consultar', 'ruta' => $rutaDe('productos.index')],
            ['clave' => 'usuarios', 'texto' => 'Usuarios', 'icono' => 'icon-user-2.svg', 'permiso' => 'usuarios.gestionar', 'ruta' => $rutaDe('usuarios.index')],
            ['clave' => 'alertas', 'texto' => 'Alertas', 'icono' => 'icon-bell-2.svg', 'permiso' => 'solicitudes.autorizar', 'ruta' => $rutaDe('alertas.index')],
            ['clave' => 'configuracion', 'texto' => 'Configuración', 'icono' => 'icon-settings-2.svg', 'permiso' => 'configuracion.editar', 'ruta' => $rutaDe('configuracion.index')],
        ];
        // Número rojo junto a "Alertas": productos por reponer + incidencias abiertas
        $pendientesAlertas = $trabajador->puede('solicitudes.autorizar') && $rutaDe('alertas.index')
            ? \App\Models\Producto::requierenAtencion()->count()
                + \App\Models\Solicitud::where('tipoSolicitud', 'INCIDENCIA')->whereIn('estado', ['PENDIENTE', 'EN_REVISION'])->count()
            : 0;
    @endphp

    <header class="topbar d-flex align-items-center justify-content-between">
        <a href="{{ route($rutaInicio) }}" class="d-flex align-items-center gap-3 text-decoration-none">
            <span class="topbar-logo d-flex align-items-center justify-content-center">
                <img src="{{ \App\Support\Tienda::logo() }}" width="20" height="20" alt="" class="logo-tienda">
            </span>
            <span class="topbar-brand fw-bold">{{ \App\Support\Tienda::nombre() }}</span>
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
                            @if ($opcion['clave'] === 'alertas' && $pendientesAlertas > 0)
                                <span class="sidebar-contador">{{ $pendientesAlertas > 99 ? '99+' : $pendientesAlertas }}</span>
                            @endif
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
                <p class="sidebar-footer m-0 px-1">{{ \App\Support\Tienda::nombre() }} · Panel de {{ mb_strtolower($trabajador->rolTexto()) }}</p>
            </div>
        </aside>

        <main class="admin-main flex-grow-1">
            @yield('pagina')
        </main>
    </div>
@endsection
