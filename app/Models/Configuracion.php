<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
    Ajustes de la tienda guardados como clave → valor (IVA, nombre de la tienda...).
    Tabla: configuraciones (llave idConfiguracion).

    Leer:     Configuracion::valor('iva', '16')
    Guardar:  Configuracion::guardar('iva', '16', $trabajador)
*/
class Configuracion extends Model
{
    protected $table = 'configuraciones';
    protected $primaryKey = 'idConfiguracion';
    public $timestamps = false;

    protected $fillable = ['clave', 'valor', 'descripcion'];

    protected function casts(): array
    {
        return ['fechaActualizacion' => 'datetime'];
    }

    /* Guarda (o crea) una clave con quién y cuándo la cambió */
    public static function guardar(string $clave, ?string $valor, ?Trabajador $quien = null): void
    {
        $fila = static::firstOrNew(['clave' => $clave]);
        $fila->valor = $valor;
        $fila->fechaActualizacion = now();
        $fila->fk_idTrabajadorActualizacion = $quien?->idTrabajador;
        $fila->save();
    }

    /* Valor de una clave; si no existe o está vacía regresa $porDefecto */
    public static function valor(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = static::where('clave', $clave)->value('valor');

        return ($valor === null || $valor === '') ? $porDefecto : $valor;
    }
}
