<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/*
    Una venta (lo que se cobra en mostrador). Tabla: ventas (llave idVenta)
    Sus renglones están en detalleVentas y sus pagos en pagosVenta.
*/
class Venta extends Model
{
    public const COMPLETADA = 'COMPLETADA';
    public const CANCELADA = 'CANCELADA';

    public const CONTADO = 'CONTADO';
    public const CREDITO = 'CREDITO';

    protected $table = 'ventas';
    protected $primaryKey = 'idVenta';
    public $timestamps = false;

    protected $fillable = [
        'folio', 'fk_idCliente', 'fk_idTrabajador', 'estadoVenta', 'condicionPago',
        'subtotal', 'descuentoTotal', 'impuestosTotal', 'total', 'observaciones',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fechaVenta ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'descuentoTotal' => 'decimal:2',
            'impuestosTotal' => 'decimal:2',
            'total' => 'decimal:2',
            'fechaVenta' => 'datetime',
            'fechaCancelacion' => 'datetime',
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'fk_idVenta', 'idVenta')->orderBy('idDetalleVenta');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVenta::class, 'fk_idVenta', 'idVenta');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'fk_idCliente', 'idCliente');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajador', 'idTrabajador');
    }

    /* Ojo: "motivoCancelacion" (sin "De") es la columna con el comentario escrito */
    public function motivoDeCancelacion(): BelongsTo
    {
        return $this->belongsTo(Motivo::class, 'fk_idMotivoCancelacion', 'idMotivo');
    }

    public function trabajadorCancelacion(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajadorCancelacion', 'idTrabajador');
    }

    /* Devoluciones ya hechas de esta venta (una por producto) */
    public function devoluciones(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'fk_idVenta', 'idVenta')
            ->where('tipoSolicitud', Solicitud::DEVOLUCION)->where('estado', Solicitud::APROBADA)
            ->orderBy('idSolicitud');
    }

    /* Piezas que el cliente ya regresó, por producto: [idProducto => cantidad] */
    public function piezasDevueltas(): array
    {
        return $this->devoluciones->groupBy('fk_idProducto')->map(fn ($d) => (int) $d->sum('cantidad'))->all();
    }

    /* Lo que la ventana "Registrar devolución" necesita */
    public function datosParaDevolucion(): array
    {
        $devueltas = $this->piezasDevueltas();

        return [
            'folio' => $this->folio,
            'cliente' => $this->nombreCliente(),
            'renglones' => $this->detalles->map(fn ($d) => [
                'id' => $d->fk_idProducto,
                'nombre' => $d->producto?->nombre,
                'codigo' => $d->producto?->codigoInterno,
                'vendidas' => $d->cantidad,
                'disponibles' => $d->cantidad - ($devueltas[$d->fk_idProducto] ?? 0),
                // Lo que se pagó por pieza (con descuento e IVA), para mostrar el reembolso
                'porPieza' => $d->cantidad > 0 ? round((float) $d->totalLinea / $d->cantidad, 2) : 0,
            ])->filter(fn ($r) => $r['disponibles'] > 0)->values()->all(),
        ];
    }

    /* La solicitud de edición que está esperando respuesta (si hay) */
    public function solicitudPendiente(): HasOne
    {
        return $this->hasOne(Solicitud::class, 'fk_idVenta', 'idVenta')
            ->where('tipoSolicitud', Solicitud::EDITAR_VENTA)->where('estado', Solicitud::PENDIENTE);
    }

    /*
        Lo que la ventana "Editar venta" necesita. Con $pedidos (id => cantidad) y $idCliente
        se arma con lo que se había escrito (cuando el servidor regresa un error).
    */
    public function datosParaEditar(?array $pedidos = null, int|null|false $idCliente = false, ?float $descuento = null): array
    {
        $anteriores = $this->detalles()->with(['producto.marca'])->get()->keyBy('fk_idProducto');
        $pedidos ??= $anteriores->map(fn ($d) => $d->cantidad)->all();
        $productos = Producto::with('marca')->whereKey(array_keys($pedidos))->get()->keyBy('idProducto');
        $ivaGeneral = (float) Configuracion::valor('iva', '16');

        $renglones = [];
        foreach ($pedidos as $id => $cantidad) {
            $producto = $productos->get($id);
            if (!$producto) {
                continue;
            }
            $anterior = $anteriores->get($id);
            $renglones[] = [
                'id' => $producto->idProducto,
                'codigo' => $producto->codigoInterno,
                'nombre' => $producto->nombre,
                'detalle' => $producto->marca?->nombre,
                'precio' => (float) ($anterior?->precioUnitarioVenta ?? $producto->precioVentaActual),
                'tasa' => (float) ($anterior?->tasaIVA ?? $producto->tasaIVA ?? $ivaGeneral),
                'cantidad' => (int) $cantidad,
                // Lo que ya se llevó más lo que queda en el almacén
                'maximo' => $producto->existencia + ($anterior?->cantidad ?? 0),
                'yaEstaba' => (bool) $anterior,
            ];
        }

        $cliente = $idCliente === false ? $this->cliente : ($idCliente ? Cliente::find($idCliente) : null);

        return [
            'folio' => $this->folio,
            'cliente' => $cliente ? [
                'id' => $cliente->idCliente, 'nombre' => $cliente->nombre(),
                'codigo' => $cliente->codigo(), 'telefono' => $cliente->telefono,
            ] : null,
            'descuento' => $descuento ?? \App\Support\EdicionVenta::porcentajeDescuento($this),
            'descuentoAntes' => \App\Support\EdicionVenta::porcentajeDescuento($this),
            'totalAntes' => (float) $this->total,
            'renglones' => $renglones,
        ];
    }

    public function estaCancelada(): bool
    {
        return $this->estadoVenta === self::CANCELADA;
    }

    /* "El cliente ya no la quiere · se llevó otra marca" */
    public function textoCancelacion(): string
    {
        return collect([$this->motivoDeCancelacion?->nombre, $this->getAttribute('motivoCancelacion')])
            ->filter(fn ($t) => is_string($t) && trim($t) !== '')
            ->implode(' · ');
    }

    /* "Efectivo" o "Efectivo + Tarjeta" */
    public function metodosPagoTexto(): string
    {
        return $this->pagos->map(fn ($p) => $p->metodo?->nombre)->filter()->unique()->implode(' + ') ?: '—';
    }

    /* Lo que la ventana "Detalle de la venta" necesita */
    public function datosParaDetalle(): array
    {
        return [
            'folio' => $this->folio,
            'fecha' => $this->fechaVenta?->format('d/m/Y h:i a'),
            'cliente' => $this->nombreCliente(),
            'trabajador' => $this->trabajador?->nombreCompleto(),
            'metodoPago' => $this->metodosPagoTexto(),
            'estado' => $this->estadoVenta,
            'cancelacion' => $this->estaCancelada() ? [
                'fecha' => $this->fechaCancelacion?->format('d/m/Y h:i a'),
                'quien' => $this->trabajadorCancelacion?->nombreCompleto(),
                'motivo' => $this->textoCancelacion(),
            ] : null,
            'devoluciones' => $this->devoluciones->map(fn ($s) => [
                'folio' => $s->folio,
                'fecha' => $s->fechaSolicitud?->format('d/m/Y h:i a'),
                'producto' => $s->producto?->nombre,
                'cantidad' => $s->cantidad,
                'motivo' => collect([$s->motivoDe?->nombre, $s->comentario])->filter()->implode(' · '),
                'reembolso' => Producto::formatoPrecio($s->reembolso()),
                'inventario' => (bool) ($s->datosPropuestos['regresaInventario'] ?? false),
            ])->all(),
            'renglones' => $this->detalles->map(fn ($d) => [
                'codigo' => $d->producto?->codigoInterno,
                'nombre' => $d->producto?->nombre,
                'cantidad' => $d->cantidad,
                'precio' => Producto::formatoPrecio($d->precioUnitarioVenta),
                'importe' => Producto::formatoPrecio($d->subtotal),
            ])->all(),
            'pagos' => $this->pagos->map(fn ($p) => [
                'fecha' => $p->fechaPago?->format('d/m/Y h:i a'),
                'metodo' => $p->metodo?->nombre,
                'monto' => Producto::formatoPrecio($p->monto),
            ])->all(),
            'credito' => $this->esCredito(),
            'pagado' => Producto::formatoPrecio($this->pagado()),
            'saldo' => $this->saldo() >= 0.01 ? Producto::formatoPrecio($this->saldo()) : null,
            'subtotal' => Producto::formatoPrecio($this->subtotal),
            'descuento' => (float) $this->descuentoTotal > 0 ? '−' . Producto::formatoPrecio($this->descuentoTotal) : null,
            'iva' => Producto::formatoPrecio($this->impuestosTotal),
            'total' => Producto::formatoPrecio($this->total),
        ];
    }

    // ---------- Pagos (contado o crédito con abonos) ----------

    public function esCredito(): bool
    {
        return $this->condicionPago === self::CREDITO;
    }

    /* Lo que ya pagó el cliente (suma de pagosVenta) */
    public function pagado(): float
    {
        return round((float) ($this->relationLoaded('pagos') ? $this->pagos->sum('monto') : $this->pagos()->sum('monto')), 2);
    }

    /* Lo que todavía debe (0 si ya liquidó) */
    public function saldo(): float
    {
        return max(0.0, round((float) $this->total - $this->pagado(), 2));
    }

    public function tieneSaldo(): bool
    {
        return !$this->estaCancelada() && $this->saldo() >= 0.01;
    }

    public function nombreCliente(): string
    {
        return $this->cliente?->nombre() ?? 'Público en general';
    }

    public function piezas(): int
    {
        return (int) $this->detalles->sum('cantidad');
    }
}
