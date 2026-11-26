{{--
    Productos del administrador (Figma Page 2: "Administrador · Productos").
    Todo lo manda Administrador\ProductoController@index.
    Las ventanas están en modalProducto.blade.php y modalDesactivar.blade.php
--}}
@extends('layouts.panel')

@section('titulo', 'Productos')
@section('menu', 'productos')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/productos.css') }}">
@endpush

@section('pagina')
    @php
        $precio = fn ($cantidad) => \App\Models\Producto::formatoPrecio($cantidad);
        $insignias = [
            \App\Models\Producto::ESTADO_DISPONIBLE => ['badge-ok', 'Disponible'],
            \App\Models\Producto::ESTADO_BAJO => ['badge-warning', 'Bajo'],
            \App\Models\Producto::ESTADO_AGOTADO => ['badge-danger', 'Agotado'],
        ];
        $hayFiltros = $filtros['buscar'] !== '' || $filtros['categoria'] || $filtros['marca'] || $filtros['vehiculo'] || $filtros['estado'] !== '';
    @endphp

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-heading m-0">Productos</h1>
            <p class="page-description m-0 mt-1">Consulta y administra el inventario de tu refaccionaria.</p>
        </div>
        @if (auth()->user()->puede('productos.gestionar'))
            <button type="button" class="btn-app btn-app-primary" data-bs-toggle="modal" data-bs-target="#modalProducto" data-modo="registrar">
                Registrar nuevo
            </button>
        @endif
    </div>

    <section class="panel">
        {{-- Menú consulta: es un formulario GET, así los filtros quedan en la dirección --}}
        <form method="get" action="{{ \App\Support\Ruta::a('productos.index') }}" class="panel-body d-flex flex-column gap-3" id="formFiltros">
            @if ($verDesactivados)
                <input type="hidden" name="ver" value="desactivados">
            @endif

            <div>
                <h2 class="panel-title m-0">{{ $verDesactivados ? 'Productos desactivados' : 'Menú consulta' }}</h2>
                <p class="panel-text m-0 mt-1">
                    {{ $verDesactivados ? 'Estos productos no aparecen en ventas ni en el inventario. Puedes reactivarlos.' : 'Busca refacciones y filtra tu inventario.' }}
                </p>
            </div>

            <div>
                <label for="buscar" class="field-label-app">Buscar producto</label>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <input type="search" id="buscar" name="buscar" value="{{ $filtros['buscar'] }}" maxlength="100"
                           class="form-control input-app filtro-buscar" placeholder="Nombre o código de refacción">
                    <button type="submit" class="btn-app btn-app-primary">Buscar</button>
                    @if ($hayFiltros)
                        <a href="{{ \App\Support\Ruta::a('productos.index', $verDesactivados ? ['ver' => 'desactivados'] : []) }}" class="btn-text">Limpiar filtros</a>
                    @endif
                    <span class="panel-text ms-auto">
                        {{ number_format($totalActivos) }} {{ $totalActivos === 1 ? 'producto' : 'productos' }} · Precios en MXN
                        ·
                        @if ($verDesactivados)
                            <a href="{{ \App\Support\Ruta::a('productos.index') }}" class="btn-text">Ver activos</a>
                        @else
                            <a href="{{ \App\Support\Ruta::a('productos.index', ['ver' => 'desactivados']) }}" class="btn-text">Ver desactivados ({{ $totalDesactivados }})</a>
                        @endif
                    </span>
                </div>
            </div>

            {{-- Al cambiar un filtro se busca solo (productos.js) --}}
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <label for="filtroCategoria" class="field-label-app">Categoría</label>
                    <select id="filtroCategoria" name="categoria" class="form-select input-app filtro-auto">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->idCategoria }}" @selected($filtros['categoria'] === $categoria->idCategoria)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <label for="filtroMarca" class="field-label-app">Marca</label>
                    <select id="filtroMarca" name="marca" class="form-select input-app filtro-auto">
                        <option value="">Todas las marcas</option>
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca->idMarca }}" @selected($filtros['marca'] === $marca->idMarca)>{{ $marca->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <label for="filtroVehiculo" class="field-label-app">Modelo de vehículo</label>
                    <select id="filtroVehiculo" name="vehiculo" class="form-select input-app filtro-auto">
                        <option value="">Todos los modelos</option>
                        {{-- Agrupados por marca: <optgroup label="Nissan"> Versa, March... --}}
                        @foreach ($marcasVehiculo as $marcaVehiculo)
                            @if ($marcaVehiculo->modelos->isNotEmpty())
                                <optgroup label="{{ $marcaVehiculo->nombre }}">
                                    @foreach ($marcaVehiculo->modelos as $modeloVehiculo)
                                        <option value="{{ $modeloVehiculo->idModeloVehiculo }}" @selected($filtros['vehiculo'] === $modeloVehiculo->idModeloVehiculo)>{{ $marcaVehiculo->nombre }} {{ $modeloVehiculo->nombre }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <label for="filtroEstado" class="field-label-app">Estado de stock</label>
                    <select id="filtroEstado" name="estado" class="form-select input-app filtro-auto">
                        <option value="">Todos los estados</option>
                        <option value="disponible" @selected($filtros['estado'] === 'disponible')>Disponible</option>
                        <option value="bajo" @selected($filtros['estado'] === 'bajo')>Stock bajo</option>
                        <option value="agotado" @selected($filtros['estado'] === 'agotado')>Agotado</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th>Compatibilidad<br>(vehículo)</th>
                        <th>Stock</th>
                        <th>Precio</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        @php
                            [$claseInsignia, $textoInsignia] = $insignias[$producto->estadoStock()];
                            $primero = $producto->compatibilidades->first();
                            $mas = $producto->compatibilidades->count() - 1;
                        @endphp
                        <tr>
                            <td class="text-muted small text-nowrap">{{ $producto->codigoInterno }}</td>
                            <td class="cell-main">{{ $producto->nombre }}</td>
                            <td class="text-muted">{{ $producto->categoria?->nombre }}</td>
                            <td class="text-muted">{{ $producto->marca?->nombre }}</td>
                            <td>
                                @if ($primero)
                                    <div class="small text-dark">{{ $primero->vehiculo() }}</div>
                                    <div class="cell-sub">{{ $primero->anios() }}{{ $mas > 0 ? " · y {$mas} más" : '' }}</div>
                                @else
                                    <div class="small text-dark">Universal</div>
                                    <div class="cell-sub">Multimarca</div>
                                @endif
                            </td>
                            <td><span class="badge-estado {{ $claseInsignia }}">{{ $producto->existencia }} · {{ $textoInsignia }}</span></td>
                            <td class="text-dark">{{ $precio($producto->precioVentaActual) }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    @if ($verDesactivados)
                                        <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalDesactivar" data-accion="reactivar"
                                                data-url="{{ \App\Support\Ruta::a('productos.reactivar', $producto) }}"
                                                data-nombre="{{ $producto->nombre }}"
                                                data-detalle="{{ $producto->codigoInterno }} · {{ $producto->marca?->nombre }}">
                                            Reactivar
                                        </button>
                                    @else
                                        @if (auth()->user()->puede('inventario.ajustar'))
                                            <a href="{{ \App\Support\Ruta::a('productos.ajuste', $producto) }}" class="btn-app btn-app-soft btn-app-sm">Ajuste stock</a>
                                        @endif
                                        @if (auth()->user()->puede('productos.gestionar'))
                                            <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalProducto" data-modo="editar"
                                                    data-url="{{ \App\Support\Ruta::a('productos.update', $producto) }}"
                                                    data-producto="{{ json_encode($producto->datosParaEditar()) }}">
                                                Editar
                                            </button>
                                        @endif
                                        @if (auth()->user()->puede('productos.desactivar'))
                                            <button type="button" class="btn-app btn-app-danger btn-app-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalDesactivar" data-accion="desactivar"
                                                    data-url="{{ \App\Support\Ruta::a('productos.desactivar', $producto) }}"
                                                    data-nombre="{{ $producto->nombre }}"
                                                    data-detalle="{{ $producto->codigoInterno }} · {{ $producto->marca?->nombre }}">
                                                Eliminar
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                @if ($hayFiltros)
                                    Ningún producto coincide con la búsqueda.
                                @elseif ($verDesactivados)
                                    No hay productos desactivados.
                                @else
                                    Todavía no hay productos. Usa «Registrar nuevo» para agregar el primero.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $productos, 'nombre' => 'productos'])
    </section>
@endsection

@push('modales')
    @include('administrador.productos.modalProducto')
    @include('administrador.productos.modalDesactivar')
@endpush

@push('scripts')
    <script src="{{ asset('javascript/administrador/productos.js') }}"></script>
@endpush
