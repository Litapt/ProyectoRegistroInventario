<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
    Un vehículo en el que sirve un producto: "Nissan Versa · 2012–2019".
    Tabla: productosCompatibilidades (llave idCompatibilidad).
    La marca y el modelo vienen de modelosVehiculo; los años se guardan aquí.
*/
class ProductoCompatibilidad extends Model
{
    protected $table = 'productosCompatibilidades';
    protected $primaryKey = 'idCompatibilidad';
    public $timestamps = false;

    protected $fillable = ['fk_idModeloVehiculo', 'anioDesde', 'anioHasta', 'motor', 'notas'];

    protected function casts(): array
    {
        return ['anioDesde' => 'integer', 'anioHasta' => 'integer', 'fk_idModeloVehiculo' => 'integer'];
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloVehiculo::class, 'fk_idModeloVehiculo', 'idModeloVehiculo');
    }

    /* "Nissan Versa" */
    public function vehiculo(): string
    {
        return $this->modelo?->nombreCompleto() ?? '';
    }

    /* "2012–2019", o "2012 en adelante" si no tiene año final */
    public function anios(): string
    {
        if ($this->anioHasta === null) {
            return $this->anioDesde . ' en adelante';
        }

        return $this->anioDesde === $this->anioHasta
            ? (string) $this->anioDesde
            : $this->anioDesde . '–' . $this->anioHasta;
    }
}
