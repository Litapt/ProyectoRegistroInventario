<?php
// Funciones compartidas para mostrar datos en pantalla con el mismo formato.

/**
 * Convierte un número a pesos, por ejemplo 185 → "$185.00"
 */
function formatMoney(float $amount): string
{
    return '$' . number_format($amount, 2);
}
