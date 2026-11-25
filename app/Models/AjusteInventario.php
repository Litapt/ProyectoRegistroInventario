<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
    Un ajuste de stock de UN producto: entrada, salida (egreso) o conteo.
    Tabla: ajustesInventario (llave idAjuste).
    Cuando se aplica, además queda un renglón en movimientosInventario.
*/
class AjusteInventario extends Model
{
    public const ENTRADA = 'ENTRADA';
    public const SALIDA = 'SALIDA';
    public const CONTEO = 'CONTEO';

    public const PENDIENTE = 'PENDIENTE';
    public const APLICADO = 'APLICADO';
    public const RECHAZADO = 'RECHAZADO';
    public const CANCELADO = 'CANCELADO';

    protected $table = 'ajustesInventario';
    protected $primaryKey = 'idAjuste';
    public $timestamps = false;

    protected $fillable = [
        'folio', 'tipoAjuste', 'fk_idProducto', 'cantidad', 'existenciaSistema', 'existenciaContada',
        'diferencia', 'costoUnitario', 'fk_idTrabajador', 'estado', 'fk_idMotivo', 'referencia',
        'observaciones', 'fk_idTrabajadorAutoriza', 'fechaAutorizacion',
    ];

    /*
        La fecha la pone Laravel (zona horaria de config/app.php) y no MySQL,
        para que todas las fechas del sistema usen la misma hora.
    */
    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fecha ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'existenciaSistema' => 'integer',
            'existenciaContada' => 'integer',
            'diferencia' => 'integer',
            'costoUnitario' => 'decimal:2',
            'fecha' => 'datetime',
            'fechaAutorizacion' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'fk_idProducto', 'idProducto');
    }

    public function motivo(): BelongsTo
    {
        return $this->belongsTo(Motivo::class, 'fk_idMotivo', 'idMotivo');
    }
}
