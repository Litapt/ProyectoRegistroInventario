{{--
    Registrar ajuste de stock de un producto (Figma: "Registro de ajuste de stock"
    y la ventana "Ajuste de stock · Confirmación"). Lo manda Administrador\AjusteStockController@create.
--}}
@extends('layouts.panel')

@section('titulo', 'Ajuste de stock')
@section('menu', 'productos')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ajusteStock.css') }}">
@endpush

@section('pagina')
    @php
        $tipoElegido = old('tipo', \App\Models\AjusteInventario::ENTRADA);
        $precio = fn ($cantidad) => \App\Models\Producto::formatoPrecio($cantidad);
        $vehiculos = $producto->compatibilidades->map(fn ($c) => $c->vehiculo() . ' ' . $c->anios())->implode(', ');
        $datos = [
            ['Nombre' => $producto->nombre, 'Código / SKU' => $producto->codigoInterno],
            ['Marca' => $producto->marca?->nombre, 'Categoría' => $producto->categoria?->nombre],
            ['Precio de compra' => $precio($producto->precioCompraActual), 'Precio de venta' => $precio($producto->precioVentaActual)],
            ['Ubicación' => $producto->ubicacion?->texto() ?: 'Sin asignar', 'Vehículos' => $vehiculos !== '' ? $vehiculos : 'Universal'],
        ];
    @endphp

    <a href="{{ route('administrador.productos.index') }}" class="btn-text d-inline-flex align-items-center gap-2 mb-3">
        <img src="{{ asset('imagenes/icon-adjust-back.svg') }}" width="18" height="18" alt="">
        Volver a Productos
    </a>

    <div class="mb-4">
        <h1 class="page-heading m-0">Registrar ajuste de stock</h1>
        <p class="page-description m-0 mt-1">Selecciona una entrada o un egreso, ingresa la cantidad y revisa el stock resultante antes de confirmar.</p>
    </div>

    @include('partials.errores')

    <form id="formAjuste" method="post" action="{{ route('administrador.productos.ajuste.store', $producto) }}" class="d-flex flex-column gap-4"
          data-stock="{{ $producto->existencia }}" data-minimo="{{ $producto->stockMinimoEfectivo() }}"
          data-producto="{{ $producto->nombre }}" data-detalle="{{ $producto->codigoInterno }} · {{ $producto->marca?->nombre }}">
        @csrf

        {{-- 1) Movimiento de stock --}}
        <section class="panel">
            <div class="adjust-section-header d-flex align-items-center gap-3 p-4">
                <span class="adjust-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
                    <img src="{{ asset('imagenes/icon-movement.svg') }}" width="20" height="20" alt="">
                </span>
                <div>
                    <h2 class="panel-title m-0">Movimiento de stock</h2>
                    <p class="panel-text m-0 mt-1">Elige el tipo de ajuste. La cantidad se sumará o se restará del stock actual.</p>
                </div>
            </div>

            <div class="d-flex flex-column gap-4 p-4">
                <div class="adjust-columns">
                    <div class="adjust-capture d-flex flex-column gap-3">
                        <fieldset class="m-0 p-0 border-0">
                            <legend class="adjust-label fw-medium">Tipo de movimiento</legend>
                            <div class="move-options">
                                <label class="move-option d-flex align-items-center gap-12" data-tipo="ENTRADA">
                                    <input type="radio" name="tipo" value="ENTRADA" class="visually-hidden" @checked($tipoElegido === 'ENTRADA')>
                                    <img src="{{ asset('imagenes/icon-plus-option.svg') }}" width="18" height="18" alt="" class="option-icon-idle flex-shrink-0">
                                    <img src="{{ asset('imagenes/icon-plus-option-active.svg') }}" width="18" height="18" alt="" class="option-icon-active flex-shrink-0">
                                    <span class="d-flex flex-column gap-1 flex-grow-1">
                                        <span class="option-title fw-semibold">Entrada · Aumentar stock</span>
                                        <span class="option-text">Agregar unidades al almacén</span>
                                    </span>
                                    <span class="option-radio flex-shrink-0"></span>
                                </label>
                                <label class="move-option d-flex align-items-center gap-12" data-tipo="SALIDA">
                                    <input type="radio" name="tipo" value="SALIDA" class="visually-hidden" @checked($tipoElegido === 'SALIDA')>
                                    <img src="{{ asset('imagenes/icon-minus-option-muted.svg') }}" width="18" height="18" alt="" class="option-icon-idle flex-shrink-0">
                                    <img src="{{ asset('imagenes/icon-minus-option.svg') }}" width="18" height="18" alt="" class="option-icon-active flex-shrink-0">
                                    <span class="d-flex flex-column gap-1 flex-grow-1">
                                        <span class="option-title fw-semibold">Egreso · Disminuir stock</span>
                                        <span class="option-text">Retirar unidades del almacén</span>
                                    </span>
                                    <span class="option-radio flex-shrink-0"></span>
                                </label>
                            </div>
                        </fieldset>

                        <div>
                            <label for="cantidad" class="adjust-label fw-medium">Cantidad a ajustar</label>
                            <div class="quantity-box d-flex align-items-center gap-12 @error('cantidad') border-danger @enderror">
                                <input type="number" id="cantidad" name="cantidad" value="{{ old('cantidad') }}" class="quantity-input fw-medium" min="1" max="99999" step="1" placeholder="4" required>
                                <span class="quantity-unit">unidades</span>
                            </div>
                            <p id="ayudaCantidad" class="quantity-help"></p>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="motivo" class="adjust-label fw-medium">Motivo</label>
                                <select id="motivo" name="motivo" class="form-select input-app @error('motivo') is-invalid @enderror" required>
                                    <option value="">Elige el motivo</option>
                                    @foreach ($motivos as $motivo)
                                        <option value="{{ $motivo->idMotivo }}" @selected((string) old('motivo') === (string) $motivo->idMotivo)>{{ $motivo->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6" id="cajaReferencia">
                                <label for="referencia" class="adjust-label fw-medium">Factura o referencia <span class="field-help">(opcional)</span></label>
                                <input type="text" id="referencia" name="referencia" value="{{ old('referencia') }}" maxlength="60"
                                       class="form-control input-app @error('referencia') is-invalid @enderror" placeholder="Ej. FAC-2026-0184">
                            </div>
                        </div>
                    </div>

                    {{-- Vista previa: ajusteStock.js la recalcula mientras escribes --}}
                    <div class="adjust-preview d-flex flex-column" aria-live="polite">
                        <p class="preview-title fw-semibold m-0">Vista previa del ajuste</p>
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            <div class="preview-value d-flex flex-column gap-2">
                                <span class="preview-label">Stock actual</span>
                                <span id="stockActual" class="preview-number fw-semibold"></span>
                            </div>
                            <span id="operacion" class="preview-operation fw-semibold"></span>
                            <div class="preview-value preview-result d-flex flex-column gap-2">
                                <span class="preview-label">Stock resultante</span>
                                <span id="stockResultante" class="preview-number fw-semibold"></span>
                            </div>
                        </div>
                        <div class="preview-footer d-flex flex-wrap justify-content-between gap-2">
                            <span id="textoMinimo"></span>
                            <span id="estadoMinimo"></span>
                        </div>
                    </div>
                </div>

                <div class="reason-row">
                    <div class="reason-info d-flex flex-column gap-2">
                        <label for="especificacion" class="reason-title fw-semibold" id="tituloEspecificacion">Especificación del egreso</label>
                        <p class="reason-text m-0">Indica el destino, uso o circunstancia que origina el movimiento.</p>
                        <p class="reason-note fw-medium m-0">Obligatorio solo para egresos</p>
                    </div>
                    <textarea id="especificacion" name="especificacion" class="form-control reason-input @error('especificacion') is-invalid @enderror"
                              maxlength="500" placeholder="Salida para mantenimiento de maquinaria del taller.">{{ old('especificacion') }}</textarea>
                </div>
            </div>
        </section>

        {{-- 2) Datos generales del producto (sólo consulta) --}}
        <section class="panel">
            <div class="adjust-section-header d-flex flex-wrap align-items-center gap-3 p-4">
                <span class="adjust-section-icon d-flex align-items-center justify-content-center flex-shrink-0">
                    <img src="{{ asset('imagenes/icon-adjust-package.svg') }}" width="20" height="20" alt="">
                </span>
                <div class="flex-grow-1">
                    <h2 class="panel-title m-0">Datos generales del producto</h2>
                    <p class="panel-text m-0 mt-1">Identificación, precios, ubicación y vehículos registrados.</p>
                </div>
                <span class="readonly-tag fw-semibold">Solo consulta</span>
            </div>
            @foreach ($datos as $fila)
                <div class="data-row {{ $loop->last ? 'data-row-last' : '' }}">
                    @foreach ($fila as $etiqueta => $valor)
                        <div class="data-pair"><span class="data-label">{{ $etiqueta }}</span><span class="data-value fw-medium">{{ $valor }}</span></div>
                    @endforeach
                </div>
            @endforeach
        </section>

        {{-- 3) Últimos movimientos del producto --}}
        <section class="panel">
            <div class="panel-body">
                <h2 class="panel-title m-0">Últimos movimientos</h2>
                <p class="panel-text m-0 mt-1">Las 5 entradas y salidas más recientes de este producto.</p>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Stock</th>
                            <th>Motivo</th>
                            <th>Quién</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movimientos as $movimiento)
                            <tr>
                                <td class="text-muted small text-nowrap">{{ $movimiento->fechaMovimiento?->format('d/m/Y H:i') }}</td>
                                <td>{{ $movimiento->tipoTexto() }}</td>
                                <td class="fw-semibold {{ $movimiento->sumo() ? 'mov-mas' : 'mov-menos' }}">{{ $movimiento->sumo() ? '+' : '−' }}{{ $movimiento->cantidad }}</td>
                                <td class="text-muted">{{ $movimiento->existenciaAnterior }} → {{ $movimiento->existenciaNueva }}</td>
                                <td class="text-muted small">{{ $movimiento->motivo?->nombre ?? $movimiento->observaciones }}</td>
                                <td class="text-muted small">{{ $movimiento->trabajador?->nombres }} {{ $movimiento->trabajador?->apellidoPaterno }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">Este producto todavía no tiene movimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Resumen y confirmar --}}
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="confirm-summary d-flex flex-column flex-grow-1">
                <p id="resumenTitulo" class="summary-title fw-semibold m-0"></p>
                <p id="resumenTexto" class="summary-text m-0"></p>
            </div>
            <a href="{{ route('administrador.productos.index') }}" class="btn-app btn-app-outline adjust-button d-flex align-items-center">Cancelar</a>
            <button type="submit" id="botonConfirmar" class="btn-app btn-app-primary adjust-button d-flex align-items-center gap-2">
                <img id="iconoEntrada" src="{{ asset('imagenes/icon-plus-sm.svg') }}" width="18" height="18" alt="">
                <img id="iconoEgreso" src="{{ asset('imagenes/icon-minus-white.svg') }}" width="18" height="18" alt="">
                <span id="textoConfirmar"></span>
            </button>
        </div>
    </form>
@endsection

@push('modales')
    {{-- Ventana "¿Confirmas el ajuste de stock?" (Figma: Ajuste de stock · Confirmación) --}}
    <div class="modal fade modal-app" id="modalConfirmarAjuste" tabindex="-1" aria-labelledby="confirmarAjusteTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body d-flex flex-column gap-3">
                    <h2 class="modal-app-title m-0" id="confirmarAjusteTitulo">¿Confirmas el ajuste de stock?</h2>
                    <p class="panel-text fs-6 m-0" id="confirmarAjusteTexto"></p>
                    <div class="tarjeta-gris">
                        <div class="fw-semibold text-dark">{{ $producto->nombre }}</div>
                        <div class="small text-muted">{{ $producto->codigoInterno }} · {{ $producto->marca?->nombre }}</div>
                    </div>
                </div>
                <div class="modal-footer gap-3">
                    <button type="button" class="btn-app btn-app-outline flex-grow-1" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-app btn-app-primary flex-grow-1" id="confirmarAjusteSi">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script src="{{ asset('javascript/administrador/ajusteStock.js') }}"></script>
@endpush
