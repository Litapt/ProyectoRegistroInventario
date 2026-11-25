<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/* Modelo de vehículo: Versa (Nissan), Aveo (Chevrolet)... Tabla: modelosVehiculo (llave idModeloVehiculo) */
class ModeloVehiculo extends Model
{
    protected $table = 'modelosVehiculo';
    protected $primaryKey = 'idModeloVehiculo';
    public $timestamps = false;

    protected $fillable = ['fk_idMarcaVehiculo', 'nombre'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(MarcaVehiculo::class, 'fk_idMarcaVehiculo', 'idMarcaVehiculo');
    }

    /* "Nissan Versa" */
    public function nombreCompleto(): string
    {
        return trim(($this->marca?->nombre ?? '') . ' ' . $this->nombre);
    }
}
