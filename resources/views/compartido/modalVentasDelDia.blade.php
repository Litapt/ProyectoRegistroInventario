{{--
    Ventana "Imprimir ventas del día" (administrador, gerente y cajero; cada quien imprime las SUYAS).
    Siempre la autoriza un gerente o administrador. Se imprime en un marco invisible
    (javascript/imprimir.js con formtarget="marcoImpresion"): la página no se mueve.
        @include('compartido.modalVentasDelDia')
--}}
<div class="modal fade modal-app" id="modalVentasDelDia" tabindex="-1" aria-labelledby="ventasDelDiaTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ \App\Support\Ruta::a('ventas.delDia') }}" class="modal-content" id="formVentasDelDia" autocomplete="off">
            @csrf
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <h2 class="modal-app-title m-0" id="ventasDelDiaTitulo">Imprimir mis ventas del día</h2>
                        <p class="panel-text fs-6 m-0 mt-1">
                            Todas las ventas, abonos y devoluciones que registraste ese día, con lo cobrado por método de pago,
                            para entregarlo con el corte de caja.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div>
                    <label for="ventasDelDiaFecha" class="field-label-app">Día</label>
                    <input type="date" id="ventasDelDiaFecha" name="dia" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                           class="form-control input-app" required>
                </div>

                @include('cajero.autorizacion', ['prefijo' => 'ventasDelDia', 'reabrir' => false, 'ayuda' => 'Lo autoriza un gerente o administrador.'])
            </div>

            <div class="modal-footer justify-content-end gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn-app btn-app-primary" id="ventasDelDiaBoton" formtarget="marcoImpresion">Imprimir</button>
            </div>
        </form>
    </div>
</div>
