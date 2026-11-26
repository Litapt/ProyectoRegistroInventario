<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
    Turno de caja de un cajero. Tabla: turnosCaja.
    ABIERTO desde que cuenta su fondo hasta que hace el corte (CERRADO).
    La lógica (abrir, lo esperado, el corte) está en App\Support\Caja.
*/
class TurnoCaja extends Model
{
    public const ABIERTO = 'ABIERTO';
    public const CERRADO = 'CERRADO';

    protected $table = 'turnosCaja';
    protected $primaryKey = 'idTurno';
    public $timestamps = false;

    protected $fillable = [
        'fk_idTrabajador', 'numeroCaja', 'estado', 'fechaApertura', 'fondoInicial', 'conteoApertura',
        'fk_idTrabajadorAutorizaApertura', 'observacionesApertura',
        'fechaCierre', 'conteoCierre', 'efectivoContado', 'tarjetaContado', 'transferenciaContado',
        'efectivoEsperado', 'tarjetaEsperado', 'transferenciaEsperado', 'diferencia',
        'fk_idTrabajadorAutorizaCierre', 'observacionesCierre',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fechaApertura ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'fechaApertura' => 'datetime',
            'fechaCierre' => 'datetime',
            'fondoInicial' => 'decimal:2',
            'conteoApertura' => 'array',
            'conteoCierre' => 'array',
            'efectivoContado' => 'decimal:2',
            'tarjetaContado' => 'decimal:2',
            'transferenciaContado' => 'decimal:2',
            'efectivoEsperado' => 'decimal:2',
            'tarjetaEsperado' => 'decimal:2',
            'transferenciaEsperado' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->where('estado', self::ABIERTO);
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajador', 'idTrabajador');
    }

    public function autorizoApertura(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajadorAutorizaApertura', 'idTrabajador');
    }

    public function autorizoCierre(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'fk_idTrabajadorAutorizaCierre', 'idTrabajador');
    }

    /* "Caja 01 · Turno #44" */
    public function nombre(): string
    {
        return 'Caja ' . str_pad((string) $this->numeroCaja, 2, '0', STR_PAD_LEFT) . ' · Turno #' . $this->idTurno;
    }

    /* Lo que la pagosVenta.referencia guarda para saber en qué turno se cobró */
    public function referencia(): string
    {
        return 'TURNO-' . $this->idTurno;
    }
}
