{{-- Errores de UNA sección de Configuración. Uso: @include('administrador.configuracion.errores', ['bolsa' => 'tienda']) --}}
@if ($errors->getBag($bolsa)->any())
    <div class="alert alert-danger mb-0" role="alert">
        @foreach ($errors->getBag($bolsa)->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
