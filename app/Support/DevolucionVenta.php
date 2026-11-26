<?php

namespace App\Support;

use App\Models\Bitacora;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
    Devolución: el cliente regresa piezas de una venta.

    - Una devolución = un producto (como dice el plan del equipo: "una solicitud = un producto").
      Se guarda en la tabla solicitudes (tipo DEVOLUCION), ya APROBADA, con quién autorizó.
    - No se pueden devolver más piezas de las que se vendieron (sumando devoluciones anteriores).
    - Reembolso: lo que el cliente pagó por esas piezas (con su descuento e IVA).
      En la última devolución de un producto se usa "lo que falta" para que cuadren los centavos.
    - Si las piezas están bien, regresan al inventario (movimiento DEVOLUCION);
      si llegaron dañadas, no se suman al inventario.
    - La venta no cambia: la devolución queda aparte, ligada a la venta.
*/
class DevolucionVenta
{
    public static function registrar(
        Venta $venta,
        int $idProducto,
        int $cantidad,
        int $idMotivo,
        ?string $comentario,
        bool $regresaInventario,
        Trabajador $quien,
        Trabajador $autorizo,
    ): Solicitud {
        return DB::transaction(function () use ($venta, $idProducto, $cantidad, $idMotivo, $comentario, $regresaInventario, $quien, $autorizo) {
            $venta = Venta::whereKey($venta->idVenta)->lockForUpdate()->firstOrFail();
            if ($venta->estaCancelada()) {
                throw ValidationException::withMessages(['devolucion' => 'Esa venta está cancelada: no se le pueden hacer devoluciones.']);
            }

            if ($venta->tieneSaldo()) {
                throw ValidationException::withMessages(['devolucion' => "La venta {$venta->folio} todavía tiene saldo pendiente ("
                    . Producto::formatoPrecio($venta->saldo()) . '). Para devolver, primero que liquide, o edita la venta.']);
            }

            $renglon = $venta->detalles()->where('fk_idProducto', $idProducto)->first();
            if (!$renglon) {
                throw ValidationException::withMessages(['devolucion' => 'Ese producto no está en la venta.']);
            }

            $anteriores = Solicitud::where('tipoSolicitud', Solicitud::DEVOLUCION)
                ->where('estado', Solicitud::APROBADA)
                ->where('fk_idVenta', $venta->idVenta)->where('fk_idProducto', $idProducto)
                ->get();
            $disponibles = $renglon->cantidad - (int) $anteriores->sum('cantidad');

            if ($cantidad < 1 || $cantidad > $disponibles) {
                throw ValidationException::withMessages(['devolucion' => $disponibles > 0
                    ? "De ese producto sólo se pueden devolver {$disponibles} " . ($disponibles === 1 ? 'pieza' : 'piezas') . '.'
                    : 'De ese producto ya se devolvieron todas las piezas.']);
            }

            // Reembolso: proporcional a lo que se pagó; la última vez, lo que falta (centavos exactos)
            $reembolso = $cantidad === $disponibles
                ? round((float) $renglon->totalLinea - $anteriores->sum(fn ($s) => $s->reembolso()), 2)
                : round((float) $renglon->totalLinea * $cantidad / $renglon->cantidad, 2);

            $solicitud = Solicitud::create([
                'folio' => Folios::siguiente('SOLICITUD'),
                'tipoSolicitud' => Solicitud::DEVOLUCION,
                'fk_idVenta' => $venta->idVenta,
                'fk_idCliente' => $venta->fk_idCliente,
                'fk_idProducto' => $idProducto,
                'cantidad' => $cantidad,
                'fk_idTrabajadorSolicitante' => $quien->idTrabajador,
                'fk_idTrabajadorResponsable' => $autorizo->idTrabajador,
                'estado' => Solicitud::APROBADA,
                'fk_idMotivo' => $idMotivo,
                'comentario' => $comentario !== null && trim($comentario) !== '' ? trim($comentario) : null,
                'datosPropuestos' => [
                    'reembolso' => $reembolso,
                    'regresaInventario' => $regresaInventario,
                    'precioUnitario' => (float) $renglon->precioUnitarioVenta,
                ],
                'fechaResolucion' => now(),
            ]);

            if ($regresaInventario) {
                $producto = Producto::whereKey($idProducto)->lockForUpdate()->firstOrFail();
                $antes = $producto->existencia;
                $producto->existencia = $antes + $cantidad;
                $producto->fechaActualizacion = now();
                $producto->save();

                MovimientoInventario::create([
                    'fk_idProducto' => $producto->idProducto,
                    'tipoMovimiento' => MovimientoInventario::DEVOLUCION,
                    'cantidad' => $cantidad,
                    'existenciaAnterior' => $antes,
                    'existenciaNueva' => $producto->existencia,
                    'costoUnitario' => $producto->precioCompraActual,
                    'fk_idTrabajador' => $quien->idTrabajador,
                    'referenciaTipo' => 'SOLICITUD',
                    'referenciaId' => $solicitud->idSolicitud,
                    'fk_idMotivo' => $idMotivo,
                    'observaciones' => "Devolución {$solicitud->folio} de la venta {$venta->folio}",
                ]);
            }

            Bitacora::registrar('DEVOLUCION', 'ventas', $venta->idVenta, [
                'folio' => $solicitud->folio,
                'venta' => $venta->folio,
                'producto' => $idProducto,
                'cantidad' => $cantidad,
                'reembolso' => $reembolso,
                'regresaInventario' => $regresaInventario,
            ], $autorizo);

            return $solicitud;
        });
    }
}
