{{--
    Enlaces del cajero en la barra superior (Figma: "Pedidos", "Hacer corte de caja"...).
    Las siguientes (corte de caja, consultar productos, ventas) se agregan aquí.
    Cada pantalla dice cuál está activa: @section('navegacion') @include('cajero.menu', ['activo' => 'pedidos']) @endsection
--}}
@php
    $activo = $activo ?? '';
    $enlaces = [
        ['clave' => 'venta', 'texto' => 'Nueva venta', 'ruta' => route('cajero.inicio')],
        ['clave' => 'pedidos', 'texto' => 'Pedidos', 'ruta' => route('cajero.pedidos.index')],
    ];
@endphp
@foreach ($enlaces as $enlace)
    <a href="{{ $enlace['ruta'] }}" class="topbar-nav-enlace {{ $activo === $enlace['clave'] ? 'is-active' : '' }}"
       @if ($activo === $enlace['clave']) aria-current="page" @endif>{{ $enlace['texto'] }}</a>
@endforeach
