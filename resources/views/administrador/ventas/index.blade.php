{{--
    Ventas del administrador · pestaña "Registrar venta" (Figma Page 2: "Ventas · Registrar venta").
    Todo lo manda Administrador\VentaController@index.

    La lista de productos y las cuentas las maneja javascript/administrador/ventas.js.
    Al guardar, el servidor vuelve a calcular todo (App\Support\RegistroVenta): la pantalla
    sólo manda qué productos, cuántos, el cliente, el % de descuento y el método de pago.
--}}
@extends('layouts.panel')

@section('titulo', 'Ventas')
@section('menu', 'ventas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
@endpush

@section('pagina')
    @php
        $puedeCotizar = auth()->user()->puede('pedidos.gestionar');
        $puedeRegistrarCliente = auth()->user()->puede('clientes.gestionar');
        $metodoElegido = (string) old('metodoPago', $metodosPago->first()?->idMetodoPago);
    @endphp

    <div class="mb-3">
        <h1 class="page-heading m-0">Ventas</h1>
        <p class="page-description m-0 mt-1">Registra una venta y actualiza tu inventario.</p>
    </div>

    @include('administrador.ventas.pestanas', ['activa' => 'registrar'])

    @if ($ventaRegistrada)
        <div class="notice notice-ok d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="notice-title">Venta {{ $ventaRegistrada->folio }} · {{ \App\Models\Producto::formatoPrecio($ventaRegistrada->total) }}</div>
                <div class="notice-text">{{ $ventaRegistrada->nombreCliente() }} · {{ $ventaRegistrada->fechaVenta->format('d/m/Y h:i a') }}</div>
            </div>
            {{-- Imprime directo, sin abrir otra pestaña (javascript/imprimir.js). El enlace es por si falla. --}}
            <a href="{{ route('administrador.ventas.ticket', $ventaRegistrada) }}" class="btn-app btn-app-outline" data-imprimir>
                Imprimir ticket
            </a>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4">@include('partials.errores')</div>
    @endif

    <form method="post" action="{{ route('administrador.ventas.store') }}" id="formVenta" novalidate
          data-buscar-clientes="{{ route('administrador.ventas.buscarClientes') }}"
          data-buscar-productos="{{ route('administrador.ventas.buscarProductos') }}"
          data-registrar-cliente="{{ $puedeRegistrarCliente ? route('administrador.clientes.store') : '' }}"
          data-carrito="{{ json_encode($carritoAnterior) }}"
          data-cliente="{{ json_encode($clienteAnterior) }}"
          data-iva="{{ $iva }}"
          data-icono-quitar="{{ asset('imagenes/icon-trash.svg') }}">
        @csrf
        {{-- ventas.js llena aquí los campos ocultos (cliente, productos, descuento, total) --}}
        <div id="camposOcultos"></div>

        <div class="row g-4 align-items-start">
            <div class="col-xl-8 d-flex flex-column gap-4">
                {{-- ---------- Cliente ---------- --}}
                <section class="panel">
                    <div class="panel-body d-flex flex-column gap-3">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <h2 class="panel-title m-0">Cliente</h2>
                            <span class="field-help" id="clienteEstado">Opcional</span>
                        </div>

                        <div id="clienteElegido" class="cliente-elegido d-none">
                            <div>
                                <div class="result-name" id="clienteElegidoNombre"></div>
                                <div class="result-detail" id="clienteElegidoDetalle"></div>
                            </div>
                            <button type="button" class="btn-text" id="clienteQuitar">Cambiar cliente</button>
                        </div>

                        <div id="clienteBuscador">
                            <label for="buscarCliente" class="field-label-app">Buscar cliente por nombre o teléfono</label>
                            <div class="d-flex flex-wrap gap-3">
                                <input type="search" id="buscarCliente" maxlength="60" autocomplete="off"
                                       class="form-control input-app flex-grow-1 buscador-venta" placeholder="Ej. María o 613 123">
                                <button type="button" class="btn-app btn-app-outline" id="consultarCliente">Consultar cliente</button>
                                @if ($puedeRegistrarCliente)
                                    <button type="button" class="btn-app btn-app-outline" data-bs-toggle="modal" data-bs-target="#modalClienteRapido">
                                        Registrar nuevo cliente
                                    </button>
                                @endif
                            </div>
                            <div class="results-list mt-3 d-none" id="resultadosClientes" role="listbox" aria-label="Clientes encontrados"></div>
                            <p class="field-help m-0 mt-2">Si no eliges cliente, la venta queda como «Público en general».</p>
                        </div>
                    </div>
                </section>

                {{-- ---------- Productos ---------- --}}
                <section class="panel">
                    <div class="panel-body d-flex flex-column gap-3">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <h2 class="panel-title m-0">Productos</h2>
                            <span class="field-help" id="conteoProductos">Sin productos</span>
                        </div>

                        <div>
                            <label for="buscarProducto" class="field-label-app">Buscar refacción</label>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <input type="search" id="buscarProducto" maxlength="60" autocomplete="off"
                                       class="form-control input-app flex-grow-1 buscador-venta" placeholder="Nombre, código o marca">
                                <button type="button" class="btn-app btn-app-outline" id="consultarProducto">Consultar producto</button>
                                <span class="field-help ms-auto">Precios sin IVA · MXN</span>
                            </div>
                            <div class="results-list mt-3 d-none" id="resultadosProductos" role="listbox" aria-label="Productos encontrados"></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table tabla-venta">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th class="text-end">Precio unitario</th>
                                    <th class="text-end">Subtotal</th>
                                    <th aria-label="Quitar"></th>
                                </tr>
                            </thead>
                            <tbody id="carrito">
                                <tr id="carritoVacio">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        Busca una refacción y haz clic en ella para agregarla.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- ---------- Resumen de venta ---------- --}}
            <aside class="col-xl-4">
                <section class="panel resumen-venta">
                    <div class="panel-body d-flex flex-column gap-3">
                        <div class="pb-3 border-bottom">
                            <h2 class="panel-title m-0">Resumen de venta</h2>
                            <p class="panel-text m-0 mt-1" id="resumenConteo">0 productos · 0 piezas</p>
                        </div>

                        <div>
                            <div class="d-flex align-items-center justify-content-between">
                                <label for="descuento" class="fw-semibold text-dark">Descuento sobre el total</label>
                                <span class="field-help">Antes de IVA</span>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <div class="input-group">
                                    <input type="number" id="descuento" min="0" max="100" step="0.01" inputmode="decimal"
                                           value="{{ old('descuento', '') }}" class="form-control input-app" placeholder="0">
                                    <span class="input-group-text input-suffix">%</span>
                                </div>
                                <button type="button" class="btn-app btn-app-outline text-nowrap" id="aplicarDescuento">Aplicar</button>
                            </div>
                            <p class="field-help m-0 mt-1" id="descuentoAyuda">Porcentaje de 0 a 100.</p>
                        </div>

                        <div class="d-flex flex-column gap-2 pt-3 border-top">
                            <div class="summary-line d-flex justify-content-between"><span>Subtotal</span><span id="resumenSubtotal">$0.00</span></div>
                            <div class="summary-line d-flex justify-content-between"><span>Descuento <span id="resumenPorcentaje"></span></span><span class="text-primary-app" id="resumenDescuento">−$0.00</span></div>
                            <div class="summary-line d-flex justify-content-between"><span>IVA</span><span id="resumenIva">$0.00</span></div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                            <span class="fw-bold text-dark fs-5">Total</span>
                            <span class="summary-total" id="resumenTotal">$0.00</span>
                        </div>

                        <div>
                            <label for="metodoPago" class="field-label-app">Método de pago</label>
                            <select id="metodoPago" name="metodoPago" class="form-select input-app @error('metodoPago') is-invalid @enderror">
                                @foreach ($metodosPago as $metodo)
                                    <option value="{{ $metodo->idMetodoPago }}" @selected($metodoElegido === (string) $metodo->idMetodoPago)>{{ $metodo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn-app btn-app-primary w-100" id="botonRegistrar" disabled>Registrar venta</button>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn-app btn-app-soft flex-fill botones-cotizacion" id="botonMostrarCotizacion" disabled
                                        formaction="{{ route('administrador.ventas.cotizacion.vista') }}" formtarget="_blank">
                                    Mostrar cotización
                                </button>
                                @if ($puedeCotizar)
                                    <button type="submit" class="btn-app btn-app-soft flex-fill botones-cotizacion" id="botonConvertirCotizacion" disabled
                                            formaction="{{ route('administrador.ventas.cotizacion.store') }}">
                                        Convertir a cotización
                                    </button>
                                @endif
                            </div>
                            <p class="field-help text-center m-0 mt-1">
                                El inventario se actualiza al registrar. Una cotización no descuenta inventario.
                            </p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
@endsection

@push('modales')
    @if ($puedeRegistrarCliente)
        @include('administrador.ventas.modalClienteRapido')
    @endif
@endpush

@push('scripts')
    <script src="{{ asset('javascript/imprimir.js') }}"></script>
    <script src="{{ asset('javascript/administrador/ventas.js') }}"></script>
@endpush
