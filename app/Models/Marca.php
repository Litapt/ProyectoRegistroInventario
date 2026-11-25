<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/* Marca de producto: Bosch, NGK... Tabla: marcas (llave idMarca) */
class Marca extends Model
{
    protected $table = 'marcas';
    protected $primaryKey = 'idMarca';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /* Marca::activas()->get() */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('nombre');
    }
}
