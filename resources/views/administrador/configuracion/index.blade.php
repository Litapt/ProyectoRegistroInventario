{{--
    Configuración del administrador (Figma Page 2: "Configuración").
    Cinco secciones, cada una con su propio formulario y su botón "Guardar cambios".
    Los errores de cada sección van en su propia "bolsa" ($errors->tienda, $errors->ventas...),
    así sólo se marca la sección que se intentó guardar.
    Todo lo manda Administrador\ConfiguracionController@index.
--}}
@extends('layouts.panel')

@section('titulo', 'Configuración')
@section('menu', 'configuracion')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/configuracion.css') }}">
@endpush

@section('pagina')
    @php
        $secciones = [
            'tienda' => 'Datos de la tienda',
            'inventario' => 'Inventario',
            'ventas' => 'Ventas',
            'notificaciones' => 'Notificaciones',
            'seguridad' => 'Seguridad',
        ];
        // Si una sección regresó con errores, la página se va directo a ella
        $seccionConError = collect(array_keys($secciones))->first(fn ($s) => $errors->getBag($s)->any());
        $folioActual = old('formatoFolio', $folioVenta?->formato ?? \App\Support\Folios::FORMATOS_VENTA[0]);
    @endphp

    <div class="mb-4 pb-3 border-bottom">
        <h1 class="page-heading m-0">Configuración</h1>
        <p class="page-description m-0 mt-1">Administra los datos y las preferencias de tu tienda.</p>
    </div>

    <div class="row g-4">
        <aside class="col-lg-3">
            <nav class="config-nav" aria-label="Secciones de la configuración" data-ir-a="{{ $seccionConError }}">
                <p class="sidebar-label fw-semibold text-uppercase mb-2">Secciones</p>
                @foreach ($secciones as $clave => $texto)
                    <a href="#{{ $clave }}" class="config-nav-link {{ $loop->first ? 'is-active' : '' }}" data-seccion="{{ $clave }}">{{ $texto }}</a>
                @endforeach
                <p class="field-help mt-3 mb-0">Los cambios se guardan por sección.</p>
            </nav>
        </aside>

        <div class="col-lg-9 d-flex flex-column gap-4">

            {{-- ========== 01 · Datos de la tienda ========== --}}
            <section class="panel config-seccion" id="tienda">
                <form method="post" action="{{ \App\Support\Ruta::a('configuracion.tienda') }}" enctype="multipart/form-data" class="panel-body d-flex flex-column gap-3" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="panel-title m-0">Datos de la tienda</h2>
                            <p class="panel-text m-0 mt-1">Información que aparecerá en tus ventas y cotizaciones.</p>
                        </div>
                        <span class="config-numero">01</span>
                    </div>

                    @include('administrador.configuracion.errores', ['bolsa' => 'tienda'])

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="config-logo d-flex align-items-center justify-content-center">
                                <img src="{{ \App\Support\Tienda::logo() }}" alt="Logo actual" id="vistaLogo">
                            </span>
                            <div>
                                <div class="fw-semibold text-dark">Logo de la tienda</div>
                                <div class="field-help" id="nombreLogo">PNG o JPG · Hasta 2 MB</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @if ($tienda['logo'])
                                <label class="btn-app btn-app-outline mb-0" for="quitarLogo" id="botonQuitarLogo">
                                    <input type="checkbox" class="d-none" id="quitarLogo" name="quitarLogo" value="1"> Quitar logo
                                </label>
                            @endif
                            <label class="btn-app btn-app-outline mb-0" for="logo">Cambiar logo</label>
                            <input type="file" id="logo" name="logo" accept="image/png,image/jpeg" class="d-none">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cfgNombre" class="field-label-app">Nombre de la tienda</label>
                            <input type="text" id="cfgNombre" name="nombre" maxlength="120" required
                                   value="{{ old('nombre', $v('tienda.nombreComercial', config('app.name'))) }}"
                                   class="form-control input-app @error('nombre', 'tienda') is-invalid @enderror" placeholder="Tienda">
                        </div>
                        <div class="col-md-6">
                            <label for="cfgRfc" class="field-label-app">RFC <span class="field-help">(opcional)</span></label>
                            <input type="text" id="cfgRfc" name="rfc" maxlength="13" value="{{ old('rfc', $v('tienda.rfc')) }}"
                                   class="form-control input-app mayusculas @error('rfc', 'tienda') is-invalid @enderror" placeholder="Ingresa el RFC de la tienda">
                        </div>
                        <div class="col-md-6">
                            <label for="cfgDireccion" class="field-label-app">Dirección</label>
                            <input type="text" id="cfgDireccion" name="direccion" maxlength="255" value="{{ old('direccion', $v('tienda.direccion')) }}"
                                   class="form-control input-app @error('direccion', 'tienda') is-invalid @enderror" placeholder="Calle, número, colonia y ciudad">
                        </div>
                        <div class="col-md-6">
                            <label for="cfgTelefono" class="field-label-app">Teléfono</label>
                            <input type="tel" id="cfgTelefono" name="telefono" maxlength="20" value="{{ old('telefono', $v('tienda.telefono')) }}"
                                   class="form-control input-app @error('telefono', 'tienda') is-invalid @enderror" placeholder="613 123 4567">
                        </div>
                        <div class="col-12">
                            <label for="cfgPie" class="field-label-app">Mensaje al pie del ticket <span class="field-help">(opcional)</span></label>
                            <input type="text" id="cfgPie" name="pie" maxlength="200" value="{{ old('pie', $v('tienda.textoComprobante')) }}"
                                   class="form-control input-app @error('pie', 'tienda') is-invalid @enderror" placeholder="¡Gracias por su compra!">
                        </div>
                    </div>
                    <p class="field-help m-0">Estos datos identifican tu negocio ante tus clientes: salen arriba, en el ticket y en las cotizaciones.</p>

                    <div class="config-pie">
                        <button type="submit" class="btn-app btn-app-primary">Guardar cambios</button>
                    </div>
                </form>
            </section>

            {{-- ========== 02 · Inventario ========== --}}
            <section class="panel config-seccion" id="inventario">
                <form method="post" action="{{ \App\Support\Ruta::a('configuracion.inventario') }}" class="panel-body d-flex flex-column gap-3" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="panel-title m-0">Inventario</h2>
                            <p class="panel-text m-0 mt-1">Define los valores que se usarán al registrar productos.</p>
                        </div>
                        <span class="config-numero">02</span>
                    </div>

                    @include('administrador.configuracion.errores', ['bolsa' => 'inventario'])

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cfgStock" class="field-label-app">Stock mínimo por defecto</label>
                            <input type="number" id="cfgStock" name="stockMinimo" min="0" max="99999" step="1" required
                                   value="{{ old('stockMinimo', $v('stockMinimoDefecto', '10')) }}"
                                   class="form-control input-app @error('stockMinimo', 'inventario') is-invalid @enderror">
                            <p class="field-help m-0 mt-1">Se aplicará a los nuevos productos.</p>
                        </div>
                        <div class="col-12">
                            <label for="cfgCategorias" class="field-label-app">Categorías</label>
                            <textarea id="cfgCategorias" name="categorias" rows="2" maxlength="3000" required
                                      class="form-control input-app @error('categorías', 'inventario') is-invalid @enderror"
                                      placeholder="Frenos, filtros, encendido">{{ old('categorias', $categorias) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label for="cfgMarcas" class="field-label-app">Marcas de refacciones</label>
                            <textarea id="cfgMarcas" name="marcas" rows="2" maxlength="3000" required
                                      class="form-control input-app @error('marcas', 'inventario') is-invalid @enderror"
                                      placeholder="Bosch, NGK, Monroe">{{ old('marcas', $marcas) }}</textarea>
                        </div>
                    </div>
                    <p class="field-help m-0">
                        Separa las categorías y marcas con comas. Si quitas una, sólo se desactiva:
                        los productos existentes conservan su configuración y la puedes volver a escribir después.
                    </p>

                    <div class="config-pie">
                        <button type="submit" class="btn-app btn-app-primary">Guardar cambios</button>
                    </div>
                </form>
            </section>

            {{-- ========== 03 · Ventas ========== --}}
            <section class="panel config-seccion" id="ventas">
                <form method="post" action="{{ \App\Support\Ruta::a('configuracion.ventas') }}" class="panel-body d-flex flex-column gap-3" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="panel-title m-0">Ventas</h2>
                            <p class="panel-text m-0 mt-1">Personaliza los impuestos y los datos de tus comprobantes.</p>
                        </div>
                        <span class="config-numero">03</span>
                    </div>

                    @include('administrador.configuracion.errores', ['bolsa' => 'ventas'])

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cfgIva" class="field-label-app">IVA (%)</label>
                            <input type="number" id="cfgIva" name="iva" min="0" max="100" step="0.01" required
                                   value="{{ old('iva', $v('iva', '16')) }}"
                                   class="form-control input-app @error('iva', 'ventas') is-invalid @enderror">
                            <p class="field-help m-0 mt-1">Para productos sin IVA propio. Las ventas que ya existen no cambian.</p>
                        </div>
                        <div class="col-md-6">
                            <span class="field-label-app d-block">Formato de folio</span>
                            <div class="d-flex flex-column gap-2">
                                @foreach (\App\Support\Folios::FORMATOS_VENTA as $formato)
                                    <label class="config-opcion">
                                        <input type="radio" name="formatoFolio" value="{{ $formato }}" @checked($folioActual === $formato)>
                                        <span>
                                            <span class="fw-semibold text-dark">{{ \App\Support\Folios::ejemplo($formato, $siguienteNumero, (int) ($folioVenta->longitudNumero ?? 4)) }}</span>
                                            <span class="field-help d-block">{{ str_contains($formato, '{AAAA}') ? 'Con el año · vuelve a 0001 cada año' : 'Sin año · el número nunca se reinicia' }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="field-help m-0 mt-1">Vista previa: así se verá la siguiente venta.</p>
                        </div>
                        <div class="col-12">
                            <label for="cfgMetodos" class="field-label-app">Métodos de pago</label>
                            <input type="text" id="cfgMetodos" name="metodosPago" maxlength="1000" required
                                   value="{{ old('metodosPago', $metodosPago) }}"
                                   class="form-control input-app @error('métodos de pago', 'ventas') is-invalid @enderror"
                                   placeholder="Efectivo, tarjeta, transferencia">
                            <p class="field-help m-0 mt-1">Separa los métodos de pago con comas.</p>
                        </div>
                    </div>

                    <div class="config-pie">
                        <button type="submit" class="btn-app btn-app-primary">Guardar cambios</button>
                    </div>
                </form>
            </section>

            {{-- ========== 04 · Notificaciones ========== --}}
            <section class="panel config-seccion" id="notificaciones">
                <form method="post" action="{{ \App\Support\Ruta::a('configuracion.notificaciones') }}" class="panel-body d-flex flex-column gap-3" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="panel-title m-0">Notificaciones</h2>
                            <p class="panel-text m-0 mt-1">Elige cómo recibir los avisos de tu inventario.</p>
                        </div>
                        <span class="config-numero">04</span>
                    </div>

                    @include('administrador.configuracion.errores', ['bolsa' => 'notificaciones'])

                    @php
                        $alertas = (string) old('alertasStockBajo', $v('alertasStockBajo', '1'));
                        $porCorreo = (string) old('notificacionesCorreo', $v('notificacionesCorreo', '0'));
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cfgAlertas" class="field-label-app">Alertas de stock bajo</label>
                            <select id="cfgAlertas" name="alertasStockBajo" class="form-select input-app">
                                <option value="1" @selected($alertas === '1')>Activadas</option>
                                <option value="0" @selected($alertas === '0')>Desactivadas</option>
                            </select>
                            <p class="field-help m-0 mt-1">Avisa al llegar al stock mínimo de cada producto.</p>
                        </div>
                        <div class="col-md-6">
                            <label for="cfgPorCorreo" class="field-label-app">Notificaciones por correo</label>
                            <select id="cfgPorCorreo" name="notificacionesCorreo" class="form-select input-app">
                                <option value="1" @selected($porCorreo === '1')>Activadas</option>
                                <option value="0" @selected($porCorreo === '0')>Desactivadas</option>
                            </select>
                            <p class="field-help m-0 mt-1">Envía las alertas al correo configurado.</p>
                        </div>
                        <div class="col-md-6">
                            <label for="cfgCorreo" class="field-label-app">Correo para notificaciones</label>
                            <input type="email" id="cfgCorreo" name="correoNotificaciones" maxlength="120"
                                   value="{{ old('correoNotificaciones', $v('correoNotificaciones')) }}"
                                   class="form-control input-app @error('correoNotificaciones', 'notificaciones') is-invalid @enderror"
                                   placeholder="Ingresa un correo electrónico">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <p class="field-help m-0">
                                Recibirás aquí los avisos de stock bajo. Para que de verdad salgan los correos,
                                el servidor (VPS) necesita configurado un servicio de correo en el <code>.env</code>.
                            </p>
                        </div>
                    </div>

                    <div class="config-pie">
                        <button type="submit" class="btn-app btn-app-primary">Guardar cambios</button>
                    </div>
                </form>
            </section>

            {{-- ========== 05 · Seguridad ========== --}}
            <section class="panel config-seccion" id="seguridad">
                <form method="post" action="{{ \App\Support\Ruta::a('configuracion.seguridad') }}" class="panel-body d-flex flex-column gap-3" novalidate autocomplete="off">
                    @csrf
                    @method('PUT')
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="panel-title m-0">Seguridad</h2>
                            <p class="panel-text m-0 mt-1">Protege tu cuenta y define el tiempo de inactividad permitido.</p>
                        </div>
                        <span class="config-numero">05</span>
                    </div>

                    @include('administrador.configuracion.errores', ['bolsa' => 'seguridad'])

                    @php
                        $minutos = (int) old('minutosCierre', $v('minutosCierreSesion', '15'));
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cfgActual" class="field-label-app">Contraseña actual</label>
                            <input type="password" id="cfgActual" name="contrasenaActual" maxlength="255" autocomplete="current-password"
                                   class="form-control input-app @error('contrasenaActual', 'seguridad') is-invalid @enderror"
                                   placeholder="Ingresa tu contraseña actual">
                        </div>
                        <div class="col-md-6">
                            <label for="cfgNueva" class="field-label-app">Nueva contraseña</label>
                            <input type="password" id="cfgNueva" name="contrasenaNueva" maxlength="255" autocomplete="new-password"
                                   class="form-control input-app @error('contrasenaNueva', 'seguridad') is-invalid @enderror"
                                   placeholder="Crea una nueva contraseña">
                        </div>
                        <div class="col-md-6">
                            <label for="cfgConfirmar" class="field-label-app">Confirmar nueva contraseña</label>
                            <input type="password" id="cfgConfirmar" name="contrasenaNueva_confirmation" maxlength="255" autocomplete="new-password"
                                   class="form-control input-app @error('contrasenaNueva', 'seguridad') is-invalid @enderror"
                                   placeholder="Repite la nueva contraseña">
                            <p class="field-help m-0 mt-1">Usa al menos 8 caracteres, letras y números. Déjalas vacías si no quieres cambiarla.</p>
                        </div>
                        <div class="col-md-6">
                            <label for="cfgMinutos" class="field-label-app">Cierre de sesión automático</label>
                            <select id="cfgMinutos" name="minutosCierre" class="form-select input-app">
                                @foreach ($minutosCierre as $opcion)
                                    <option value="{{ $opcion }}" @selected($minutos === $opcion)>
                                        {{ $opcion === 0 ? 'Nunca' : 'Después de ' . ($opcion >= 60 ? ($opcion / 60) . ($opcion === 60 ? ' hora' : ' horas') : $opcion . ' minutos') }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="field-help m-0 mt-1">La sesión se cerrará si no hay actividad. Aplica a todos los usuarios.</p>
                        </div>
                    </div>

                    <div class="config-pie">
                        <button type="submit" class="btn-app btn-app-primary">Guardar cambios</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('javascript/administrador/configuracion.js') }}"></script>
@endpush
