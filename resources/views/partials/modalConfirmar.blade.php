{{--
    Ventana de confirmación (Figma: "05 / Modal de confirmación").
    Se incluye una vez por página con @include('partials.modalConfirmar')
    y se abre con cualquier botón que tenga estos atributos:
        data-confirmar-url="..."        a dónde se envía
        data-confirmar-metodo="DELETE"  DELETE o PATCH
        data-confirmar-titulo="¿Eliminar cliente?"
        data-confirmar-texto="..."
        data-confirmar-boton="Eliminar"
--}}
<div class="modal fade modal-app" id="modalConfirmar" tabindex="-1" aria-labelledby="modalConfirmarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" class="modal-content" id="modalConfirmarForm">
            @csrf
            <input type="hidden" name="_method" value="DELETE" id="modalConfirmarMetodo">
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex align-items-start justify-content-between">
                    <span class="modal-app-icon d-flex align-items-center justify-content-center">
                        <span class="icono" style="--icono: url('{{ asset('imagenes/icon-trash-2.svg') }}')"></span>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <h2 class="modal-app-title m-0" id="modalConfirmarTitulo"></h2>
                <p class="panel-text fs-6 m-0" id="modalConfirmarTexto"></p>
            </div>
            <div class="modal-footer justify-content-center gap-3">
                <button type="button" class="btn-app btn-app-outline" data-bs-dismiss="modal">Volver</button>
                <button type="submit" class="btn-app btn-app-danger" id="modalConfirmarBoton"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('javascript/confirmar.js') }}"></script>
@endpush
