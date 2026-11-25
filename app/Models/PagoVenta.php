<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/* Un pago (o abono, si es a crédito) de una venta o pedido. Tabla: pagosVenta */
class PagoVenta extends Model
{
    protected $table = 'pagosVenta';
    protected $primaryKey = 'idPagoVenta';
    public $timestamps = false;

    protected $fillable = [
        'fk_idVenta', 'fk_idPedido', 'fk_idMetodoPago', 'monto', 'moneda', 'tipoCambio',
        'fk_idTrabajador', 'referencia', 'observaciones',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fechaPago ??= now();
        });
    }

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'fechaPago' => 'datetime'];
    }

    public function metodo(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'fk_idMetodoPago', 'idMetodoPago');
    }
}
