<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/*
    Motivos de catálogo: de ajuste, cancelación, incidencia, desactivación, devolución.
    Tabla: motivos (llave idMotivo). Motivo::de('AJUSTE')->get()
*/
class Motivo extends Model
{
    public const AJUSTE = 'AJUSTE';
    public const CANCELACION = 'CANCELACION';
    public const INCIDENCIA = 'INCIDENCIA';
    public const DESACTIVACION = 'DESACTIVACION';
    public const DEVOLUCION = 'DEVOLUCION';

    protected $table = 'motivos';
    protected $primaryKey = 'idMotivo';
    public $timestamps = false;

    protected $fillable = ['nombre', 'categoriaMotivo', 'descripcion'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /* Los motivos activos de una categoría, en orden */
    public function scopeDe(Builder $query, string $categoria): Builder
    {
        return $query->where('categoriaMotivo', $categoria)->where('activo', true)->orderBy('idMotivo');
    }
}
