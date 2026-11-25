<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
    Una solicitud que un gerente o administrador debe aceptar (tabla: solicitudes).
    - EDITAR_VENTA: lo que se pide cambiar va en datosPropuestos (JSON):
        { "cliente": 3, "descuento": 10, "productos": [{"id": 5, "cantidad": 2}], "antes": {...} }
    - DEVOLUCION: un producto (fk_idProducto, cantidad, fk_idMotivo) que el cliente regresó;
      datosPropuestos: { "reembolso": 98.60, "regresaInventario": true }
    - INCIDENCIA: un producto con un problema (dañado, defectuoso, diferencia de inventario...).
      motivo = la descripción; prioridad ALTA/MEDIA/BAJA; estado PENDIENTE → EN_REVISION → RESUELTA;
      comentario = cómo se resolvió; datosPropuestos: { "piezasDadasDeBaja": 2 }
*/
class Solicitud extends Model
{
    public const EDITAR_VENTA = 'EDITAR_VENTA';
    public const DEVOLUCION = 'DEVOLUCION';
    public const INCIDENCIA = 'INCIDENCIA';

    public const PENDIENTE = 'PENDIENTE';
    public const APROBADA = 'APROBADA';
    public const RECHAZADA = 'RECHAZADA';
    public const EN_REVISION = 'EN_REVISION';   // incidencias
    public const RESUELTA = 'RESUELTA';         // incidencias

    public const PRIORIDADES = ['ALTA' => 'Alta', 'MEDIA' => 'Media', 'BAJA' => 'Baja'];

    protected $table = 'solicitudes';
    protected $primaryKey = 'idSolicitud';
    public $timestamps = false;

    protected $fillable = [
        'folio', 'tipoSolicitud', 'fk_idCliente', 'fk_idVenta', 'fk_idProducto', 'cantidad',
        'fk_idTrabajadorSolicitante', 'fk_idTrabajadorResponsable', 'estado', 'prioridad',
        'fk_idMotivo', 'motivo', 'comentario', 'datosPropuestos', 'fechaResolucion',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fechaSolicitud ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'datosPropuestos' => 'array',
            'fechaSolicitud' => 'datetime',
            'fechaResolucion' => 'datetime',
        ];
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::PENDIENTE);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'fk_idVenta', 'idVenta');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'fk_idProducto', 'idProducto');
    }

    public function motivoDe(): BelongsTo
    {
        return $this->belongsTo(Motivo::class, 'fk_idMotivo', 'idMotivo');
    }

    public function reembolso(): float
    {
        return (float) ($this->datosPropuestos['reembolso'] ?? 0);
    }

    /* Lo que la ventana "Detalle de la incidencia" necesita */
    public function datosIncidencia(): array
    {
        return [
            'folio' => $this->folio,
            'estado' => $this->estado,
            'fecha' => $this->fechaSolicitud?->format('d/m/Y h:i a'),
            'tipo' => $this->motivoDe?->nombre,
            'prioridad' => self::PRIORIDADES[$this->prioridad] ?? '—',
            'producto' => $this->producto?->nombre,
            'codigo' => $this->producto?->codigoInterno,
            'existencia' => $this->producto?->existencia ?? 0,
            'cantidad' => $this->cantidad,
            'descripcion' => $this->motivo,
            'reporto' => $this->solicitante?->nombreCompleto(),
            'responsable' => $this->responsable?->nombreCompleto(),
            'resolucion' => $this->comentario,
            'fechaResolucion' => $this->fechaResolucion?->format('d/m/Y h:i a'),
            'piezasDeBaja' => (int) ($this->datosPropuestos['piezasDadasDeBaja'] ?? 0),
        ];
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajadorSolicitante', 'idTrabajador');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajadorResponsable', 'idTrabajador');
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }
}
