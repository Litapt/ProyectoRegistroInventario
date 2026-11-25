<?php

namespace App\Support;

use App\Models\Bitacora;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
    Incidencias de inventario (Figma: "Alertas · Incidencias" y "Alertas de incidencias").
    Se guardan en solicitudes (tipo INCIDENCIA) con folio INC-0001.
      PENDIENTE (Abierta) → EN_REVISION → RESUELTA
    Al resolver se pueden dar de baja del inventario las piezas que ya no sirven
    (movimiento INCIDENCIA), por ejemplo si estaban dañadas.
*/
class Incidencias
{
    public static function registrar(int $idProducto, int $idMotivo, int $cantidad, string $prioridad, string $descripcion, Trabajador $quien): Solicitud
    {
        return DB::transaction(function () use ($idProducto, $idMotivo, $cantidad, $prioridad, $descripcion, $quien) {
            $incidencia = Solicitud::create([
                'folio' => Folios::siguiente('INCIDENCIA'),
                'tipoSolicitud' => Solicitud::INCIDENCIA,
                'fk_idProducto' => $idProducto,
                'cantidad' => $cantidad,
                'fk_idTrabajadorSolicitante' => $quien->idTrabajador,
                'estado' => Solicitud::PENDIENTE,
                'prioridad' => $prioridad,
                'fk_idMotivo' => $idMotivo,
                'motivo' => $descripcion,
            ]);

            Bitacora::registrar('CREAR', 'solicitudes', $incidencia->idSolicitud, [
                'folio' => $incidencia->folio, 'tipo' => Solicitud::INCIDENCIA, 'producto' => $idProducto,
            ]);

            return $incidencia;
        });
    }

    public static function enRevision(Solicitud $incidencia, Trabajador $quien): Solicitud
    {
        return DB::transaction(function () use ($incidencia, $quien) {
            $incidencia = self::bloqueada($incidencia);
            if ($incidencia->estado !== Solicitud::PENDIENTE) {
                throw ValidationException::withMessages(['incidencia' => "La incidencia {$incidencia->folio} ya no está abierta."]);
            }

            $incidencia->estado = Solicitud::EN_REVISION;
            $incidencia->fk_idTrabajadorResponsable = $quien->idTrabajador;
            $incidencia->save();
            Bitacora::registrar('EN_REVISION', 'solicitudes', $incidencia->idSolicitud, ['folio' => $incidencia->folio]);

            return $incidencia;
        });
    }

    /* Resolver: con $piezasDeBaja > 0 se quitan del inventario (no se pueden quitar más de las que hay) */
    public static function resolver(Solicitud $incidencia, string $comentario, int $piezasDeBaja, Trabajador $quien): Solicitud
    {
        return DB::transaction(function () use ($incidencia, $comentario, $piezasDeBaja, $quien) {
            $incidencia = self::bloqueada($incidencia);
            if ($incidencia->estado === Solicitud::RESUELTA) {
                throw ValidationException::withMessages(['incidencia' => "La incidencia {$incidencia->folio} ya estaba resuelta."]);
            }

            if ($piezasDeBaja > 0) {
                $producto = Producto::whereKey($incidencia->fk_idProducto)->lockForUpdate()->firstOrFail();
                if ($piezasDeBaja > $producto->existencia) {
                    throw ValidationException::withMessages(['incidencia' => "Sólo hay {$producto->existencia} piezas de «{$producto->nombre}» en inventario."]);
                }

                $antes = $producto->existencia;
                $producto->existencia = $antes - $piezasDeBaja;
                $producto->fechaActualizacion = now();
                $producto->save();

                MovimientoInventario::create([
                    'fk_idProducto' => $producto->idProducto,
                    'tipoMovimiento' => MovimientoInventario::INCIDENCIA,
                    'cantidad' => $piezasDeBaja,
                    'existenciaAnterior' => $antes,
                    'existenciaNueva' => $producto->existencia,
                    'costoUnitario' => $producto->precioCompraActual,
                    'fk_idTrabajador' => $quien->idTrabajador,
                    'referenciaTipo' => 'SOLICITUD',
                    'referenciaId' => $incidencia->idSolicitud,
                    'fk_idMotivo' => $incidencia->fk_idMotivo,
                    'observaciones' => "Baja por la incidencia {$incidencia->folio}",
                ]);
            }

            $incidencia->estado = Solicitud::RESUELTA;
            $incidencia->fk_idTrabajadorResponsable = $quien->idTrabajador;
            $incidencia->fechaResolucion = now();
            $incidencia->comentario = trim($comentario);
            $incidencia->datosPropuestos = ['piezasDadasDeBaja' => $piezasDeBaja];
            $incidencia->save();

            Bitacora::registrar('RESOLVER', 'solicitudes', $incidencia->idSolicitud, [
                'folio' => $incidencia->folio, 'piezasDadasDeBaja' => $piezasDeBaja,
            ]);

            return $incidencia;
        });
    }

    private static function bloqueada(Solicitud $incidencia): Solicitud
    {
        $incidencia = Solicitud::whereKey($incidencia->idSolicitud)->lockForUpdate()->firstOrFail();
        abort_unless($incidencia->tipoSolicitud === Solicitud::INCIDENCIA, 404);

        return $incidencia;
    }
}
