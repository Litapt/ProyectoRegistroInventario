{{--
    Ventana "Registrar abono" para ventas a crédito (las que quedaron con saldo pendiente).
    El abono no puede pasar de lo que debe. Si el servidor regresa un error, se vuelve a abrir.
--}}
@php
    $reabrirAbono = $ventaAAbonar && $ventaAAbonar->tieneSaldo();
@endphp
<div class="modal fade modal-app" id="modalAbono" tabindex="-1" aria-labelledby="abonoTitulo" aria-hidden="true"
     @if ($reabrirAbono)
         data-reabrir="1"
         data-url="{{ \App\Support\Ruta::a('ventas.abono', $ventaAAbonar) }}"
         data-folio="{{ $ventaAAbonar->folio }}"
         data-detalle="{{ $ventaAAbonar->nombreCliente() }} · Total {{ \App\Models\Producto::formatoPrecio($ventaAAbonar->total) }} · Pagado {{ \App\Models\Producto::formatoPrecio($ventaAAbonar->pagado()) }}"
         data-saldo="{{ number_format($ventaAAbonar->saldo(), 2, '.', '') }}"
     @endif>
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" class="modal-content" id="formAbono" novalidate autocomplete="off">
            @csrf
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <h2 class="modal-app-title m-0" id="abonoTitulo">Registrar abono · <span id="abonoFolio"></span></h2>
                        <p class="panel-text fs-6 m-0 mt-1" id="abonoDetalle"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                @if ($reabrirAbono && $errors->has('abono'))
                    <div class="alert alert-danger mb-0" role="alert" id="abonoErrores">
                        @foreach ($errors->get('abono') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="tarjeta-gris d-flex align-items-center justify-content-between">
                    <span class="fw-semibold text-dark">Debe</span>
                    <span class="summary-total fs-4" id="abonoSaldo"></span>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="abonoMonto" class="field-label-app">¿Cuánto abona?</label>
                        <div class="input-group">
                            <span class="input-group-text input-prefix">$</span>
                            <input type="number" id="abonoMonto" name="monto" min="0.01" step="0.01" inputmode="decimal"
                                   value="{{ $reabrirAbono ? old('monto') : '' }}" class="form-control input-app" placeholder="0.00">
                        </div>
                        <button type="button" class="btn-text mt-1" id="abonoTodo">Liquidar todo</button>
                    </div>
                    <div class="col-sm-6">
                        <label for="abonoMetodo" class="field-label-app">Método de pago</label>
                        <select id="abonoMetodo" name="metodoPago" class="form-select input-app">
                            @foreach ($metodosPago as $metodo)
                                <option value="{{ $metodo->idMetodoPago }}" @selected($reabrirAbono && (string) old('metodoPago') === (string) $metodo->idMetodoPago)>{{ $metodo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="field-help m-0" id="abonoRestante"></p>
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-app btn-app-primary" id="abonoBoton">Registrar abono</button>
            </div>
        </form>
    </div>
</div>
