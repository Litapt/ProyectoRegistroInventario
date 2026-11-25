{{--
    Título y pestañas de Alertas (Stock · Incidencias) con sus contadores.
    Uso: @include('administrador.alertas.encabezado', ['activa' => 'stock'])
--}}
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
        <h1 class="page-heading m-0">Alertas</h1>
        <p class="page-description m-0 mt-1">Monitorea existencias y da seguimiento a las incidencias.</p>
    </div>
    <span class="rol-chip">{{ $totalStock + $totalIncidencias }} pendientes</span>
</div>

<nav class="tabs-app d-flex flex-wrap gap-4 mb-4" aria-label="Secciones de alertas">
    <a href="{{ route('administrador.alertas.index') }}" class="tab-app {{ $activa === 'stock' ? 'is-active' : '' }}" @if ($activa === 'stock') aria-current="page" @endif>
        Stock <span class="contador-gris">{{ $totalStock }}</span>
    </a>
    <a href="{{ route('administrador.alertas.incidencias') }}" class="tab-app {{ $activa === 'incidencias' ? 'is-active' : '' }}" @if ($activa === 'incidencias') aria-current="page" @endif>
        Incidencias <span class="contador-gris">{{ $totalIncidencias }}</span>
    </a>
</nav>
