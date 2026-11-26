<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/*
    Las pantallas con menú lateral (Inicio, Ventas, Clientes, Productos, Alertas...) son las
    MISMAS para el administrador y el gerente; cada uno entra por su carpeta:
        /administrador/ventas   (rutas "administrador.ventas.index")
        /gerente/ventas         (rutas "gerente.ventas.index")

    Por eso las vistas y controladores no escriben "administrador." a mano, sino:
        Ruta::a('ventas.index')          → la de la carpeta en la que se está
        Ruta::a('ventas.ticket', $venta)
        Ruta::existe('alertas.index')    → ¿esa pantalla existe para este rol?

    Lo que cada quien puede hacer lo deciden los permisos de su rol (tabla rolPermisos).

    El CAJERO tiene sus propias pantallas (barra superior), pero reusa algunas ventanas de Ventas
    (detalle, abono, devolución, cancelar). Sus rutas se llaman igual con "cajero." al inicio,
    así esas ventanas encuentran la suya: Ruta::a('ventas.abono', $venta) → cajero.ventas.abono.
*/
class Ruta
{
    public const CARPETAS = ['ADMINISTRADOR' => 'administrador', 'GERENTE' => 'gerente', 'CAJERO' => 'cajero'];

    /* "administrador", "gerente" o "cajero": la de la pantalla actual; si no se sabe, la del rol */
    public static function carpeta(): string
    {
        $actual = request()->route()?->getName();
        $prefijo = $actual ? strstr($actual, '.', true) : false;

        if ($prefijo && in_array($prefijo, self::CARPETAS, true)) {
            return $prefijo;
        }

        return self::CARPETAS[auth()->user()?->nombreRol()] ?? 'administrador';
    }

    public static function a(string $nombre, mixed $parametros = [], bool $absoluta = true): string
    {
        return route(self::carpeta() . '.' . $nombre, $parametros, $absoluta);
    }

    public static function existe(string $nombre): bool
    {
        return Route::has(self::carpeta() . '.' . $nombre);
    }
}
