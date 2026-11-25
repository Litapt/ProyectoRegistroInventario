<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
    Historial de existencias: cada vez que el stock de un producto sube o baja
    queda un renglón. Tabla: movimientosInventario (llave idMovimientoInventario)
*/
class MovimientoInventario extends Model
{
    public const ENTRADA = 'ENTRADA';
    public const SALIDA = 'SALIDA';
    public const VENTA = 'VENTA';
    public const CANCELACION_VENTA = 'CANCELACION_VENTA';
    public const DEVOLUCION = 'DEVOLUCION';
    public const CONTEO = 'CONTEO';
    public const INCIDENCIA = 'INCIDENCIA';

    protected $table = 'movimientosInventario';
    protected $primaryKey = 'idMovimientoInventario';
    public $timestamps = false;

    protected $fillable = [
        'fk_idProducto', 'tipoMovimiento', 'cantidad', 'existenciaAnterior', 'existenciaNueva',
        'costoUnitario', 'fk_idTrabajador', 'referenciaTipo', 'referenciaId', 'fk_idMotivo', 'observaciones',
    ];

    /*
        La fecha la pone Laravel (zona horaria de config/app.php) y no MySQL,
        para que todas las fechas del sistema usen la misma hora.
    */
    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fechaMovimiento ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'existenciaAnterior' => 'integer',
            'existenciaNueva' => 'integer',
            'costoUnitario' => 'decimal:2',
            'fechaMovimiento' => 'datetime',
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

    /* "Entrada", "Salida", "Venta"... para mostrar */
    public function tipoTexto(): string
    {
        return ucfirst(mb_strtolower(str_replace('_', ' ', $this->tipoMovimiento)));
    }

    /* ¿Sumó piezas? (para pintar + o −) */
    public function sumo(): bool
    {
        return $this->existenciaNueva >= $this->existenciaAnterior;
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajador', 'idTrabajador');
    }
}
