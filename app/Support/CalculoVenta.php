<?php

namespace App\Support;

use App\Models\Configuracion;
use App\Models\Producto;

/*
    Las cuentas de una venta o cotización, en UN solo lugar.
    (javascript/administrador/ventas.js hace exactamente las mismas cuentas para mostrarlas
    en pantalla; si cambias algo aquí, cámbialo allá también.)

    Reglas:
      - Los precios de los productos son SIN IVA.
      - El descuento es un porcentaje sobre el subtotal y se aplica ANTES del IVA
        (así el IVA se cobra sobre lo que de verdad paga el cliente, como en una factura).
      - El descuento se reparte entre los renglones; el centavo que sobre por redondeo
        se le pone al último renglón, para que la suma cuadre exacta.
      - Todo se cuenta en CENTAVOS (números enteros) para no tener errores de decimales.
*/
class CalculoVenta
{
    /**
     * @param  array<int, array{producto: Producto, cantidad: int}>  $renglones
     * @param  float  $porcentajeDescuento  de 0 a 100
     * @return array{renglones: array, subtotal: float, descuento: float, iva: float, total: float, piezas: int}
     */
    public static function calcular(array $renglones, float $porcentajeDescuento): array
    {
        $ivaGeneral = (float) Configuracion::valor('iva', '16');
        $puntosDescuento = (int) round($porcentajeDescuento * 100); // 12.5 % → 1250

        $resultado = [];
        $subtotalCentavos = 0;
        foreach ($renglones as $renglon) {
            $producto = $renglon['producto'];
            $cantidad = (int) $renglon['cantidad'];
            $precioCentavos = (int) round((float) $producto->precioVentaActual * 100);
            $tasa = $producto->tasaIVA !== null ? (float) $producto->tasaIVA : $ivaGeneral;

            $resultado[] = [
                'producto' => $producto,
                'cantidad' => $cantidad,
                'precioCentavos' => $precioCentavos,
                'subtotalCentavos' => $precioCentavos * $cantidad,
                'tasa' => $tasa,
            ];
            $subtotalCentavos += $precioCentavos * $cantidad;
        }

        // Descuento total y su reparto por renglón
        $descuentoCentavos = self::porcentaje($subtotalCentavos, $puntosDescuento);
        $repartido = 0;
        foreach ($resultado as $i => &$renglon) {
            $esUltimo = $i === array_key_last($resultado);
            $renglon['descuentoCentavos'] = $esUltimo
                ? $descuentoCentavos - $repartido
                : self::porcentaje($renglon['subtotalCentavos'], $puntosDescuento);
            $repartido += $renglon['descuentoCentavos'];
        }
        unset($renglon);

        // IVA de cada renglón sobre lo que queda después del descuento
        $ivaCentavos = 0;
        foreach ($resultado as &$renglon) {
            $base = $renglon['subtotalCentavos'] - $renglon['descuentoCentavos'];
            $renglon['ivaCentavos'] = self::porcentaje($base, (int) round($renglon['tasa'] * 100));
            $renglon['totalCentavos'] = $base + $renglon['ivaCentavos'];
            $ivaCentavos += $renglon['ivaCentavos'];
        }
        unset($renglon);

        return [
            'renglones' => array_map(fn ($r) => [
                'producto' => $r['producto'],
                'cantidad' => $r['cantidad'],
                'precio' => $r['precioCentavos'] / 100.0,
                'subtotal' => $r['subtotalCentavos'] / 100.0,
                'descuento' => $r['descuentoCentavos'] / 100.0,
                'tasa' => $r['tasa'],
                'iva' => $r['ivaCentavos'] / 100.0,
                'total' => $r['totalCentavos'] / 100.0,
            ], $resultado),
            'subtotal' => $subtotalCentavos / 100.0,
            'descuento' => $descuentoCentavos / 100.0,
            'iva' => $ivaCentavos / 100.0,
            'total' => ($subtotalCentavos - $descuentoCentavos + $ivaCentavos) / 100.0,
            'piezas' => array_sum(array_column($resultado, 'cantidad')),
        ];
    }

    /* centavos × (puntos / 10 000), redondeado al centavo (0.5 sube). 1600 puntos = 16 % */
    private static function porcentaje(int $centavos, int $puntos): int
    {
        return intdiv($centavos * $puntos * 2 + 10000, 20000);
    }
}
