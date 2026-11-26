{{--
    Pantallas SIN menú lateral: cajero, vendedor y almacenista (decisión del 20/11/2026).
    Sólo barra superior: logo, Ayuda, el rol y el menú del usuario.

    Cada pantalla la usa así:
        @extends('layouts.barraSuperior')
        @section('titulo', 'Inventario')
        @section('navegacion') @include('cajero.menu') @endsection   ← opcional: los enlaces del rol
        @section('pagina') ...contenido... @endsection
--}}
@extends('layouts.base')

@section('clase-body', 'admin-body')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
@endpush

@section('contenido')
    @php
        $trabajador = auth()->user();
    @endphp

    <header class="topbar d-flex align-items-center justify-content-between">
        <a href="{{ route($trabajador->rutaInicio()) }}" class="d-flex align-items-center gap-3 text-decoration-none">
            <span class="topbar-logo d-flex align-items-center justify-content-center">
                <img src="{{ \App\Support\Tienda::logo() }}" width="20" height="20" alt="" class="logo-tienda">
            </span>
            <span class="topbar-brand fw-bold">{{ \App\Support\Tienda::nombre() }}</span>
        </a>

        <div class="d-flex align-items-center gap-3">
            @hasSection('navegacion')
                <nav class="topbar-nav d-none d-lg-flex align-items-center gap-1" aria-label="Menú">
                    @yield('navegacion')
                </nav>
            @endif
            <a href="#" class="topbar-help d-none d-sm-flex align-items-center gap-2 text-decoration-none">
                <span class="icono" style="--icono: url('{{ asset('imagenes/icon-help.svg') }}')"></span>
                Ayuda
            </a>
            <span class="rol-chip d-none d-sm-inline">{{ $trabajador->rolTexto() }}</span>
            <span class="topbar-divider d-none d-sm-block"></span>
            @include('partials.menuUsuario')
        </div>
    </header>

    @hasSection('navegacion')
        {{-- En celular, los mismos enlaces van debajo de la barra --}}
        <nav class="topbar-nav-movil d-flex d-lg-none" aria-label="Menú">
            @yield('navegacion')
        </nav>
    @endif

    <main class="contenido-rol">
        @yield('pagina')
    </main>
@endsection
