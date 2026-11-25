<?php

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
    Una refacción del inventario. Tabla: productos (llave idProducto)
    Una sola tienda: la existencia, el stock mínimo y la ubicación viven aquí.
*/
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    // Estados de stock que se ven en la tabla (insignias de colores)
    public const ESTADO_DISPONIBLE = 'disponible';
    public const ESTADO_BAJO = 'bajo';
    public const ESTADO_AGOTADO = 'agotado';

    protected $table = 'productos';
    protected $primaryKey = 'idProducto';
    public $timestamps = false;

    /*
        "existencia" y "activo" NO están a propósito:
        la existencia sólo cambia con ajustes y ventas (queda en movimientosInventario)
        y "activo" sólo con desactivar / reactivar.
    */
    protected $fillable = [
        'codigoInterno', 'codigoBarras', 'nombre', 'descripcion',
        'fk_idCategoria', 'fk_idMarca', 'fk_idUbicacion',
        'numeroParteFabricante', 'precioCompraActual', 'precioVentaActual',
        'tasaIVA', 'stockMinimo', 'stockMaximo', 'imagen',
    ];

    /*
        La fecha la pone Laravel (zona horaria de config/app.php) y no MySQL,
        para que todas las fechas del sistema usen la misma hora.
    */
    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fechaCreacion ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'precioCompraActual' => 'decimal:2',
            'precioVentaActual' => 'decimal:2',
            'tasaIVA' => 'decimal:2',
            'existencia' => 'integer',
            'stockMinimo' => 'integer',
            'stockMaximo' => 'integer',
            'activo' => 'boolean',
            'requiereNumeroSerie' => 'boolean',
            'fechaCreacion' => 'datetime',
            'fechaActualizacion' => 'datetime',
        ];
    }

    // ---------- Relaciones ----------

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'fk_idCategoria', 'idCategoria');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'fk_idMarca', 'idMarca');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'fk_idUbicacion', 'idUbicacion');
    }

    public function compatibilidades(): HasMany
    {
        return $this->hasMany(ProductoCompatibilidad::class, 'fk_idProducto', 'idProducto')
            ->with('modelo.marca')->orderBy('idCompatibilidad');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'fk_idProducto', 'idProducto');
    }

    // ---------- Consultas ----------

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /* Filtra por estado de stock: disponible, bajo o agotado */
    public function scopeConEstado(Builder $query, string $estado): Builder
    {
        $minimo = self::stockMinimoPorDefecto();

        return match ($estado) {
            self::ESTADO_AGOTADO => $query->where('existencia', 0),
            self::ESTADO_BAJO => $query->where('existencia', '>', 0)
                ->whereRaw('existencia <= COALESCE(stockMinimo, ?)', [$minimo]),
            self::ESTADO_DISPONIBLE => $query->whereRaw('existencia > COALESCE(stockMinimo, ?)', [$minimo]),
            default => $query,
        };
    }

    /* Activos que ya llegaron a su stock mínimo (Alertas → Stock); agotados primero */
    public function scopeRequierenAtencion(Builder $query): Builder
    {
        return $query->where('activo', true)
            ->whereRaw('existencia <= COALESCE(stockMinimo, ?)', [self::stockMinimoPorDefecto()]);
    }

    // ---------- Stock ----------

    /* El de Configuración (10 si no hay) */
    public static function stockMinimoPorDefecto(): int
    {
        static $valor = null;

        return $valor ??= (int) Configuracion::valor('stockMinimoDefecto', '10');
    }

    /* El mínimo propio del producto; si no tiene, el de Configuración */
    public function stockMinimoEfectivo(): int
    {
        return $this->stockMinimo ?? self::stockMinimoPorDefecto();
    }

    public function estadoStock(): string
    {
        if ($this->existencia === 0) {
            return self::ESTADO_AGOTADO;
        }

        return $this->existencia <= $this->stockMinimoEfectivo() ? self::ESTADO_BAJO : self::ESTADO_DISPONIBLE;
    }

    // ---------- Para mostrar ----------

    /* 1234.5 → "$1,234.50" */
    public static function formatoPrecio(float|string|null $cantidad): string
    {
        return '$' . number_format((float) $cantidad, 2);
    }

    /* Dirección pública de la foto, o null si no tiene */
    public function urlImagen(): ?string
    {
        return $this->imagen ? asset('imagenes/productos/' . $this->imagen) : null;
    }

    /* Datos que usa el JavaScript para llenar la ventana "Editar producto" */
    public function datosParaEditar(): array
    {
        return [
            'id' => $this->idProducto,
            'codigo' => $this->codigoInterno,
            'nombre' => $this->nombre,
            'categoria' => $this->fk_idCategoria,
            'marca' => $this->fk_idMarca,
            'precioCompra' => $this->precioCompraActual,
            'precioVenta' => $this->precioVentaActual,
            'existencia' => $this->existencia,
            'stockMinimo' => $this->stockMinimo,
            'ubicacion' => $this->ubicacion?->texto() ?? '',
            'imagen' => $this->urlImagen(),
            'vehiculos' => $this->compatibilidades->map(fn ($c) => [
                'modelo' => $c->fk_idModeloVehiculo,
                'texto' => $c->vehiculo(),
                'desde' => $c->anioDesde,
                'hasta' => $c->anioHasta,
            ])->values()->all(),
        ];
    }
}
