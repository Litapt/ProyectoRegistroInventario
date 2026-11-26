<?php

namespace App\Support;

use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\DetalleVenta;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
    Editar una venta ya registrada (cliente, productos y cantidades).

    Siempre se hace con una SOLICITUD (tabla solicitudes, tipo EDITAR_VENTA):
      - si un gerente o administrador autoriza en ese momento → se aplica ya
        y la solicitud queda APROBADA (para que quede el historial);
      - si no hay nadie → queda PENDIENTE y se acepta o rechaza después
        (Ventas → pestaña "Solicitudes").

    Precios: los productos que ya estaban conservan el precio con el que se vendieron;
    los que se agregan toman el precio de hoy. El % de descuento también se puede cambiar.
*/
class EdicionVenta
{
    /* Crea la solicitud; con $autorizo, la aplica de una vez */
    public static function solicitar(Venta $venta, array $datos, string $motivo, Trabajador $quien, ?Trabajador $autorizo): Solicitud
    {
        return DB::transaction(function () use ($venta, $datos, $motivo, $quien, $autorizo) {
            $venta = self::ventaBloqueada($venta);

            if ($venta->devoluciones()->exists()) {
                throw ValidationException::withMessages([
                    'editar' => "La venta {$venta->folio} ya tiene devoluciones: no se puede editar (las cuentas no cuadrarían).",
                ]);
            }

            if (Solicitud::pendientes()->where('tipoSolicitud', Solicitud::EDITAR_VENTA)->where('fk_idVenta', $venta->idVenta)->exists()) {
                throw ValidationException::withMessages([
                    'editar' => "La venta {$venta->folio} ya tiene una solicitud de edición pendiente. Espera a que la resuelvan.",
                ]);
            }

            $propuesta = [
                'cliente' => $datos['cliente'],
                'descuento' => round((float) $datos['descuento'], 2),
                'productos' => collect($datos['pedidos'])->map(fn ($cantidad, $id) => ['id' => (int) $id, 'cantidad' => (int) $cantidad])->values()->all(),
                'antes' => [
                    'cliente' => $venta->fk_idCliente,
                    'descuento' => self::porcentajeDescuento($venta),
                    'productos' => $venta->detalles()->get()->map(fn ($d) => ['id' => $d->fk_idProducto, 'cantidad' => $d->cantidad])->values()->all(),
                    'total' => (float) $venta->total,
                ],
            ];

            if (self::sinCambios($propuesta)) {
                throw ValidationException::withMessages(['editar' => 'No cambiaste nada de la venta.']);
            }

            // Se revisa desde ahora (stock, productos activos) aunque quede pendiente
            $previa = self::calcular($venta, $datos['pedidos'], false, $propuesta['descuento']);
            $propuesta['total'] = $previa['cuentas']['total'];

            $solicitud = Solicitud::create([
                'folio' => Folios::siguiente('SOLICITUD'),
                'tipoSolicitud' => Solicitud::EDITAR_VENTA,
                'fk_idVenta' => $venta->idVenta,
                'fk_idCliente' => $datos['cliente'],
                'fk_idTrabajadorSolicitante' => $quien->idTrabajador,
                'estado' => Solicitud::PENDIENTE,
                'motivo' => $motivo,
                'datosPropuestos' => $propuesta,
            ]);

            Bitacora::registrar('SOLICITAR', 'solicitudes', $solicitud->idSolicitud, [
                'folio' => $solicitud->folio, 'venta' => $venta->folio, 'motivo' => $motivo,
            ]);

            if ($autorizo) {
                self::aplicar($venta, $solicitud, $autorizo);
            }

            return $solicitud;
        });
    }

    /* Botón "Aceptar" de la pestaña Solicitudes */
    public static function aprobar(Solicitud $solicitud, Trabajador $autorizo): Solicitud
    {
        return DB::transaction(function () use ($solicitud, $autorizo) {
            $solicitud = Solicitud::whereKey($solicitud->idSolicitud)->lockForUpdate()->firstOrFail();
            if (!$solicitud->estaPendiente()) {
                throw ValidationException::withMessages(['solicitud' => "La solicitud {$solicitud->folio} ya se había resuelto."]);
            }

            $venta = self::ventaBloqueada($solicitud->venta);
            if ($venta->devoluciones()->exists()) {
                throw ValidationException::withMessages(['editar' => 'A la venta se le hizo una devolución después de pedir el cambio. Rechaza esta solicitud.']);
            }
            self::aplicar($venta, $solicitud, $autorizo);

            return $solicitud;
        });
    }

    /* Botón "Rechazar": la venta no cambia */
    public static function rechazar(Solicitud $solicitud, Trabajador $quien, ?string $comentario): Solicitud
    {
        return DB::transaction(function () use ($solicitud, $quien, $comentario) {
            $solicitud = Solicitud::whereKey($solicitud->idSolicitud)->lockForUpdate()->firstOrFail();
            if (!$solicitud->estaPendiente()) {
                throw ValidationException::withMessages(['solicitud' => "La solicitud {$solicitud->folio} ya se había resuelto."]);
            }

            $solicitud->estado = Solicitud::RECHAZADA;
            $solicitud->fk_idTrabajadorResponsable = $quien->idTrabajador;
            $solicitud->fechaResolucion = now();
            $solicitud->comentario = $comentario !== null && trim($comentario) !== '' ? trim($comentario) : null;
            $solicitud->save();

            Bitacora::registrar('RECHAZAR', 'solicitudes', $solicitud->idSolicitud, [
                'folio' => $solicitud->folio, 'comentario' => $solicitud->comentario,
            ], $quien);

            return $solicitud;
        });
    }

    /*
        Cuentas de cómo quedaría la venta (sin guardar). Lanza un error si un producto
        nuevo está desactivado o si no alcanza el inventario.
    */
    public static function calcular(Venta $venta, array $pedidos, bool $bloquear = false, ?float $descuento = null): array
    {
        $anteriores = $venta->detalles()->get()->keyBy('fk_idProducto');
        $ids = collect(array_keys($pedidos))->merge($anteriores->keys())->unique()->sort()->values();

        $consulta = Producto::whereKey($ids)->orderBy('idProducto');
        $productos = ($bloquear ? $consulta->lockForUpdate() : $consulta)->get()->keyBy('idProducto');

        $ivaGeneral = (float) Configuracion::valor('iva', '16');
        $errores = [];
        $renglones = [];

        foreach ($pedidos as $id => $cantidad) {
            $producto = $productos->get($id);
            $anterior = $anteriores->get($id);

            if (!$producto || (!$anterior && !$producto->activo)) {
                $errores[] = 'Un producto que agregaste ya no está disponible (se desactivó).';
                continue;
            }

            $extra = $cantidad - ($anterior?->cantidad ?? 0);
            if ($extra > $producto->existencia) {
                $errores[] = "De «{$producto->nombre}» sólo hay {$producto->existencia} más en inventario.";
                continue;
            }

            // Copia sólo para las cuentas: el que ya estaba conserva su precio de venta
            $paraCuentas = clone $producto;
            if ($anterior) {
                $paraCuentas->precioVentaActual = $anterior->precioUnitarioVenta;
                $paraCuentas->tasaIVA = $anterior->tasaIVA;
            } elseif ($paraCuentas->tasaIVA === null) {
                $paraCuentas->tasaIVA = $ivaGeneral;
            }

            $renglones[] = ['producto' => $paraCuentas, 'cantidad' => (int) $cantidad];
        }

        if ($errores) {
            throw ValidationException::withMessages(['editar' => array_values(array_unique($errores))]);
        }

        return [
            'cuentas' => CalculoVenta::calcular($renglones, $descuento ?? self::porcentajeDescuento($venta)),
            'productos' => $productos,
            'anteriores' => $anteriores,
        ];
    }

    /* El % de descuento que tenía la venta (se guardó en pesos) */
    public static function porcentajeDescuento(Venta $venta): float
    {
        return (float) $venta->subtotal > 0
            ? round((float) $venta->descuentoTotal / (float) $venta->subtotal * 100, 2)
            : 0.0;
    }

    // ---------------------------------------------------------------------

    private static function ventaBloqueada(?Venta $venta): Venta
    {
        $venta = $venta ? Venta::whereKey($venta->idVenta)->lockForUpdate()->first() : null;

        if (!$venta || $venta->estaCancelada()) {
            throw ValidationException::withMessages(['editar' => 'Esa venta está cancelada: ya no se puede editar.']);
        }

        return $venta;
    }

    private static function sinCambios(array $propuesta): bool
    {
        $ordenar = fn ($lista) => collect($lista)->sortBy('id')->map(fn ($r) => $r['id'] . 'x' . $r['cantidad'])->implode(',');

        return (int) $propuesta['cliente'] === (int) $propuesta['antes']['cliente']
            && abs($propuesta['descuento'] - $propuesta['antes']['descuento']) < 0.005
            && $ordenar($propuesta['productos']) === $ordenar($propuesta['antes']['productos']);
    }

    /* Cambia la venta de verdad: inventario, renglones, totales, pago */
    private static function aplicar(Venta $venta, Solicitud $solicitud, Trabajador $autorizo): void
    {
        $propuesta = $solicitud->datosPropuestos;
        $pedidos = collect($propuesta['productos'])->mapWithKeys(fn ($r) => [(int) $r['id'] => (int) $r['cantidad']])->all();

        if ($propuesta['cliente'] && !Cliente::whereKey($propuesta['cliente'])->where('activo', true)->exists()) {
            throw ValidationException::withMessages(['editar' => 'El cliente elegido se desactivó. Rechaza la solicitud o pide una nueva.']);
        }

        // Las solicitudes viejas (sin "descuento") conservan el de la venta
        $descuento = isset($propuesta['descuento']) ? (float) $propuesta['descuento'] : null;
        ['cuentas' => $cuentas, 'productos' => $productos, 'anteriores' => $anteriores] = self::calcular($venta, $pedidos, true, $descuento);
        $quien = auth()->user() ?? $autorizo;
        $totalAntes = (float) $venta->total;

        // Inventario: sólo la diferencia de cada producto
        foreach ($productos as $id => $producto) {
            $diferencia = ($pedidos[$id] ?? 0) - ($anteriores->get($id)?->cantidad ?? 0);
            if ($diferencia === 0) {
                continue;
            }

            $antes = $producto->existencia;
            $producto->existencia = $antes - $diferencia;
            $producto->fechaActualizacion = now();
            $producto->save();

            MovimientoInventario::create([
                'fk_idProducto' => $producto->idProducto,
                'tipoMovimiento' => $diferencia > 0 ? MovimientoInventario::VENTA : MovimientoInventario::CANCELACION_VENTA,
                'cantidad' => abs($diferencia),
                'existenciaAnterior' => $antes,
                'existenciaNueva' => $producto->existencia,
                'costoUnitario' => $producto->precioCompraActual,
                'fk_idTrabajador' => $quien->idTrabajador,
                'referenciaTipo' => 'VENTA',
                'referenciaId' => $venta->idVenta,
                'observaciones' => "Edición de la venta {$venta->folio} (solicitud {$solicitud->folio})",
            ]);
        }

        // Renglones nuevos
        DetalleVenta::where('fk_idVenta', $venta->idVenta)->delete();
        foreach ($cuentas['renglones'] as $r) {
            DetalleVenta::create([
                'fk_idVenta' => $venta->idVenta,
                'fk_idProducto' => $r['producto']->idProducto,
                'cantidad' => $r['cantidad'],
                'precioUnitarioVenta' => $r['precio'],
                'descuento' => $r['descuento'],
                'tasaIVA' => $r['tasa'],
                'subtotal' => $r['subtotal'],
                'totalLinea' => $r['total'],
            ]);
        }

        $venta->fk_idCliente = $propuesta['cliente'];
        $venta->subtotal = $cuentas['subtotal'];
        $venta->descuentoTotal = $cuentas['descuento'];
        $venta->impuestosTotal = $cuentas['iva'];
        $venta->total = $cuentas['total'];
        $venta->save();

        if ($venta->esCredito()) {
            // A crédito los pagos no se tocan: cambia lo que debe. No puede quedar debajo de lo ya pagado.
            if ($cuentas['total'] - $venta->pagado() <= -0.005) {
                throw ValidationException::withMessages(['editar' => 'El nuevo total (' . Producto::formatoPrecio($cuentas['total'])
                    . ') es menor a lo que el cliente ya pagó (' . Producto::formatoPrecio($venta->pagado()) . ').']);
            }
        } else {
            // De contado: el pago se ajusta al nuevo total (tiene un solo pago)
            $pago = $venta->pagos()->orderBy('idPagoVenta')->first();
            if ($pago) {
                $pago->monto = $cuentas['total'];
                $pago->save();
            }
        }

        $solicitud->estado = Solicitud::APROBADA;
        $solicitud->fk_idTrabajadorResponsable = $autorizo->idTrabajador;
        $solicitud->fechaResolucion = now();
        $solicitud->save();

        Bitacora::registrar('EDITAR', 'ventas', $venta->idVenta, [
            'folio' => $venta->folio,
            'solicitud' => $solicitud->folio,
            'totalAntes' => $totalAntes,
            'totalDespues' => $cuentas['total'],
            'antes' => $propuesta['antes'],
            'despues' => ['cliente' => $propuesta['cliente'], 'descuento' => $descuento, 'productos' => $propuesta['productos']],
        ], $autorizo);
    }
}
