{{--
    Ventana "Abrir turno" (Figma: "Cajero - Inicio" y "Cajero - Autorizar").
    Aparece sola si el cajero no tiene turno abierto y no se puede cerrar.
    Cuenta billetes y monedas; javascript/cajero/inicio.js suma en pantalla
    (el servidor vuelve a sumar: App\Support\Caja).
    Si el total no es el fondo autorizado, pide la autorización de un gerente o administrador.
--}}
@php
    $errores = $errors->getBag('turno');
    $conteo = old('billetes', []);
@endphp

<div class="modal fade modal-app" id="modalAbrirTurno" tabindex="-1" aria-labelledby="abrirTurnoTitulo" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false" data-abrir-solo="1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="post" action="{{ route('cajero.turno.abrir') }}" class="modal-content" id="formAbrirTurno"
              autocomplete="off" novalidate data-fondo="{{ number_format($fondoAutorizado, 2, '.', '') }}">
            @csrf
            <div class="modal-body d-flex flex-column gap-3">
                <div>
                    <h2 class="modal-app-title m-0" id="abrirTurnoTitulo">Abrir turno</h2>
                    <p class="panel-text fs-6 m-0 mt-1">Cuenta el dinero con el que empieza la caja.</p>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="field-help">Fecha y hora de apertura</div>
                        <div class="fw-semibold text-dark">{{ now()->format('d/m/Y h:i a') }}</div>
                    </div>
                    <div class="col-6">
                        <div class="field-help">Fondo autorizado</div>
                        <div class="fw-semibold text-primary-app">{{ \App\Models\Producto::formatoPrecio($fondoAutorizado) }}</div>
                    </div>
                </div>

                @if ($errores->any())
                    <div class="alert alert-danger mb-0" role="alert">
                        @foreach ($errores->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="conteo-caja">
                    @foreach ($billetes as $billete)
                        <label class="conteo-renglon" for="billete{{ $billete }}">
                            <span class="conteo-nombre">Billetes de ${{ $billete }}</span>
                            <input type="number" id="billete{{ $billete }}" name="billetes[{{ $billete }}]" min="0" max="9999" step="1"
                                   inputmode="numeric" value="{{ $conteo[$billete] ?? '' }}" placeholder="0"
                                   class="form-control input-app conteo-piezas" data-valor="{{ $billete }}">
                            <span class="conteo-importe" data-importe-de="{{ $billete }}">$0.00</span>
                        </label>
                    @endforeach
                    <label class="conteo-renglon" for="monedas">
                        <span class="conteo-nombre">Monedas (total en pesos)</span>
                        <input type="number" id="monedas" name="monedas" min="0" step="0.01" inputmode="decimal"
                               value="{{ old('monedas') }}" placeholder="0.00" class="form-control input-app conteo-monedas">
                        <span class="conteo-importe" data-importe-de="monedas">$0.00</span>
                    </label>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <span class="fw-bold text-dark">Fondo total</span>
                    <span class="summary-total fs-4" id="fondoTotal">$0.00</span>
                </div>

                {{-- Sólo si el fondo no cuadra --}}
                <div class="d-none" id="bloqueAutorizacionTurno"><div class="d-flex flex-column gap-3">
                    <div class="notice notice-danger mb-0">
                        <div class="notice-text" id="avisoFondo">Tu caja no empieza con el fondo autorizado: un superior tiene que autorizarlo.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="turnoCorreo" class="field-label-app">Correo del gerente o administrador</label>
                            <input type="email" id="turnoCorreo" name="correo_autoriza" maxlength="120" value="{{ old('correo_autoriza') }}"
                                   class="form-control input-app" placeholder="gerente@correo.com">
                        </div>
                        <div class="col-sm-6">
                            <label for="turnoContrasena" class="field-label-app">Contraseña</label>
                            <input type="password" id="turnoContrasena" name="contrasena_autoriza" maxlength="255"
                                   class="form-control input-app" placeholder="Ingresa la contraseña" autocomplete="new-password">
                        </div>
                    </div>
                    <div>
                        <label for="turnoObservaciones" class="field-label-app">Observaciones</label>
                        <textarea id="turnoObservaciones" name="observaciones" rows="2" maxlength="500" class="form-control input-app"
                                  placeholder="Qué pasó y cómo se resolvió">{{ old('observaciones') }}</textarea>
                    </div>
                </div></div>
            </div>

            <div class="modal-footer justify-content-between gap-3">
                <button type="submit" form="formSalirSinTurno" class="btn-text">Cerrar sesión</button>
                <button type="submit" class="btn-app btn-app-primary" id="botonAbrirTurno">Abrir turno</button>
            </div>
        </form>
    </div>
</div>
{{-- "Cerrar sesión" sin abrir turno (formulario aparte: no puede ir dentro del de arriba) --}}
<form method="post" action="{{ route('logout') }}" id="formSalirSinTurno" class="d-none">@csrf</form>
