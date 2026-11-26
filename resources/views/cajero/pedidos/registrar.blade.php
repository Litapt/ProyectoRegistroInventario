{{--
    Cajero · "Editar pedido" (antes de cobrarlo). Lo manda Cajero\PedidoController@edit.
    Los pedidos los arman los vendedores; el cajero puede corregir cliente, productos o cantidades.
    Usa el mismo javascript/administrador/ventas.js que "Registrar venta". El cajero no aplica descuentos.
--}}
@extends('layouts.barraSuperior')

@section('titulo', 'Editar pedido ' . $pedido->folio)

@section('navegacion')
    @include('cajero.menu', ['activo' => 'pedidos'])
@endsection

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cajero/cajero.css') }}">
@endpush

@section('pagina')
    <a href="{{ route('cajero.pedidos.index') }}" class="volver-enlace mb-3">← Volver a los pedidos</a>

    <div class="mb-4">
        <h1 class="page-heading m-0">{{ $pedido ? 'Editar pedido ' . $pedido->folio : 'Registrar nuevo pedido' }}</h1>
        <p class="page-description m-0 mt-1">
            {{ $pedido ? 'Cambia el cliente, los productos o las cantidades antes de cobrarlo.' : 'Arma el pedido; queda en «Pendientes de cobrar» hasta que lo cobres.' }}
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4">@include('partials.errores')</div>
    @endif

    <form method="post" action="{{ route('cajero.pedidos.update', $pedido) }}" id="formVenta" novalidate
          data-buscar-clientes="{{ route('cajero.buscarClientes') }}"
          data-buscar-productos="{{ route('cajero.buscarProductos') }}"
          data-registrar-cliente="{{ route('cajero.clientes.store') }}"
          data-carrito="{{ json_encode($carritoAnterior) }}"
          data-cliente="{{ json_encode($clienteAnterior) }}"
          data-icono-quitar="{{ asset('imagenes/icon-trash.svg') }}">
        @csrf
        @if ($pedido)
            @method('PUT')
        @endif
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
                                <button type="button" class="btn-app btn-app-outline" data-bs-toggle="modal" data-bs-target="#modalClienteRapido">
                                    Registrar nuevo cliente
                                </button>
                            </div>
                            <div class="results-list mt-3 d-none" id="resultadosClientes" role="listbox" aria-label="Clientes encontrados"></div>
                            <p class="field-help m-0 mt-2">Si no eliges cliente, queda como «Público en general» (y no podrá dejar saldo pendiente).</p>
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

            {{-- ---------- Resumen ---------- --}}
            <aside class="col-xl-4">
                <section class="panel resumen-venta">
                    <div class="panel-body d-flex flex-column gap-3">
                        <div class="pb-3 border-bottom">
                            <h2 class="panel-title m-0">Resumen del pedido</h2>
                            <p class="panel-text m-0 mt-1" id="resumenConteo">0 productos · 0 piezas</p>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <div class="summary-line d-flex justify-content-between"><span>Subtotal</span><span id="resumenSubtotal">$0.00</span></div>
                            <div class="summary-line d-flex justify-content-between"><span>IVA</span><span id="resumenIva">$0.00</span></div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                            <span class="fw-bold text-dark fs-5">Total</span>
                            <span class="summary-total" id="resumenTotal">$0.00</span>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn-app btn-app-primary w-100" id="botonRegistrar" disabled>
                                {{ $pedido ? 'Guardar cambios' : 'Guardar pedido' }}
                            </button>
                            <a href="{{ route('cajero.pedidos.index') }}" class="btn-app btn-app-soft w-100">Cancelar</a>
                            <p class="field-help text-center m-0 mt-1">El inventario se descuenta hasta que se cobra.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
@endsection

@push('modales')
    @include('administrador.ventas.modalClienteRapido')
@endpush

@push('scripts')
    <script src="{{ asset('javascript/administrador/ventas.js') }}"></script>
@endpush
