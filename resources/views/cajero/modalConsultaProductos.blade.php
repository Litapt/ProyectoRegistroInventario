{{--
    Ventana "Consultar productos" del cajero (Figma: "Cajero - Menú consulta"). Sólo para ver.
    Se abre desde el menú en cualquier pantalla del cajero, sin salir de ella.
    Los resultados los trae javascript/cajero/consultaProductos.js (Cajero\ProductoController@consultar).
--}}
@php
    $categorias = \App\Models\Categoria::activas()->get();
    $marcas = \App\Models\Marca::activas()->get();
    $marcasVehiculo = \App\Models\MarcaVehiculo::activas()->with(['modelos' => fn ($q) => $q->where('activo', true)])->get();
@endphp
<div class="modal fade modal-app" id="modalConsultaProductos" tabindex="-1" aria-labelledby="consultaProductosTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <h2 class="modal-app-title m-0" id="consultaProductosTitulo">Consultar productos</h2>
                        <p class="panel-text fs-6 m-0 mt-1">Busca refacciones y filtra el inventario. <span class="field-help" id="consultaConteo"></span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form class="row g-3 align-items-end" id="formConsultaProductos" data-url="{{ route('cajero.productos.consultar') }}" autocomplete="off">
                    <div class="col-md-8 col-xl-5">
                        <label for="consultaBuscar" class="field-label-app">Buscar producto</label>
                        <input type="search" id="consultaBuscar" name="buscar" maxlength="60" class="form-control input-app"
                               placeholder="Nombre, código, número de parte o código de barras">
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="consultaEstado" class="field-label-app">Estado de stock</label>
                        <select id="consultaEstado" name="estado" class="form-select input-app">
                            <option value="">Todos los estados</option>
                            <option value="{{ \App\Models\Producto::ESTADO_DISPONIBLE }}">Disponible</option>
                            <option value="{{ \App\Models\Producto::ESTADO_BAJO }}">Stock bajo</option>
                            <option value="{{ \App\Models\Producto::ESTADO_AGOTADO }}">Agotado</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-4 d-none d-xl-block"></div>
                    <div class="col-md-4 col-xl-3">
                        <label for="consultaCategoria" class="field-label-app">Categoría</label>
                        <select id="consultaCategoria" name="categoria" class="form-select input-app">
                            <option value="">Todas las categorías</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->idCategoria }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="consultaMarca" class="field-label-app">Marca</label>
                        <select id="consultaMarca" name="marca" class="form-select input-app">
                            <option value="">Todas las marcas</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca->idMarca }}">{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="consultaVehiculo" class="field-label-app">Modelo de vehículo</label>
                        <select id="consultaVehiculo" name="vehiculo" class="form-select input-app">
                            <option value="">Todos los modelos</option>
                            @foreach ($marcasVehiculo as $marcaVehiculo)
                                <optgroup label="{{ $marcaVehiculo->nombre }}">
                                    @foreach ($marcaVehiculo->modelos as $modelo)
                                        <option value="{{ $modelo->idModeloVehiculo }}">{{ $marcaVehiculo->nombre }} {{ $modelo->nombre }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 d-flex flex-wrap align-items-center justify-content-end gap-3">
                        <button type="button" class="btn-text" id="consultaLimpiar">Limpiar filtros</button>
                        <button type="submit" class="btn-app btn-app-primary">Buscar</button>
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
                                <th>Compatibilidad (vehículo)</th>
                                <th>Stock</th>
                                <th class="text-end">Precio</th>
                            </tr>
                        </thead>
                        <tbody id="consultaResultados">
                            <tr><td colspan="7" class="text-center py-5 text-muted">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <span class="field-help" id="consultaMostrando"></span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-app btn-app-outline btn-app-sm" id="consultaAnterior">Anterior</button>
                        <span class="field-help align-self-center" id="consultaPagina"></span>
                        <button type="button" class="btn-app btn-app-outline btn-app-sm" id="consultaSiguiente">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
