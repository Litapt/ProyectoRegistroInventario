{{--
    Bienvenida de cada rol mientras se programan sus módulos.
    @include('partials.bienvenida', ['subtitulo' => '...', 'pendientes' => ['...', '...']])
--}}
@php
    $trabajador = auth()->user();
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <p class="sidebar-label fw-semibold text-uppercase mb-2">Menú principal</p>
        <h1 class="page-heading m-0">Bienvenido, {{ $trabajador->nombres }}</h1>
        <p class="page-description m-0 mt-1">{{ $subtitulo }}</p>
    </div>
    <p class="fecha-hoy m-0">{{ ucfirst(now()->translatedFormat('l, j \d\e F \d\e Y')) }}</p>
</div>

<section class="panel">
    <div class="panel-body d-flex flex-column gap-3">
        <div>
            <h2 class="panel-title m-0">Tu pantalla está en construcción</h2>
            <p class="panel-text m-0 mt-1">Ya puedes iniciar sesión. Estas son las opciones que vas a tener aquí:</p>
        </div>
        <ul class="lista-pronto">
            @foreach ($pendientes as $pendiente)
                <li>{{ $pendiente }}</li>
            @endforeach
        </ul>
    </div>
</section>
