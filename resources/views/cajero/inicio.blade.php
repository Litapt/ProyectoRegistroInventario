{{--
    Cajero · "Nueva venta" (su pantalla principal). Lo manda Cajero\InicioController.

    Vende directo y cobra: cliente (opcional), productos, método de pago y, en efectivo,
    con cuánto paga y el cambio. Usa el mismo javascript/administrador/ventas.js que "Registrar venta"
    del administrador; lo del cambio y la autorización está en javascript/cajero/venta.js.

    Sólo los PAGOS DIRECTOS los hace solo. Si pone descuento o el cliente paga sólo una parte,
    aparece la autorización de un gerente o administrador (el servidor también lo revisa).
    Si no ha abierto su turno, encima aparece la ventana "Abrir turno" (no se puede cerrar).
--}}
@extends('layouts.barraSuperior')

@section('titulo', 'Nueva venta')

@section('navegacion')
    @include('cajero.menu', ['activo' => 'venta'])
@endsection

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cajero/cajero.css') }}">
@endpush

@section('pagina')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
        $metodoElegido = (string) old('metodoPago', $idEfectivo ?? $metodosPago->first()?->idMetodoPago);
        $condicion = old('condicion', 'CONTADO');
        $pideAutorizacion = $errors->has('autorizacion');
    @endphp

    <div class="mb-4">
        <h1 class="page-heading m-0">Nueva venta</h1>
        <p class="page-description m-0 mt-1">
            @if ($turno)
                {{ $turno->nombre() }} · abierto desde las {{ $turno->fechaApertura->format('h:i a') }} con {{ $dinero($turno->fondoInicial) }}
            @else
                Abre tu turno para empezar a cobrar.
            @endif
        </p>
    </div>

    @if ($corteGuardado)
        <div class="notice notice-ok d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="notice-title">Corte del {{ $corteGuardado->nombre() }} guardado</div>
                <div class="notice-text">El comprobante con todas tus ventas del turno se manda a imprimir solo. Para seguir vendiendo, abre otro turno.</div>
            </div>
            <a href="{{ route('cajero.corte.comprobante', $corteGuardado) }}" class="btn-app btn-app-outline" data-imprimir data-imprimir-auto>
                Imprimir corte
            </a>
        </div>
    @endif

    @if ($ventaCobrada)
        <div class="notice notice-ok d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="notice-title">Venta {{ $ventaCobrada->folio }} · {{ $dinero($ventaCobrada->total) }}</div>
                <div class="notice-text">
                    {{ $ventaCobrada->nombreCliente() }}
                    @if ($ventaCobrada->tieneSaldo())
                        · Pagó {{ $dinero($ventaCobrada->pagado()) }}, debe <strong>{{ $dinero($ventaCobrada->saldo()) }}</strong>
                    @endif
                    · El ticket se manda a imprimir solo.
                </div>
            </div>
            {{-- data-imprimir-auto: se imprime al llegar, sin abrir otra pestaña (javascript/imprimir.js) --}}
            <a href="{{ route('cajero.ventas.ticket', $ventaCobrada) }}" class="btn-app btn-app-outline" data-imprimir data-imprimir-auto>
                Imprimir ticket
            </a>
        </div>
    @endif

    @if ($cotizacionGuardada)
        <div class="notice notice-ok d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="notice-title">Cotización {{ $cotizacionGuardada->folio }} · {{ $dinero($cotizacionGuardada->total) }}</div>
                <div class="notice-text">{{ $cotizacionGuardada->cliente?->nombre() ?? 'Público en general' }} · válida hasta el {{ $cotizacionGuardada->vigencia->format('d/m/Y') }}</div>
            </div>
            <a href="{{ route('cajero.ventas.cotizacion', $cotizacionGuardada) }}" class="btn-app btn-app-outline" data-imprimir>Imprimir cotización</a>
        </div>
    @endif

    @if ($cotizacion)
        <div class="notice notice-warning mb-4">
            <div class="notice-title">Cobrando la cotización {{ $cotizacion->folio }} (cotizada en {{ $dinero($cotizacion->total) }})</div>
            <div class="notice-text">
                Se cobra con los precios de hoy.
                {{ (float) $cotizacion->descuentoTotal > 0 ? 'Trae descuento: al cobrar pide autorización.' : '' }}
                {{ $cotizacion->estaVigente() ? '' : 'Ojo: su vigencia terminó el ' . $cotizacion->vigencia?->format('d/m/Y') . '.' }}
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4">@include('partials.errores')</div>
    @endif

    <form method="post" action="{{ route('cajero.ventas.store') }}" id="formVenta" novalidate autocomplete="off"
          data-buscar-clientes="{{ route('cajero.buscarClientes') }}"
          data-buscar-productos="{{ route('cajero.buscarProductos') }}"
          data-registrar-cliente="{{ route('cajero.clientes.store') }}"
          data-carrito="{{ json_encode($carritoAnterior) }}"
          data-cliente="{{ json_encode($clienteAnterior) }}"
          data-icono-quitar="{{ asset('imagenes/icon-trash.svg') }}"
          data-efectivo="{{ $idEfectivo }}">
        @csrf
        <div id="camposOcultos"></div>
        @if ($cotizacion)
            <input type="hidden" name="cotizacion" value="{{ $cotizacion->idPedido }}">
        @endif

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
                                <button type="button" class="btn-app btn-app-outline" data-bs-toggle="modal" data-bs-target="#modalClienteRapido">
                                    Registrar nuevo cliente
                                </button>
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

            {{-- ---------- Resumen y cobro ---------- --}}
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
                                <span class="field-help">Necesita autorización</span>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <div class="input-group">
                                    <input type="number" id="descuento" min="0" max="100" step="0.01" inputmode="decimal"
                                           value="{{ $descuentoAnterior ?: '' }}" class="form-control input-app" placeholder="0">
                                    <span class="input-group-text input-suffix">%</span>
                                </div>
                                <button type="button" class="btn-app btn-app-outline text-nowrap" id="aplicarDescuento">Aplicar</button>
                            </div>
                            <p class="field-help m-0 mt-1" id="descuentoAyuda">Porcentaje de 0 a 100, antes de IVA.</p>
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

                        {{-- Pagar todo o dejar saldo pendiente (sólo con cliente registrado y con autorización) --}}
                        <div class="d-flex flex-column gap-2" id="bloqueCondicion">
                            <label class="config-opcion m-0">
                                <input type="radio" name="condicionPago" value="CONTADO" @checked($condicion !== 'CREDITO')>
                                <span><span class="fw-semibold text-dark">Paga todo ahora</span></span>
                            </label>
                            <label class="config-opcion m-0" id="opcionCredito">
                                <input type="radio" name="condicionPago" value="CREDITO" @checked($condicion === 'CREDITO')>
                                <span>
                                    <span class="fw-semibold text-dark">Paga una parte</span>
                                    <span class="field-help d-block" id="ayudaCredito">El resto queda pendiente a cuenta del cliente.</span>
                                </span>
                            </label>
                            <div class="d-none" id="bloquePagoInicial">
                                <label for="pagoInicial" class="field-label-app">¿Cuánto paga ahora?</label>
                                <div class="input-group">
                                    <span class="input-group-text input-prefix">$</span>
                                    <input type="number" id="pagoInicial" min="0" step="0.01" inputmode="decimal"
                                           value="{{ old('pagoInicial', '') }}" class="form-control input-app @error('condicion') is-invalid @enderror" placeholder="0.00">
                                </div>
                                <p class="field-help m-0 mt-1" id="ayudaPagoInicial"></p>
                            </div>
                        </div>

                        {{-- Sólo en efectivo: con cuánto paga y el cambio --}}
                        <div id="bloqueEfectivo">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="recibido" class="field-label-app">Monto recibido</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-prefix">$</span>
                                        <input type="number" id="recibido" name="recibido" min="0" step="0.01" inputmode="decimal"
                                               value="{{ old('recibido') }}" class="form-control input-app @error('recibido') is-invalid @enderror" placeholder="0.00">
                                    </div>
                                    <button type="button" class="btn-app btn-app-soft btn-app-sm mt-2" id="botonExacto">Exacto</button>
                                </div>
                                <div class="col-6">
                                    <span class="field-label-app d-block">Cambio</span>
                                    <div class="cambio-caja" id="cambio" aria-live="polite">$0.00</div>
                                    <p class="field-help m-0 mt-1" id="ayudaCambio"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Descuento o saldo pendiente: autoriza un gerente o administrador --}}
                        <div class="{{ $pideAutorizacion ? '' : 'd-none' }}" id="bloqueAutorizacion">
                            @include('cajero.autorizacion', [
                                'prefijo' => 'venta',
                                'ayuda' => 'Esta venta tiene descuento o deja saldo pendiente: no es un pago directo.',
                                'idAyuda' => 'ayudaAutorizacion',
                            ])
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn-app btn-app-primary w-100" id="botonRegistrar" disabled>Cobrar venta</button>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn-app btn-app-soft flex-fill botones-cotizacion" id="botonMostrarCotizacion" disabled
                                        formaction="{{ route('cajero.ventas.cotizacion.vista') }}" formtarget="marcoImpresion">
                                    Mostrar cotización
                                </button>
                                <button type="submit" class="btn-app btn-app-soft flex-fill botones-cotizacion" id="botonConvertirCotizacion" disabled
                                        formaction="{{ route('cajero.ventas.cotizacion.store') }}">
                                    Convertir a cotización
                                </button>
                            </div>
                            <p class="field-help text-center m-0 mt-1">Al cobrar se descuenta el inventario y se imprime el ticket. Una cotización no lo toca.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
@endsection

@push('modales')
    @include('administrador.ventas.modalClienteRapido')
    @unless ($turno)
        @include('cajero.modalAbrirTurno')
    @endunless
@endpush

@push('scripts')
    <script src="{{ asset('javascript/imprimir.js') }}"></script>
    <script src="{{ asset('javascript/cajero/inicio.js') }}"></script>
    {{-- venta.js va ANTES de ventas.js: así revisa el cambio y la autorización antes de enviar --}}
    <script src="{{ asset('javascript/cajero/venta.js') }}"></script>
    <script src="{{ asset('javascript/administrador/ventas.js') }}"></script>
@endpush
