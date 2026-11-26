{{--
    Ventana "Cancelar pedido": motivo + autorización de un gerente o administrador
    (el cajero sólo hace pagos directos por su cuenta).
    Si algo falla, se vuelve a abrir sola con lo que se había escrito.
--}}
@php
    $reabrir = $pedidoACancelar && $pedidoACancelar->estadoPedido === \App\Models\Pedido::PENDIENTE;
@endphp

<div class="modal fade modal-app" id="modalCancelarPedido" tabindex="-1" aria-labelledby="cancelarPedidoTitulo" aria-hidden="true"
     @if ($reabrir)
         data-reabrir="1"
         data-url="{{ route('cajero.pedidos.cancelar', $pedidoACancelar) }}"
         data-folio="{{ $pedidoACancelar->folio }}"
         data-detalle="{{ $pedidoACancelar->nombreCliente() }} · {{ \App\Models\Producto::formatoPrecio($pedidoACancelar->total) }}"
     @endif>
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" class="modal-content" id="formCancelarPedido" autocomplete="off" novalidate>
            @csrf
            @method('PATCH')
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between">
                    <span class="modal-app-icon d-flex align-items-center justify-content-center">
                        <span class="icono" style="--icono: url('{{ asset('imagenes/icon-triangle-alert-md.svg') }}')"></span>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div>
                    <h2 class="modal-app-title m-0" id="cancelarPedidoTitulo">Cancelar pedido <span id="cancelarPedidoFolio"></span></h2>
                    <p class="panel-text fs-6 m-0 mt-2">El pedido sale de «Pendientes» y pasa a «Cancelados». No se borra.</p>
                </div>

                <div class="tarjeta-gris small text-muted" id="cancelarPedidoDetalle"></div>

                @if ($reabrir && $errors->has('cancelar'))
                    <div class="alert alert-danger mb-0" role="alert">
                        @foreach ($errors->get('cancelar') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label for="cancelarPedidoMotivo" class="field-label-app">Motivo</label>
                    <select id="cancelarPedidoMotivo" name="motivo" required class="form-select input-app">
                        <option value="">Selecciona el motivo</option>
                        @foreach ($motivosCancelacion as $motivo)
                            <option value="{{ $motivo->idMotivo }}" @selected($reabrir && (string) old('motivo') === (string) $motivo->idMotivo)>{{ $motivo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="cancelarPedidoComentario" class="field-label-app">Comentario <span class="field-help">(opcional)</span></label>
                    <textarea id="cancelarPedidoComentario" name="comentario" rows="2" maxlength="300" class="form-control input-app"
                              placeholder="Ej. el cliente ya no regresó">{{ $reabrir ? old('comentario') : '' }}</textarea>
                </div>

                @include('cajero.autorizacion', ['prefijo' => 'cancelarPedido', 'reabrir' => $reabrir])
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">No cancelar</button>
                <button type="submit" class="btn-app btn-app-danger">Cancelar pedido</button>
            </div>
        </form>
    </div>
</div>
