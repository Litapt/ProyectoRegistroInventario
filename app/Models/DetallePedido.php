<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/* Un renglón de un pedido o cotización. Tabla: detallePedidos */
class DetallePedido extends Model
{
    protected $table = 'detallePedidos';
    protected $primaryKey = 'idDetallePedido';
    public $timestamps = false;

    protected $fillable = [
        'fk_idPedido', 'fk_idProducto', 'cantidad', 'precioUnitario',
        'descuento', 'tasaIVA', 'subtotal', 'totalLinea',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precioUnitario' => 'decimal:2',
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
