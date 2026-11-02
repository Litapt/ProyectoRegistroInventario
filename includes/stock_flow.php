<?php
// Los dos "flujos" de movimiento de stock comparten la búsqueda y el detalle de la pieza:
//   - entrada: registroEntrada.php → detallePieza.php → entradaStock.php
//   - salida:  registroSalida.php  → detallePieza.php → salidaStock.php
// Aquí están los textos y páginas que cambian entre uno y otro.

/**
 * Regresa la configuración del flujo pedido ('entrada' o 'salida').
 * Cualquier otro valor se toma como 'entrada'.
 */
function getStockFlow(string $mode): array
{
    $flows = [
        'entrada' => [
            'mode'           => 'entrada',
            'searchPage'     => 'registroEntrada.php',
            'movementPage'   => 'entradaStock.php',
            'pageTitle'      => 'Registro de entrada',
            'searchTitle'    => 'Registro de entrada - Paso uno.',
            'detailSubtitle' => 'Consulta la información de la pieza seleccionada antes de agregar stock.',
            'detailButton'   => 'Agregar stock',
            'detailIcon'     => 'icon-plus-white-small.svg',
            'detailNote'     => 'Esta vista es de consulta. Para registrar una entrada de esta pieza, selecciona Agregar stock.',
        ],
        'salida' => [
            'mode'           => 'salida',
            'searchPage'     => 'registroSalida.php',
            'movementPage'   => 'salidaStock.php',
            'pageTitle'      => 'Registro de salida',
            'searchTitle'    => 'Registro de salida - Paso uno.',
            'detailSubtitle' => 'Consulta la información de la pieza seleccionada antes de registrar su salida.',
            'detailButton'   => 'Registrar salida',
            'detailIcon'     => 'icon-stock-out.svg',
            'detailNote'     => 'Esta vista es de consulta. Para registrar una salida de esta pieza, selecciona Registrar salida.',
        ],
    ];

    return $flows[$mode] ?? $flows['entrada'];
}

/**
 * Lee el flujo desde la dirección (?mode=salida). Solo acepta 'entrada' o 'salida'.
 */
function readFlowMode(array $input): string
{
    return ($input['mode'] ?? '') === 'salida' ? 'salida' : 'entrada';
}
