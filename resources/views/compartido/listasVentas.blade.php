{{--
    Listas de la hoja "Ventas del día" y del comprobante del corte de caja:
    ventas, canceladas, abonos recibidos y devoluciones (datos de App\Support\ReporteVentas).
--}}
@php
    $dinero = fn ($n) => \App\Models\Producto::formatoPrecio($n);
@endphp
    {{-- Ventas --}}
    <p class="negrita" style="margin: 0 0 4px">Ventas ({{ $completadas->count() }})</p>
    <table>
        <thead>
            <tr><th>Folio</th><th>Hora</th><th class="derecha">Total</th></tr>
        </thead>
        <tbody>
            @forelse ($completadas as $venta)
                <tr>
                    <td>
                        <span style="white-space: nowrap">{{ $venta->folio }}</span>
                        <div class="tenue">{{ $venta->nombreCliente() }} · {{ $venta->pagos->isEmpty() ? 'Sin pago' : $venta->metodosPagoTexto() }}</div>
                        @if ($venta->tieneSaldo())
                            <div class="tenue">Debe {{ $dinero($venta->saldo()) }}</div>
                        @endif
                    </td>
                    <td style="white-space: nowrap">{{ $venta->fechaVenta->format('h:i a') }}</td>
                    <td class="derecha">{{ $dinero($venta->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="centro tenue">No hubo ventas este día.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($canceladas->isNotEmpty())
        <p class="negrita" style="margin: 12px 0 4px">Canceladas ({{ $canceladas->count() }})</p>
        <table>
            <tbody>
                @foreach ($canceladas as $venta)
                    <tr>
                        <td>
                            {{ $venta->folio }}
                            <div class="tenue">{{ $venta->motivoDeCancelacion?->nombre }}</div>
                        </td>
                        <td class="derecha" style="text-decoration: line-through">{{ $dinero($venta->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($abonos->isNotEmpty())
        <p class="negrita" style="margin: 12px 0 4px">Abonos recibidos ({{ $abonos->count() }})</p>
        <table>
            <tbody>
                @foreach ($abonos as $abono)
                    <tr>
                        <td>{{ $abono->venta?->folio }} <div class="tenue">{{ $abono->metodo?->nombre }} · {{ $abono->fechaPago->format('h:i a') }}</div></td>
                        <td class="derecha">{{ $dinero($abono->monto) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($devoluciones->isNotEmpty())
        <p class="negrita" style="margin: 12px 0 4px">Devoluciones ({{ $devoluciones->count() }})</p>
        <table>
            <tbody>
                @foreach ($devoluciones as $devolucion)
                    <tr>
                        <td>{{ $devolucion->folio }} <div class="tenue">{{ $devolucion->cantidad }} × {{ $devolucion->producto?->nombre }} · venta {{ $devolucion->venta?->folio }}</div></td>
                        <td class="derecha">−{{ $dinero($devolucion->reembolso()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

