{{-- Lista de errores del formulario (los manda Laravel cuando un dato no es válido) --}}
@if ($errors->any())
    <div class="alert alert-danger mb-0" role="alert">
        <p class="fw-semibold mb-2">Revisa estos datos antes de continuar:</p>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
