<?php
// Funciones relacionadas con las alertas de stock bajo.

/**
 * Cuenta cuántos productos tienen stock bajo.
 *
 * Por ahora siempre regresa 0 porque todavía no existe la tabla de productos.
 * Cuando exista, aquí irá una consulta con PDO, por ejemplo:
 *   SELECT COUNT(*) FROM products WHERE stock <= min_stock
 */
function countLowStockAlerts(): int
{
    // TODO: consultar la base de datos cuando exista la tabla de productos
    return 0;
}
