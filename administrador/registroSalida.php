<?php
// Registro de salida de producto – Paso uno: buscar la pieza.
// Se llega aquí con el botón "Registrar salida de producto" de panelExistencias.php.
//
// Es la misma pantalla de búsqueda que registroEntrada.php; solo cambian el título
// y a dónde llevan las filas. Por eso aquí solo elegimos el flujo "salida" y
// reutilizamos ese archivo en lugar de copiarlo.

$flowMode = 'salida';
require __DIR__ . '/registroEntrada.php';
