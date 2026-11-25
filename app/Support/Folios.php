<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/*
    Da el siguiente folio de un tipo de documento, según la tabla folios.
        Folios::siguiente('AJUSTE')  →  "AJ-2026-0001"
        Folios::siguiente('VENTA')   →  "V-2026-0048" (o el formato que elija el administrador)

    {AAAA} se cambia por el año y {0000} por el número con ceros a la izquierda.
    Si el formato lleva {AAAA}, el número vuelve a 1 cada año.

    IMPORTANTE: llamarlo DENTRO de DB::transaction(). Bloquea el renglón del folio
    hasta que termine la transacción, así dos personas nunca reciben el mismo número.
*/
class Folios
{
    /* Los 3 formatos de folio de venta que aparecen en el Figma; el administrador elige uno */
    public const FORMATOS_VENTA = [
        'VENTA-{AAAA}-{0000}',
        'V-{AAAA}-{0000}',
        'Venta #{0000}',
    ];

    /* Dónde se guarda cada tipo de folio (para no repetir uno que ya existe) */
    private const TABLAS = [
        'VENTA' => 'ventas',
        'PEDIDO' => 'pedidos',
        'COTIZACION' => 'pedidos',
        'AJUSTE' => 'ajustesInventario',
        'SOLICITUD' => 'solicitudes',
    ];

    /* Cómo se vería un formato con un número (para la "Vista previa") */
    public static function ejemplo(string $formato, int $numero = 1, int $longitud = 4): string
    {
        return strtr($formato, [
            '{AAAA}' => (string) now()->year,
            '{0000}' => str_pad((string) $numero, max(1, $longitud), '0', STR_PAD_LEFT),
        ]);
    }

    public static function siguiente(string $tipoDocumento): string
    {
        $folio = DB::table('folios')
            ->where('tipoDocumento', $tipoDocumento)
            ->where('activo', true)
            ->lockForUpdate()
            ->first();

        if (!$folio) {
            throw new RuntimeException("No hay folio configurado para {$tipoDocumento}.");
        }

        $anio = (int) now()->year;
        $reiniciaCadaAnio = str_contains($folio->formato, '{AAAA}');

        $numero = ($reiniciaCadaAnio && (int) $folio->anio !== $anio) ? 1 : $folio->ultimoNumero + 1;

        // Si el administrador cambió de formato y regresó a uno anterior, un folio podría
        // repetirse: se salta hasta encontrar uno libre
        $tabla = self::TABLAS[$tipoDocumento] ?? null;
        $armar = fn (int $n) => self::ejemplo($folio->formato, $n, (int) $folio->longitudNumero);
        while ($tabla && DB::table($tabla)->where('folio', $armar($numero))->exists()) {
            $numero++;
        }

        DB::table('folios')->where('idFolio', $folio->idFolio)->update([
            'ultimoNumero' => $numero,
            'anio' => $anio,
        ]);

        return $armar($numero);
    }
}
