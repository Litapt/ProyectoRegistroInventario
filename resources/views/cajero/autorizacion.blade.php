{{--
    Correo y contraseña de un gerente o administrador (el cajero sólo hace pagos directos;
    descuentos, saldo pendiente, abonos, devoluciones y cancelaciones los autoriza un superior).
        @include('cajero.autorizacion', ['prefijo' => 'venta', 'reabrir' => true, 'titulo' => '...'])
    La contraseña nunca se regresa a la pantalla.
--}}
@php
    $prefijo = $prefijo ?? 'autoriza';
    $reabrir = $reabrir ?? true;
@endphp
<div class="autorizacion-caja">
    <p class="fw-semibold text-dark mb-1">{{ $titulo ?? 'Autorización del gerente o administrador' }}</p>
    @isset($ayuda)
        <p class="field-help m-0 mb-2" @isset($idAyuda) id="{{ $idAyuda }}" @endisset>{{ $ayuda }}</p>
    @endisset
    <div class="row g-3">
        <div class="col-sm-6">
            <label for="{{ $prefijo }}Correo" class="field-label-app">Correo</label>
            <input type="email" id="{{ $prefijo }}Correo" name="correo_autoriza" maxlength="120"
                   value="{{ $reabrir ? old('correo_autoriza') : '' }}"
                   class="form-control input-app" placeholder="gerente@correo.com" autocomplete="off">
        </div>
        <div class="col-sm-6">
            <label for="{{ $prefijo }}Contrasena" class="field-label-app">Contraseña</label>
            <input type="password" id="{{ $prefijo }}Contrasena" name="contrasena_autoriza" maxlength="255"
                   class="form-control input-app" placeholder="Ingresa la contraseña" autocomplete="new-password">
        </div>
    </div>
</div>
