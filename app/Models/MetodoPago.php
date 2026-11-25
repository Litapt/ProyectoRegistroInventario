<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/* Efectivo, Tarjeta, Transferencia... Tabla: metodosPago */
class MetodoPago extends Model
{
    protected $table = 'metodosPago';
    protected $primaryKey = 'idMetodoPago';
    public $timestamps = false;

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('idMetodoPago');
    }
}
