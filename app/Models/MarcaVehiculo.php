<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/* Marca de vehículo: Nissan, Chevrolet... Tabla: marcasVehiculo (llave idMarcaVehiculo) */
class MarcaVehiculo extends Model
{
    protected $table = 'marcasVehiculo';
    protected $primaryKey = 'idMarcaVehiculo';
    public $timestamps = false;

    protected $fillable = ['nombre'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function modelos(): HasMany
    {
        return $this->hasMany(ModeloVehiculo::class, 'fk_idMarcaVehiculo', 'idMarcaVehiculo')->orderBy('nombre');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('nombre');
    }
}
