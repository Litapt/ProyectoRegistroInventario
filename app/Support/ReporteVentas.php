<?php

namespace App\Support;

use App\Models\PagoVenta;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\TurnoCaja;
use App\Models\Venta;
use Carbon\Carbon;

/*
    "Imprimir ventas del día": todo lo que vendió y cobró un trabajador en un día,
    para entregarlo con el corte de caja. Lo usan el administrador, el gerente y el cajero
    (cada quien imprime SUS ventas) y siempre lo autoriza un gerente o administrador.

      - Ventas que hizo ese día (completadas y, aparte, las canceladas).
      - Lo que cobró ese día, por método de pago (incluye abonos a ventas de otros días).
      - Devoluciones que registró ese día (dinero que regresó).
*/
class ReporteVentas
{
    public static function delDia(Trabajador $trabajador, Carbon $dia): array
    {
        $desde = $dia->copy()->startOfDay();
        $hasta = $dia->copy()->endOfDay();

        return self::delPeriodo($trabajador, $desde, $hasta) + [
            'dia' => $dia,
            'turnos' => TurnoCaja::where('fk_idTrabajador', $trabajador->idTrabajador)->whereBetween('fechaApertura', [$desde, $hasta])->orderBy('idTurno')->get(),
        ];
    }

    /* Lo mismo, entre dos momentos (el corte de caja usa el de su turno: de que abrió a que cerró) */
    public static function delPeriodo(Trabajador $trabajador, Carbon $desde, Carbon $hasta): array
    {

        $ventas = Venta::with(['cliente', 'pagos.metodo', 'motivoDeCancelacion'])
            ->where('fk_idTrabajador', $trabajador->idTrabajador)
            ->whereBetween('fechaVenta', [$desde, $hasta])
            ->orderBy('fechaVenta')->orderBy('idVenta')
            ->get();
        $completadas = $ventas->where('estadoVenta', Venta::COMPLETADA)->values();
        $canceladas = $ventas->where('estadoVenta', Venta::CANCELADA)->values();

        // Lo cobrado ese día (pagos que registró él), por método de pago; los abonos aparte
        $pagos = PagoVenta::with(['metodo', 'venta'])
            ->where('fk_idTrabajador', $trabajador->idTrabajador)
            ->whereBetween('fechaPago', [$desde, $hasta])
            ->whereHas('venta', fn ($v) => $v->where('estadoVenta', Venta::COMPLETADA))
            ->get();
        $porMetodo = $pagos->groupBy(fn ($p) => $p->metodo?->nombre ?? 'Otro')
            ->map(fn ($grupo) => round((float) $grupo->sum('monto'), 2))
            ->sortKeys();
        $abonos = $pagos->filter(fn ($p) => str_starts_with((string) $p->observaciones, 'Abono'))->values();

        $devoluciones = Solicitud::with(['venta', 'producto'])
            ->where('tipoSolicitud', Solicitud::DEVOLUCION)->where('estado', Solicitud::APROBADA)
            ->where('fk_idTrabajadorSolicitante', $trabajador->idTrabajador)
            ->whereBetween('fechaSolicitud', [$desde, $hasta])
            ->orderBy('fechaSolicitud')->get();
        $reembolsado = round((float) $devoluciones->sum(fn ($d) => $d->reembolso()), 2);

        $efectivo = (float) $porMetodo->first(fn ($monto, $metodo) => mb_strtolower($metodo) === 'efectivo', 0);

        return [
            'trabajador' => $trabajador,
            'completadas' => $completadas,
            'canceladas' => $canceladas,
            'abonos' => $abonos,
            'devoluciones' => $devoluciones,
            'totalVendido' => round((float) $completadas->sum('total'), 2),
            'totalCancelado' => round((float) $canceladas->sum('total'), 2),
            'porMetodo' => $porMetodo,
            'totalCobrado' => round((float) $pagos->sum('monto'), 2),
            'pendiente' => round((float) $completadas->sum(fn ($v) => $v->saldo()), 2),
            'reembolsado' => $reembolsado,
            'efectivoNeto' => round($efectivo - $reembolsado, 2),
        ];
    }
}
