{{--
    Inicio del administrador (Figma Page 2: "Administrador · Inicio").
    Lo manda Administrador\InicioController; los números salen de App\Support\Tablero.
    La gráfica es sólo HTML y CSS (barras), sin librerías.
--}}
@extends('layouts.panel')

@section('titulo', 'Inicio')
@section('menu', 'inicio')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/administrador/inicio.css') }}">
@endpush

@section('pagina')
    @php
        $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
        $esHoy = $dia->isToday();
        $cambio = function (?float $valor, string $sufijo = '%') {
            if ($valor === null) {
                return ['—', 'text-muted'];
            }
            return [($valor > 0 ? '+' : '') . rtrim(rtrim(number_format($valor, 1), '0'), '.') . $sufijo, $valor >= 0 ? 'cambio-sube' : 'cambio-baja'];
        };
        [$textoVendido, $claseVendido] = $cambio($cambioVendido);
        [$textoSemana, $claseSemana] = $cambio($cambioSemana);
        $primerDia = $semana[0]['fecha'];
    @endphp

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-heading m-0">Inicio</h1>
            <p class="page-description m-0 mt-1">
                {{ $esHoy ? 'Así va tu refaccionaria hoy.' : 'Así le fue a tu refaccionaria el ' . $dia->translatedFormat('j \d\e F \d\e Y') . '.' }}
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <form method="get" action="{{ route('administrador.inicio') }}" id="formDia">
                <label for="dia" class="visually-hidden">Día</label>
                <input type="date" id="dia" name="dia" value="{{ $dia->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                       class="form-control input-app" onchange="this.form.submit()">
            </form>
            @if (auth()->user()->puede('ventas.registrar'))
                <a href="{{ route('administrador.ventas.index') }}" class="btn-app btn-app-primary">Nueva venta</a>
            @endif
        </div>
    </div>

    {{-- Tarjetas --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="panel tarjeta-dato">
                <div class="d-flex justify-content-between"><span class="dato-titulo">Ventas {{ $esHoy ? 'de hoy' : 'del día' }}</span>
                    <span class="dato-icono"><span class="icono" style="--icono: url('{{ asset('imagenes/icon-cash.svg') }}')"></span></span></div>
                <div class="dato-valor">{{ $dinero($vendidoHoy) }}</div>
                <div class="dato-pie"><span class="{{ $claseVendido }}">{{ $textoVendido }}</span> vs. día anterior</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel tarjeta-dato">
                <div class="d-flex justify-content-between"><span class="dato-titulo">Productos activos</span>
                    <span class="dato-icono"><span class="icono" style="--icono: url('{{ asset('imagenes/icon-box.svg') }}')"></span></span></div>
                <div class="dato-valor">{{ number_format($productosActivos) }}</div>
                <div class="dato-pie"><span class="cambio-sube">+{{ $productosNuevosMes }}</span> este mes</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel tarjeta-dato">
                <div class="d-flex justify-content-between"><span class="dato-titulo">Ventas realizadas</span>
                    <span class="dato-icono"><span class="icono" style="--icono: url('{{ asset('imagenes/icon-cart.svg') }}')"></span></span></div>
                <div class="dato-valor">{{ number_format($ventasHoy) }}</div>
                <div class="dato-pie">
                    <span class="{{ $cambioVentas >= 0 ? 'cambio-sube' : 'cambio-baja' }}">{{ $cambioVentas > 0 ? '+' : '' }}{{ $cambioVentas }}</span> vs. día anterior
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel tarjeta-dato">
                <div class="d-flex justify-content-between"><span class="dato-titulo">Productos por reponer</span>
                    <span class="dato-icono dato-icono-alerta"><span class="icono" style="--icono: url('{{ asset('imagenes/icon-alert-triangle.svg') }}')"></span></span></div>
                <div class="dato-valor">{{ number_format($porReponer) }}</div>
                <div class="dato-pie {{ $porReponer > 0 ? 'texto-alerta' : 'text-muted' }}">{{ $porReponer > 0 ? 'Requieren atención' : 'Todo en orden' }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Ventas de la semana --}}
        <div class="col-xl-8">
            <section class="panel h-100">
                <div class="panel-body">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="panel-title m-0">Ventas de la semana</h2>
                            <p class="panel-text m-0 mt-1">
                                Del {{ $primerDia->translatedFormat($primerDia->month === $dia->month ? 'j' : 'j \d\e F') }}
                                al {{ $dia->translatedFormat('j \d\e F') }}
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="semana-total">{{ $dinero($totalSemana) }}</div>
                            <div class="small text-muted">
                                @if ($cambioSemana === null)
                                    Sin ventas la semana anterior para comparar
                                @else
                                    <span class="{{ $claseSemana }}">{{ $textoSemana }}</span> respecto a la semana anterior
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grafica" role="img" aria-label="Ventas de los últimos 7 días">
                        @foreach ($semana as $d)
                            @php
                                $alto = $maximoSemana > 0 ? max(2, round($d['total'] / $maximoSemana * 100)) : 2;
                                $ultimo = $loop->last;
                            @endphp
                            <div class="grafica-columna" title="{{ $d['etiqueta'] }}: {{ $dinero($d['total']) }}">
                                <div class="grafica-monto">{{ $d['total'] > 0 ? $dinero($d['total']) : '' }}</div>
                                <div class="grafica-barra-caja">
                                    <div class="grafica-barra {{ $ultimo ? 'es-actual' : '' }}" style="height: {{ $alto }}%"></div>
                                </div>
                                <div class="grafica-etiqueta {{ $ultimo ? 'es-actual' : '' }}">{{ $d['etiqueta'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        {{-- Atención al inventario --}}
        <div class="col-xl-4">
            <section class="panel h-100">
                <div class="panel-body d-flex flex-column gap-3 h-100">
                    <div>
                        <h2 class="panel-title m-0">Atención al inventario</h2>
                        <p class="panel-text m-0 mt-1">Mantén tus productos disponibles.</p>
                    </div>
                    <div class="aviso-inventario aviso-bajo">
                        <div class="fw-semibold text-dark">{{ $bajos }} {{ $bajos === 1 ? 'producto' : 'productos' }} con stock bajo</div>
                        <div class="small text-muted">Planea tu próxima reposición.</div>
                    </div>
                    <div class="aviso-inventario aviso-agotado">
                        <div class="fw-semibold text-dark">{{ $agotados }} {{ $agotados === 1 ? 'producto agotado' : 'productos agotados' }}</div>
                        <div class="small text-muted">Repón las piezas más solicitadas.</div>
                    </div>
                    @if (\Illuminate\Support\Facades\Route::has('administrador.alertas.index') && auth()->user()->puede('solicitudes.autorizar'))
                        <a href="{{ route('administrador.alertas.index') }}" class="btn-text mt-auto">Ver todas las alertas →</a>
                    @endif
                </div>
            </section>
        </div>
    </div>

    {{-- Productos por reponer --}}
    <section class="panel">
        <div class="panel-body d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h2 class="panel-title m-0">Productos por reponer</h2>
                <p class="panel-text m-0 mt-1">Revisa existencias y mantén tu inventario al día.</p>
            </div>
            <form method="get" action="{{ route('administrador.inicio') }}" class="d-flex align-items-center gap-3">
                @if (!$esHoy)
                    <input type="hidden" name="dia" value="{{ $dia->format('Y-m-d') }}">
                @endif
                <input type="search" name="buscar" value="{{ $buscar }}" maxlength="60" class="form-control input-app" placeholder="Buscar producto o código" aria-label="Buscar producto o código">
                @if (auth()->user()->puede('productos.consultar'))
                    <a href="{{ route('administrador.productos.index') }}" class="btn-text text-nowrap">Ver productos</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Código</th>
                        <th>Existencias</th>
                        <th>Mínimo</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        @php
                            $vehiculo = $producto->compatibilidades->first();
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-main">{{ $producto->nombre }}</div>
                                <div class="cell-sub">{{ $producto->categoria?->nombre }}{{ $vehiculo ? ' · ' . $vehiculo->vehiculo() : '' }}</div>
                            </td>
                            <td class="text-muted">{{ $producto->codigoInterno }}</td>
                            <td class="{{ $producto->existencia === 0 ? 'text-danger fw-semibold' : 'fw-semibold text-dark' }}">{{ $producto->existencia }} pzas</td>
                            <td class="text-muted">{{ $producto->stockMinimoEfectivo() }} pzas</td>
                            <td>
                                @if ($producto->existencia === 0)
                                    <span class="badge-estado badge-danger">Agotado</span>
                                @else
                                    <span class="badge-estado badge-warning">Stock bajo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (auth()->user()->puede('inventario.ajustar'))
                                    <a href="{{ route('administrador.productos.ajuste', $producto) }}" class="btn-app btn-app-outline btn-app-sm">Ajustar stock</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                {{ $buscar !== '' ? 'Ningún producto por reponer coincide con «' . $buscar . '».' : 'Ningún producto necesita reponerse. 🎉' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $productos, 'nombre' => 'productos que requieren atención'])
    </section>
@endsection
