{{--
    Pestañas de Ventas: Registrar · Consultar · Cotizaciones · Solicitudes (con cuántas hay pendientes).
    Uso: @include('administrador.ventas.pestanas', ['activa' => 'consultar'])
--}}
@php
    $yo = auth()->user();
    $pendientes = $yo->puede('solicitudes.autorizar')
        ? \App\Models\Solicitud::pendientes()->where('tipoSolicitud', \App\Models\Solicitud::EDITAR_VENTA)->count()
        : 0;
    $pestanas = [
        ['clave' => 'registrar', 'texto' => 'Registrar venta', 'ruta' => 'ventas.index', 'permiso' => 'ventas.registrar'],
        ['clave' => 'consultar', 'texto' => 'Consultar ventas', 'ruta' => 'ventas.consultar', 'permiso' => 'ventas.consultar'],
<<<<<<< HEAD
        ['clave' => 'cotizaciones', 'texto' => 'Cotizaciones', 'ruta' => 'ventas.cotizaciones', 'permiso' => 'pedidos.gestionar'],
=======
>>>>>>> 4362f1a976551bded5b9cae63478669ab580650f
        ['clave' => 'solicitudes', 'texto' => 'Solicitudes', 'ruta' => 'ventas.solicitudes', 'permiso' => 'solicitudes.autorizar'],
    ];
@endphp

<nav class="tabs-app d-flex flex-wrap gap-4 mb-4" aria-label="Secciones de ventas">
    @foreach ($pestanas as $pestana)
        @continue(!$yo->puede($pestana['permiso']))
        <a href="{{ \App\Support\Ruta::a($pestana['ruta']) }}" class="tab-app {{ $activa === $pestana['clave'] ? 'is-active' : '' }}"
           @if ($activa === $pestana['clave']) aria-current="page" @endif>
            {{ $pestana['texto'] }}
            @if ($pestana['clave'] === 'solicitudes' && $pendientes > 0)
                <span class="contador-pestana">{{ $pendientes }}</span>
            @endif
        </a>
    @endforeach
</nav>
