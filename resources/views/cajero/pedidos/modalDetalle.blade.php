{{--
    Ventana "Detalles del pedido" (Figma: "Cajero - Detalles Pedido"). Sólo para ver.
    La llena javascript/cajero/inicio.js con los datos del botón "Ver" (Pedido::datosParaDetalle()).
    Si el pedido está pendiente, abajo salen "Editar pedido" y "Cobrar".
--}}
<div class="modal fade modal-app" id="modalDetallePedido" tabindex="-1" aria-labelledby="detallePedidoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0" id="detallePedidoTitulo">Detalles del pedido</h2>
                        <p class="panel-text fs-6 m-0 mt-1" id="pedidoSubtitulo"></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge-folio" id="pedidoFolio"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="field-help">Cliente</div>
                        <div class="fw-semibold text-dark" id="pedidoCliente"></div>
                        <div class="cell-sub" id="pedidoClienteDatos"></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="field-help">Lo registró</div>
                        <div class="fw-semibold text-dark" id="pedidoTrabajador"></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio unitario</th>
                                <th class="text-end">Importe</th>
                            </tr>
                        </thead>
                        <tbody id="pedidoRenglones"></tbody>
                    </table>
                </div>

                <div class="ms-auto d-flex flex-column gap-1 resumen-detalle">
                    <div class="summary-line d-flex justify-content-between"><span>Subtotal</span><span id="pedidoSubtotal"></span></div>
                    <div class="summary-line d-flex justify-content-between" id="pedidoDescuentoFila"><span>Descuento</span><span class="text-primary-app" id="pedidoDescuento"></span></div>
                    <div class="summary-line d-flex justify-content-between"><span>IVA</span><span id="pedidoIva"></span></div>
                    <div class="d-flex justify-content-between pt-2 border-top fw-bold text-dark fs-5"><span>Total</span><span id="pedidoTotal"></span></div>
                </div>
            </div>

            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Regresar</button>
                <a href="#" class="btn-app btn-app-soft d-none" id="pedidoEditar">Editar pedido</a>
                <a href="#" class="btn-app btn-app-primary d-none" id="pedidoCobrar">Cobrar</a>
            </div>
        </div>
    </div>
</div>
