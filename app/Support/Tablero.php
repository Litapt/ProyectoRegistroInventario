<?php

namespace App\Support;

use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Carbon;

/*
    Números del Inicio (Figma Page 2: "Administrador · Inicio"). Lo usan el administrador
    y, más adelante, el gerente. Sólo cuentan ventas COMPLETADAS (las canceladas no).
*/
class Tablero
{
    public static function datos(Carbon $dia): array
    {
        $dia = $dia->copy()->startOfDay();
        $ayer = $dia->copy()->subDay();

        $vendidoHoy = self::vendido($dia, $dia);
        $vendidoAyer = self::vendido($ayer, $ayer);
        $ventasHoy = self::cuantas($dia, $dia);
        $ventasAyer = self::cuantas($ayer, $ayer);

        // La semana: los 7 días que terminan en el día elegido
        $semana = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $dia->copy()->subDays($i);
            $semana[] = [
                'fecha' => $d,
                'etiqueta' => $i === 0 && $d->isToday() ? 'Hoy' : ucfirst($d->translatedFormat('D j')),
                'total' => self::vendido($d, $d),
            ];
        }
        $totalSemana = array_sum(array_column($semana, 'total'));
        $semanaAnterior = self::vendido($dia->copy()->subDays(13), $dia->copy()->subDays(7));

        $agotados = Producto::requierenAtencion()->where('existencia', 0)->count();
        $porReponer = Producto::requierenAtencion()->count();

        return [
            'dia' => $dia,
            'vendidoHoy' => $vendidoHoy,
            'cambioVendido' => self::porcentaje($vendidoHoy, $vendidoAyer),
            'ventasHoy' => $ventasHoy,
            'cambioVentas' => $ventasHoy - $ventasAyer,
            'productosActivos' => Producto::where('activo', true)->count(),
            'productosNuevosMes' => Producto::where('activo', true)
                ->whereBetween('fechaCreacion', [$dia->copy()->startOfMonth(), $dia->copy()->endOfDay()])->count(),
            'porReponer' => $porReponer,
            'agotados' => $agotados,
            'bajos' => $porReponer - $agotados,
            'semana' => $semana,
            'maximoSemana' => max(array_column($semana, 'total')) ?: 0,
            'totalSemana' => $totalSemana,
            'cambioSemana' => self::porcentaje($totalSemana, $semanaAnterior),
        ];
    }

    private static function completadas(Carbon $desde, Carbon $hasta)
    {
        return Venta::where('estadoVenta', Venta::COMPLETADA)
            ->whereBetween('fechaVenta', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()]);
    }

    private static function vendido(Carbon $desde, Carbon $hasta): float
    {
        return round((float) self::completadas($desde, $hasta)->sum('total'), 2);
    }

    private static function cuantas(Carbon $desde, Carbon $hasta): int
    {
        return self::completadas($desde, $hasta)->count();
    }

    /* +12.8 (%) respecto al anterior; null si antes no hubo nada (no se puede comparar) */
    private static function porcentaje(float $ahora, float $antes): ?float
    {
        return $antes > 0 ? round(($ahora - $antes) / $antes * 100, 1) : null;
    }
}
