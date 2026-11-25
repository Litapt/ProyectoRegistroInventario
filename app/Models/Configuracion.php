<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
    Ajustes de la tienda guardados como clave → valor (IVA, nombre de la tienda...).
    Tabla: configuraciones (llave idConfiguracion).

    Leer:     Configuracion::valor('iva', '16')
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

    /* Valor de una clave; si no existe o está vacía regresa $porDefecto */
    public static function valor(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = static::where('clave', $clave)->value('valor');

        return ($valor === null || $valor === '') ? $porDefecto : $valor;
    }
}
