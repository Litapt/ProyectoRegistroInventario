{{--
    Cajero · "Hacer corte de caja" (Figma: "Cajero - Corte de caja"). Lo manda Cajero\CorteController.

      - Conteo de efectivo (billetes y monedas) y otros métodos de pago (tarjeta, transferencia...).
      - Resumen: lo contado, lo esperado y la diferencia (javascript/cajero/corte.js lo calcula en pantalla;
        el servidor lo vuelve a calcular: App\Support\Caja).
      - Si falta o sobra dinero: "Autorización de un superior" (gerente o administrador) y observaciones.
      - "Guardar corte" cierra el turno y se imprime solo el comprobante con las ventas del turno.
--}}
@extends('layouts.barraSuperior')

@section('titulo', 'Corte de caja')

@section('navegacion')
    @include('cajero.menu', ['activo' => 'corte'])
@endsection

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/ventas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cajero/cajero.css') }}">
@endpush

@section('pagina')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
        $conteo = old('billetes', []);
        $otrosAnteriores = old('otros', []);
        $pideAutorizacion = $errors->has('corte') && old('correo_autoriza') !== null;
        $centavos = fn ($n) => (int) round($n * 100);
    @endphp

    <a href="{{ route('cajero.inicio') }}" class="volver-enlace mb-3">← Volver a la pantalla principal</a>

    <div class="mb-4">
        <h1 class="page-heading m-0">Corte de caja</h1>
        <p class="page-description m-0 mt-1">{{ $turno->nombre() }} · abierto desde las {{ $turno->fechaApertura->format('h:i a') }} con {{ $dinero($turno->fondoInicial) }}</p>
    </div>

    @if ($errors->has('corte'))
        <div class="alert alert-danger mb-4" role="alert">
            @foreach ($errors->get('corte') as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('cajero.corte.store') }}" id="formCorte" novalidate autocomplete="off"
          data-esperado-efectivo="{{ $centavos($esperado['efectivo']) }}">
        @csrf
        <div class="row g-4 align-items-stretch">
            {{-- ---------- Conteo de efectivo ---------- --}}
            <div class="col-xl-8">
                <section class="panel h-100">
                    <div class="panel-body d-flex flex-column gap-3">
                        <div>
                            <h2 class="panel-title m-0">Conteo de efectivo</h2>
                            <p class="panel-text m-0 mt-1">Realiza un conteo del dinero físico que hay en la caja (con el fondo).</p>
                        </div>
                        <div class="conteo-corte">
                            @foreach ($billetes as $billete)
                                <label class="conteo-corte-item" for="corteBillete{{ $billete }}">
                                    <span class="conteo-nombre">Billetes de ${{ $billete }}</span>
                                    <span class="d-flex align-items-center gap-2">
                                        <input type="number" id="corteBillete{{ $billete }}" name="billetes[{{ $billete }}]" min="0" max="9999" step="1"
                                               inputmode="numeric" value="{{ $conteo[$billete] ?? '' }}" placeholder="0"
                                               class="form-control input-app conteo-piezas" data-valor="{{ $billete }}">
                                        <span class="conteo-importe" data-importe-de="{{ $billete }}">$0.00</span>
                                    </span>
                                </label>
                            @endforeach
                            <label class="conteo-corte-item" for="corteMonedas">
                                <span class="conteo-nombre">Monedas</span>
                                <span class="input-group">
                                    <span class="input-group-text input-prefix">$</span>
                                    <input type="number" id="corteMonedas" name="monedas" min="0" step="0.01" inputmode="decimal"
                                           value="{{ old('monedas') }}" placeholder="0.00" class="form-control input-app conteo-monedas">
                                </span>
                            </label>
                        </div>
                        <div class="tarjeta-gris d-flex align-items-center justify-content-between">
                            <span class="fw-semibold text-dark">Total de efectivo en el conteo</span>
                            <span class="summary-total fs-4" id="corteEfectivo">$0.00</span>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ---------- Otros métodos de pago ---------- --}}
            <div class="col-xl-4">
                <section class="panel h-100">
                    <div class="panel-body d-flex flex-column gap-3">
                        <div>
                            <h2 class="panel-title m-0">Otros métodos de pago</h2>
                            <p class="panel-text m-0 mt-1">Lo que entró por cada uno (vouchers de la terminal, comprobantes de transferencia).</p>
                        </div>
                        @forelse ($esperado['otros'] as $metodo)
                            <div>
                                <label for="corteOtro{{ $metodo['id'] }}" class="field-label-app">{{ $metodo['nombre'] }}</label>
                                <div class="input-group">
                                    <span class="input-group-text input-prefix">$</span>
                                    <input type="number" id="corteOtro{{ $metodo['id'] }}" name="otros[{{ $metodo['id'] }}]" min="0" step="0.01" inputmode="decimal"
                                           value="{{ $otrosAnteriores[$metodo['id']] ?? '' }}" placeholder="0.00"
                                           class="form-control input-app corte-otro" data-nombre="{{ $metodo['nombre'] }}"
                                           data-esperado="{{ $centavos($metodo['esperado']) }}">
                                </div>
                            </div>
                        @empty
                            <p class="panel-text m-0">Sólo se cobra en efectivo.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- ---------- Resumen del corte ---------- --}}
            <div class="col-12">
                <section class="panel">
                    <div class="panel-body d-flex flex-column gap-3">
                        <div>
                            <h2 class="panel-title m-0">Resumen del corte</h2>
                            <p class="panel-text m-0 mt-1">{{ $turno->nombre() }} · {{ $esperado['ventas'] }} {{ $esperado['ventas'] === 1 ? 'venta cobrada' : 'ventas cobradas' }}</p>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table tabla-corte">
                                <thead>
                                    <tr>
                                        <th>Método</th>
                                        <th class="text-end">Contado</th>
                                        <th class="text-end">Esperado</th>
                                        <th class="text-end">Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody id="corteRenglones">
                                    <tr data-metodo="efectivo">
                                        <td>
                                            Efectivo
                                            <div class="cell-sub">Fondo {{ $dinero($esperado['fondo']) }} + cobrado {{ $dinero($esperado['cobradoEfectivo']) }}{{ $esperado['reembolsos'] > 0 ? ' − devoluciones ' . $dinero($esperado['reembolsos']) : '' }}</div>
                                        </td>
                                        <td class="text-end" data-contado>$0.00</td>
                                        <td class="text-end">{{ $dinero($esperado['efectivo']) }}</td>
                                        <td class="text-end fw-semibold" data-diferencia>—</td>
                                    </tr>
                                    @foreach ($esperado['otros'] as $metodo)
                                        <tr data-metodo="{{ $metodo['id'] }}">
                                            <td>{{ $metodo['nombre'] }}</td>
                                            <td class="text-end" data-contado>$0.00</td>
                                            <td class="text-end">{{ $dinero($metodo['esperado']) }}</td>
                                            <td class="text-end fw-semibold" data-diferencia>—</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="resumen-corte">
                                    <span class="field-help">Corte total (contado)</span>
                                    <span class="resumen-corte-valor text-primary-app" id="corteTotal">$0.00</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="resumen-corte">
                                    <span class="field-help">Corte esperado</span>
                                    <span class="resumen-corte-valor text-primary-app">{{ $dinero($esperado['efectivo'] + collect($esperado['otros'])->sum('esperado')) }}</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="resumen-corte" id="corteDiferenciaCaja">
                                    <span class="field-help" id="corteDiferenciaTexto">Diferencia</span>
                                    <span class="resumen-corte-valor" id="corteDiferencia">$0.00</span>
                                </div>
                            </div>
                        </div>

                        {{-- Si falta o sobra: autoriza un superior --}}
                        <div class="{{ $pideAutorizacion ? '' : 'd-none' }}" id="bloqueAutorizacionCorte">
                            <div class="d-flex flex-column gap-3 pt-3 border-top">
                                @include('cajero.autorizacion', ['prefijo' => 'corte', 'titulo' => 'Autorización de un superior',
                                    'ayuda' => 'Si falta o sobra dinero, un gerente o administrador autoriza el corte.', 'idAyuda' => 'ayudaAutorizacionCorte'])
                                <div>
                                    <label for="corteObservaciones" class="field-label-app">Observaciones</label>
                                    <textarea id="corteObservaciones" name="observaciones" rows="2" maxlength="500" class="form-control input-app"
                                              placeholder="Qué pasó con la diferencia">{{ old('observaciones') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2">
                            <button type="button" class="btn-app btn-app-soft" id="botonAutorizacionCorte">Autorización de un superior</button>
                            <a href="{{ route('cajero.inicio') }}" class="btn-app btn-app-outline">Cancelar</a>
                            <button type="submit" class="btn-app btn-app-primary ms-sm-auto" id="botonGuardarCorte">Guardar corte</button>
                        </div>
                        <p class="field-help m-0">Al guardar, el turno se cierra y se imprime el comprobante con todas tus ventas del turno.</p>
                    </div>
                </section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('javascript/cajero/corte.js') }}"></script>
@endpush
