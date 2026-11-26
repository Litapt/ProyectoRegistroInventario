{{--
    Cajero · "Cobrar" un pedido (Figma: "Cajero - Cobrar").
    Lo manda Cajero\PedidoController@cobrar.

    A la izquierda el pedido con los precios de HOY; a la derecha el importe:
    método de pago, (si hay cliente) paga todo o una parte, y en efectivo
    con cuánto paga y su cambio (javascript/cajero/cobrar.js). El servidor revisa todo otra vez.
--}}
@extends('layouts.barraSuperior')

@section('titulo', 'Cobrar ' . $pedido->folio)

@section('navegacion')
    @include('cajero.menu', ['activo' => 'pedidos'])
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
    @endphp

    <a href="{{ route('cajero.pedidos.index') }}" class="volver-enlace mb-3">← Volver a los pedidos</a>

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-heading m-0">Cobrar pedido {{ $pedido->folio }}</h1>
            <p class="page-description m-0 mt-1">{{ $turno->nombre() }} · Lo registró {{ $pedido->trabajador?->nombreCompleto() }} el {{ $pedido->fechaPedido->format('d/m/Y h:i a') }}</p>
        </div>
        <a href="{{ route('cajero.pedidos.edit', $pedido) }}" class="btn-app btn-app-outline">Editar pedido</a>
    </div>

    @if ($errors->any())
        <div class="mb-4">@include('partials.errores')</div>
    @endif

    @if ($problema)
        <div class="notice notice-danger mb-4">
            <div class="notice-title">Este pedido no se puede cobrar así</div>
            <div class="notice-text">{{ $problema }}</div>
        </div>
    @elseif ($cambioDePrecio)
        <div class="notice notice-warning mb-4">
            <div class="notice-title">Algún precio cambió desde que se armó el pedido</div>
            <div class="notice-text">Se registró por {{ $dinero($pedido->total) }}; con los precios de hoy es {{ $dinero($cuentas['total']) }}. Se cobra con los de hoy.</div>
        </div>
    @endif

    <div class="row g-4 align-items-start">
        {{-- ---------- El pedido ---------- --}}
        <div class="col-xl-7">
            <section class="panel">
                <div class="panel-body d-flex flex-column gap-3">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h2 class="panel-title m-0">Detalles de la venta</h2>
                            <p class="panel-text m-0 mt-1">{{ $pedido->piezas() }} {{ $pedido->piezas() === 1 ? 'pieza' : 'piezas' }} · precios de hoy</p>
                        </div>
                        <span class="badge-folio">{{ $pedido->folio }}</span>
                    </div>
                    <div class="tarjeta-gris">
                        <div class="field-help">Cliente</div>
                        <div class="fw-semibold text-dark">{{ $pedido->nombreCliente() }}</div>
                        @if ($pedido->cliente)
                            <div class="cell-sub">{{ $pedido->cliente->codigo() }} · {{ $pedido->cliente->telefono }}</div>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio unitario</th>
                                <th class="text-end">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($cuentas)
                                @foreach ($cuentas['renglones'] as $r)
                                    <tr>
                                        <td>
                                            <div class="cell-main">{{ $r['producto']->nombre }}</div>
                                            <div class="cell-sub">{{ $r['producto']->codigoInterno }} · {{ $r['producto']->existencia }} en existencia</div>
                                        </td>
                                        <td class="text-end">{{ $r['cantidad'] }}</td>
                                        <td class="text-end text-nowrap">{{ $dinero($r['precio']) }}</td>
                                        <td class="text-end text-nowrap fw-semibold text-dark">{{ $dinero($r['subtotal']) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                @foreach ($pedido->detalles as $d)
                                    <tr>
                                        <td><div class="cell-main">{{ $d->producto?->nombre }}</div><div class="cell-sub">{{ $d->producto?->codigoInterno }}</div></td>
                                        <td class="text-end">{{ $d->cantidad }}</td>
                                        <td class="text-end text-nowrap">{{ $dinero($d->precioUnitario) }}</td>
                                        <td class="text-end text-nowrap">{{ $dinero($d->subtotal) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                @if ($cuentas)
                    <div class="panel-body d-flex flex-column gap-2 border-top">
                        <div class="summary-line d-flex justify-content-between"><span>Subtotal</span><span>{{ $dinero($cuentas['subtotal']) }}</span></div>
                        @if ($cuentas['descuento'] > 0)
                            <div class="summary-line d-flex justify-content-between"><span>Descuento</span><span class="text-primary-app">−{{ $dinero($cuentas['descuento']) }}</span></div>
                        @endif
                        <div class="summary-line d-flex justify-content-between"><span>IVA</span><span>{{ $dinero($cuentas['iva']) }}</span></div>
                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="fw-bold text-dark fs-5">Total</span>
                            <span class="summary-total">{{ $dinero($cuentas['total']) }}</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>

        {{-- ---------- Importe ---------- --}}
        <aside class="col-xl-5">
            <section class="panel resumen-venta">
                <form method="post" action="{{ route('cajero.pedidos.pagar', $pedido) }}" class="panel-body d-flex flex-column gap-3"
                      id="formCobrar" novalidate autocomplete="off"
                      data-total="{{ $cuentas ? number_format($cuentas['total'], 2, '.', '') : '0' }}"
                      data-efectivo="{{ $idEfectivo }}" data-con-descuento="{{ $conDescuento ? '1' : '' }}">
                    @csrf
                    <input type="hidden" name="totalEsperado" value="{{ $cuentas ? number_format($cuentas['total'], 2, '.', '') : '' }}">

                    <div>
                        <h2 class="panel-title m-0">Importe</h2>
                        <p class="panel-text m-0 mt-1">Captura lo que te entrega el cliente.</p>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark">Total a cobrar</span>
                        <span class="summary-total fs-3">{{ $cuentas ? $dinero($cuentas['total']) : '—' }}</span>
                    </div>

                    <div>
                        <label for="metodoPago" class="field-label-app">Método de pago</label>
                        <select id="metodoPago" name="metodoPago" class="form-select input-app @error('metodoPago') is-invalid @enderror">
                            @foreach ($metodosPago as $metodo)
                                <option value="{{ $metodo->idMetodoPago }}" @selected($metodoElegido === (string) $metodo->idMetodoPago)>{{ $metodo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($pedido->cliente)
                        <div class="d-flex flex-column gap-2">
                            <label class="config-opcion m-0">
                                <input type="radio" name="condicion" value="CONTADO" @checked($condicion !== 'CREDITO')>
                                <span><span class="fw-semibold text-dark">Paga todo ahora</span></span>
                            </label>
                            <label class="config-opcion m-0">
                                <input type="radio" name="condicion" value="CREDITO" @checked($condicion === 'CREDITO')>
                                <span>
                                    <span class="fw-semibold text-dark">Paga una parte</span>
                                    <span class="field-help d-block">El resto queda pendiente a cuenta de {{ $pedido->nombreCliente() }}. Necesita autorización.</span>
                                </span>
                            </label>
                            <div class="d-none" id="bloquePagoInicial">
                                <label for="pagoInicial" class="field-label-app">¿Cuánto paga ahora?</label>
                                <div class="input-group">
                                    <span class="input-group-text input-prefix">$</span>
                                    <input type="number" id="pagoInicial" name="pagoInicial" min="0" step="0.01" inputmode="decimal"
                                           value="{{ old('pagoInicial') }}" class="form-control input-app" placeholder="0.00">
                                </div>
                                <p class="field-help m-0 mt-1" id="ayudaPagoInicial"></p>
                            </div>
                        </div>
                    @endif

                    {{-- Sólo en efectivo --}}
                    <div id="bloqueEfectivo">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="recibido" class="field-label-app">Monto recibido</label>
                                <div class="input-group">
                                    <span class="input-group-text input-prefix">$</span>
                                    <input type="number" id="recibido" name="recibido" min="0" step="0.01" inputmode="decimal"
                                           value="{{ old('recibido') }}" class="form-control input-app @error('recibido') is-invalid @enderror" placeholder="0.00">
                                </div>
                                <button type="button" class="btn-app btn-app-soft btn-app-sm mt-2" id="botonExacto">Exacto</button>
                            </div>
                            <div class="col-sm-6">
                                <span class="field-label-app d-block">Cambio</span>
                                <div class="cambio-caja" id="cambio" aria-live="polite">$0.00</div>
                                <p class="field-help m-0 mt-1" id="ayudaCambio"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Descuento o saldo pendiente: autoriza un gerente o administrador --}}
                    <div class="{{ $conDescuento || $errors->has('autorizacion') ? '' : 'd-none' }}" id="bloqueAutorizacion">
                        @include('cajero.autorizacion', [
                            'prefijo' => 'cobro',
                            'ayuda' => $conDescuento ? 'Este pedido tiene descuento: no es un pago directo.' : 'Dejar saldo pendiente no es un pago directo.',
                            'idAyuda' => 'ayudaAutorizacion',
                        ])
                    </div>

                    <div class="d-flex flex-column gap-2 pt-2">
                        <button type="submit" class="btn-app btn-app-primary w-100" id="botonCobrar" @disabled($problema || !$cuentas)>
                            Proceder con el pago
                        </button>
                        <a href="{{ route('cajero.pedidos.index') }}" class="btn-app btn-app-soft w-100">Cancelar</a>
                        <p class="field-help text-center m-0 mt-1">Al cobrar se descuenta el inventario y se imprime el ticket.</p>
                    </div>
                </form>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('javascript/cajero/cobrar.js') }}"></script>
@endpush
