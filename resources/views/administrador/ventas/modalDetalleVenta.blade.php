{{--
    Ventana "Detalle de la venta" (sólo para ver). La llena consultarVentas.js
    con los datos del botón "Ver" (Venta::datosParaDetalle()).
--}}
<div class="modal fade modal-app" id="modalDetalleVenta" tabindex="-1" aria-labelledby="detalleVentaTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-bottom">
                    <div>
                        <h2 class="modal-app-title m-0">Venta <span id="detalleFolio"></span></h2>
                        <p class="panel-text fs-6 m-0 mt-1" id="detalleSubtitulo"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="notice notice-danger d-none" id="detalleCancelacion">
                    <div class="notice-title">Venta cancelada</div>
                    <div class="notice-text" id="detalleCancelacionTexto"></div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-4"><div class="field-help">Cliente</div><div class="fw-semibold text-dark" id="detalleCliente"></div></div>
                    <div class="col-sm-4"><div class="field-help">Atendió</div><div class="fw-semibold text-dark" id="detalleTrabajador"></div></div>
                    <div class="col-sm-4"><div class="field-help">Método de pago</div><div class="fw-semibold text-dark" id="detallePago"></div></div>
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
                        <tbody id="detalleRenglones"></tbody>
                    </table>
                </div>

                <div class="d-none" id="detalleDevoluciones">
                    <h3 class="panel-title m-0 mb-2">Devoluciones</h3>
                    <div class="d-flex flex-column gap-2" id="detalleDevolucionesLista"></div>
                </div>

                <div class="ms-auto d-flex flex-column gap-1" style="min-width: 240px">
                    <div class="summary-line d-flex justify-content-between"><span>Subtotal</span><span id="detalleSubtotal"></span></div>
                    <div class="summary-line d-flex justify-content-between" id="detalleDescuentoFila"><span>Descuento</span><span class="text-primary-app" id="detalleDescuento"></span></div>
                    <div class="summary-line d-flex justify-content-between"><span>IVA</span><span id="detalleIva"></span></div>
                    <div class="d-flex justify-content-between pt-2 border-top fw-bold text-dark fs-5"><span>Total</span><span id="detalleTotal"></span></div>
                </div>
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" class="btn-app btn-app-primary" id="detalleImprimir" data-imprimir>Imprimir ticket</a>
            </div>
        </div>
    </div>
</div>
