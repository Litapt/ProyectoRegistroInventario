<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
    Un renglón de una venta. Guarda el precio y el IVA DE ESE MOMENTO:
    si después cambia el precio del producto, la venta no cambia.
    Tabla: detalleVentas
*/
class DetalleVenta extends Model
{
    protected $table = 'detalleVentas';
    protected $primaryKey = 'idDetalleVenta';
    public $timestamps = false;

    protected $fillable = [
        'fk_idVenta', 'fk_idProducto', 'cantidad', 'precioUnitarioVenta',
        'descuento', 'tasaIVA', 'subtotal', 'totalLinea',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precioUnitarioVenta' => 'decimal:2',
            'descuento' => 'decimal:2',
            'tasaIVA' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'totalLinea' => 'decimal:2',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'fk_idProducto', 'idProducto');
    }
}
