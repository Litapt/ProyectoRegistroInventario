{{--
    Ventana "Registrar producto" / "Editar producto" (Figma: Modal · Registrar producto y Modal · Editar producto).
    Es UNA sola ventana: productos.js la cambia a "Editar" y la llena con los datos del producto.

    Si el servidor regresó errores, la ventana se vuelve a abrir sola con lo que se había escrito
    (session('modalProducto') = 'registrar' o 'editar').
--}}
@php
    $modo = session('modalProducto');                  // null, 'registrar' o 'editar'
    $editando = $modo === 'editar' && $productoReabierto;
    $vehiculosEscritos = old('vehiculos', []);
    // id del modelo → "Nissan Versa" (para volver a dibujar los vehículos si hubo errores)
    $nombresModelos = [];
    foreach ($marcasVehiculo as $marcaVehiculo) {
        foreach ($marcaVehiculo->modelos as $modeloVehiculo) {
            $nombresModelos[$modeloVehiculo->idModeloVehiculo] = $marcaVehiculo->nombre . ' ' . $modeloVehiculo->nombre;
        }
    }
@endphp

<div class="modal fade modal-app" id="modalProducto" tabindex="-1" aria-labelledby="modalProductoTitulo" aria-hidden="true"
     data-reabrir="{{ $modo && ($modo === 'registrar' || $editando) ? '1' : '' }}"
     data-url-registrar="{{ route('administrador.productos.store') }}">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form method="post" enctype="multipart/form-data" class="modal-content" id="formProducto" novalidate
              action="{{ $editando ? route('administrador.productos.update', $productoReabierto) : route('administrador.productos.store') }}">
            @csrf
            <input type="hidden" name="_method" value="{{ $editando ? 'PUT' : 'POST' }}" id="productoMetodo">

            <div class="modal-body d-flex flex-column gap-4">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0" id="modalProductoTitulo">{{ $editando ? 'Editar producto' : 'Registrar producto' }}</h2>
                        <p class="panel-text fs-6 m-0 mt-1" id="modalProductoSubtitulo">
                            {{ $editando ? 'Actualiza los datos y la compatibilidad de la refacción.' : 'Completa los datos para agregar una refacción al inventario.' }}
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                @if ($modo)
                    @include('partials.errores')
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="productoCodigo" class="field-label-app">Código / SKU</label>
                        <input type="text" id="productoCodigo" name="codigo" value="{{ old('codigo') }}" maxlength="30" required
                               class="form-control input-app mayusculas @error('codigo') is-invalid @enderror" placeholder="Ej. FR-BOS-001">
                    </div>
                    <div class="col-md-4">
                        <label for="productoNombre" class="field-label-app">Nombre</label>
                        <input type="text" id="productoNombre" name="nombre" value="{{ old('nombre') }}" maxlength="150" required
                               class="form-control input-app @error('nombre') is-invalid @enderror" placeholder="Nombre del producto">
                    </div>
                    <div class="col-md-4">
                        <label for="productoCategoria" class="field-label-app">Categoría</label>
                        <select id="productoCategoria" name="categoria" required class="form-select input-app @error('categoria') is-invalid @enderror">
                            <option value="">Selecciona una categoría</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->idCategoria }}" @selected((string) old('categoria') === (string) $categoria->idCategoria)>{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="productoMarca" class="field-label-app">Marca</label>
                        <select id="productoMarca" name="marca" required class="form-select input-app @error('marca') is-invalid @enderror">
                            <option value="">Selecciona una marca</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca->idMarca }}" @selected((string) old('marca') === (string) $marca->idMarca)>{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="productoPrecioCompra" class="field-label-app">Precio de compra</label>
                        <div class="input-group">
                            <span class="input-group-text input-prefix">MXN</span>
                            <input type="number" id="productoPrecioCompra" name="precioCompra" value="{{ old('precioCompra') }}" min="0" max="9999999.99" step="0.01" required
                                   class="form-control input-app @error('precioCompra') is-invalid @enderror" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="productoPrecioVenta" class="field-label-app">Precio de venta</label>
                        <div class="input-group">
                            <span class="input-group-text input-prefix">MXN</span>
                            <input type="number" id="productoPrecioVenta" name="precioVenta" value="{{ old('precioVenta') }}" min="0" max="9999999.99" step="0.01" required
                                   class="form-control input-app @error('precioVenta') is-invalid @enderror" placeholder="0.00">
                        </div>
                    </div>

                    {{-- Al registrar se escribe el stock inicial; al editar sólo se muestra (cambia con "Ajuste stock") --}}
                    <div class="col-md-4">
                        <label for="productoStock" class="field-label-app" id="productoStockEtiqueta">{{ $editando ? 'Stock actual' : 'Stock inicial' }}</label>
                        <div class="input-group">
                            <input type="number" id="productoStock" name="stockInicial" min="0" max="999999" step="1"
                                   value="{{ $editando ? $productoReabierto->existencia : old('stockInicial') }}"
                                   @disabled($editando)
                                   class="form-control input-app @error('stockInicial') is-invalid @enderror" placeholder="0">
                            <span class="input-group-text input-suffix">piezas</span>
                        </div>
                        <p class="field-help m-0 mt-1 {{ $editando ? '' : 'd-none' }}" id="productoStockAyuda">Para cambiarlo usa «Ajuste stock».</p>
                    </div>
                    <div class="col-md-4">
                        <label for="productoStockMinimo" class="field-label-app">Stock mínimo</label>
                        <div class="input-group">
                            <input type="number" id="productoStockMinimo" name="stockMinimo" value="{{ old('stockMinimo') }}" min="0" max="999999" step="1"
                                   class="form-control input-app @error('stockMinimo') is-invalid @enderror"
                                   placeholder="{{ \App\Models\Producto::stockMinimoPorDefecto() }}">
                            <span class="input-group-text input-suffix">piezas</span>
                        </div>
                        <p class="field-help m-0 mt-1">Vacío = {{ \App\Models\Producto::stockMinimoPorDefecto() }} (el de Configuración).</p>
                    </div>
                    <div class="col-md-4">
                        <label for="productoUbicacion" class="field-label-app">Ubicación en almacén</label>
                        <input type="text" id="productoUbicacion" name="ubicacion" value="{{ old('ubicacion') }}" maxlength="40"
                               class="form-control input-app mayusculas @error('ubicacion') is-invalid @enderror" placeholder="Pasillo, estante y nivel (ej. A-01-03)">
                    </div>

                    {{-- Vehículos compatibles: cada uno se agrega como "chip" con sus campos ocultos --}}
                    <div class="col-lg-8">
                        <span class="field-label-app d-block">Vehículos compatibles</span>
                        <div class="row g-2 align-items-end" id="nuevoVehiculo">
                            <div class="col-6 col-md-3">
                                <label for="vehiculoMarca" class="field-help d-block mb-1">Marca</label>
                                <select id="vehiculoMarca" class="form-select input-app">
                                    <option value="">Elige la marca</option>
                                    @foreach ($marcasVehiculo as $marcaVehiculo)
                                        <option value="{{ $marcaVehiculo->idMarcaVehiculo }}">{{ $marcaVehiculo->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="vehiculoModelo" class="field-help d-block mb-1">Modelo</label>
                                {{-- productos.js lo llena con los modelos de la marca elegida --}}
                                <select id="vehiculoModelo" class="form-select input-app" disabled>
                                    <option value="">Primero la marca</option>
                                </select>
                            </div>
                            <div class="col-4 col-md-2">
                                <label for="vehiculoDesde" class="field-help d-block mb-1">Año desde</label>
                                <input type="number" id="vehiculoDesde" min="1950" max="{{ now()->year + 1 }}" class="form-control input-app" placeholder="2012">
                            </div>
                            <div class="col-4 col-md-2">
                                <label for="vehiculoHasta" class="field-help d-block mb-1">Año hasta</label>
                                <input type="number" id="vehiculoHasta" min="1950" max="{{ now()->year + 1 }}" class="form-control input-app" placeholder="2019">
                            </div>
                            <div class="col-4 col-md-2">
                                <button type="button" class="btn-app btn-app-outline w-100" id="botonAgregarVehiculo">Agregar</button>
                            </div>
                        </div>
                        <p class="field-help text-danger m-0 mt-1 d-none" id="vehiculoError"></p>

                        {{-- Los modelos de cada marca, para el JavaScript: {"3": [{"id": 12, "nombre": "Versa"}, ...]} --}}
                        <script type="application/json" id="modelosPorMarca">@json($marcasVehiculo->mapWithKeys(fn ($m) => [$m->idMarcaVehiculo => $m->modelos->map(fn ($x) => ['id' => $x->idModeloVehiculo, 'nombre' => $x->nombre])->values()]))</script>

                        <div class="d-flex flex-wrap gap-2 mt-3" id="listaVehiculos">
                            @foreach ($vehiculosEscritos as $i => $vehiculo)
                                @if (is_array($vehiculo) && isset($nombresModelos[(int) ($vehiculo['modelo'] ?? 0)]))
                                    <span class="chip-vehiculo">
                                        {{ $nombresModelos[(int) $vehiculo['modelo']] }} · {{ $vehiculo['desde'] ?? '' }}{{ !empty($vehiculo['hasta']) ? '–' . $vehiculo['hasta'] : '' }}
                                        <input type="hidden" name="vehiculos[{{ $i }}][modelo]" value="{{ $vehiculo['modelo'] }}">
                                        <input type="hidden" name="vehiculos[{{ $i }}][desde]" value="{{ $vehiculo['desde'] ?? '' }}">
                                        <input type="hidden" name="vehiculos[{{ $i }}][hasta]" value="{{ $vehiculo['hasta'] ?? '' }}">
                                        <button type="button" class="chip-quitar" aria-label="Quitar vehículo">&times;</button>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                        <p class="field-help m-0 mt-2" id="vehiculosVacio">Sin vehículos seleccionados: el producto se mostrará como «Universal».</p>
                    </div>

                    {{-- Imagen: el <label> hace que toda la caja abra el selector de archivos --}}
                    <div class="col-lg-4">
                        <span class="field-label-app d-block">Imagen del producto</span>
                        <label for="productoImagen" class="zona-imagen d-flex flex-column align-items-center justify-content-center text-center @error('imagen') is-invalid @enderror">
                            @php
                                $imagenActual = $editando ? $productoReabierto->urlImagen() : null;
                            @endphp
                            <img src="{{ $imagenActual ?? '' }}" alt="" id="imagenVista" class="imagen-vista {{ $imagenActual ? '' : 'd-none' }}">
                            <span id="imagenIndicaciones" class="{{ $imagenActual ? 'd-none' : '' }}">
                                <span class="icono icono-lg d-block mx-auto mb-2" style="--icono: url('{{ asset('imagenes/icon-download.svg') }}'); transform: rotate(180deg)"></span>
                                <span class="d-block text-muted small">Arrastra una imagen aquí</span>
                                <span class="d-block btn-text">o selecciona una imagen</span>
                            </span>
                        </label>
                        <input type="file" id="productoImagen" name="imagen" accept="image/png,image/jpeg" class="visually-hidden">
                        <div class="d-flex justify-content-between mt-2">
                            <span class="field-help" id="imagenAyuda">PNG o JPG · Hasta 5 MB</span>
                            <label for="productoImagen" class="btn-text {{ $imagenActual ? '' : 'd-none' }}" id="imagenCambiar">Cambiar imagen</label>
                        </div>
                        @if ($modo && $errors->any())
                            <p class="field-help m-0 mt-1">Si habías elegido una imagen, vuelve a seleccionarla.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-between gap-3">
                <span class="field-help">Precios en pesos mexicanos (MXN).</span>
                <div class="d-flex gap-3">
                    <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-app btn-app-primary" id="productoGuardar">{{ $editando ? 'Guardar cambios' : 'Guardar' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
