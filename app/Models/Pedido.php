<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
    Pedido o cotización (tipoPedido). Tabla: pedidos.
    Una cotización NO descuenta inventario: sólo guarda precios para el cliente.
*/
class Pedido extends Model
{
    public const PEDIDO = 'PEDIDO';
    public const COTIZACION = 'COTIZACION';

    public const PENDIENTE = 'PENDIENTE';

    // Días que vale una cotización
    public const DIAS_VIGENCIA = 15;

    protected $table = 'pedidos';
    protected $primaryKey = 'idPedido';
    public $timestamps = false;

    protected $fillable = [
        'folio', 'tipoPedido', 'fk_idCliente', 'fk_idTrabajador', 'fechaEntrega', 'vigencia',
        'estadoPedido', 'subtotal', 'descuentoTotal', 'impuestosTotal', 'total', 'observaciones',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fechaPedido ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'descuentoTotal' => 'decimal:2',
            'impuestosTotal' => 'decimal:2',
            'total' => 'decimal:2',
            'fechaPedido' => 'datetime',
            'fechaEntrega' => 'date',
            'vigencia' => 'date',
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'fk_idPedido', 'idPedido')->orderBy('idDetallePedido');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'fk_idCliente', 'idCliente');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajador', 'idTrabajador');
    }
}
