{{--
    Enlaces del cajero en la barra superior (Figma: "Pedidos", "Hacer corte de caja"...).
    Las siguientes (corte de caja, consultar productos, ventas) se agregan aquí.
    Cada pantalla dice cuál está activa: @section('navegacion') @include('cajero.menu', ['activo' => 'pedidos']) @endsection
--}}
@php
    $activo = $activo ?? '';
    $enlaces = [
        ['clave' => 'venta', 'texto' => 'Nueva venta', 'ruta' => route('cajero.inicio')],
        ['clave' => 'ventas', 'texto' => 'Ventas', 'ruta' => route('cajero.ventas.consultar')],
        ['clave' => 'cotizaciones', 'texto' => 'Cotizaciones', 'ruta' => route('cajero.ventas.cotizaciones')],
        ['clave' => 'pedidos', 'texto' => 'Pedidos', 'ruta' => route('cajero.pedidos.index')],
        ['clave' => 'corte', 'texto' => 'Hacer corte de caja', 'ruta' => route('cajero.corte.index')],
    ];
@endphp
@foreach ($enlaces as $enlace)
    <a href="{{ $enlace['ruta'] }}" class="topbar-nav-enlace {{ $activo === $enlace['clave'] ? 'is-active' : '' }}"
       @if ($activo === $enlace['clave']) aria-current="page" @endif>{{ $enlace['texto'] }}</a>
@endforeach
{{-- "Consultar productos" abre una ventana: no sale de la pantalla en la que está --}}
<button type="button" class="topbar-nav-enlace border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#modalConsultaProductos">
    Consultar productos
</button>

{{-- La ventana y su javascript, una sola vez aunque el menú salga dos veces (computadora y celular) --}}
@once
    @push('modales')
        @include('cajero.modalConsultaProductos')
    @endpush
    @push('scripts')
        <script src="{{ asset('javascript/cajero/consultaProductos.js') }}"></script>
    @endpush
@endonce
