<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/* Categoría de producto: Frenos, Motor... Tabla: categorias (llave idCategoria) */
class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'idCategoria';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /* Categoria::activas()->get() */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('nombre');
    }
}
