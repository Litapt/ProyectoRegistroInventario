<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
    Quién hizo qué y quién lo autorizó. Tabla: bitacora (llave idBitacora)

    Bitacora::registrar('DESACTIVAR', 'productos', $producto->idProducto, ['motivo' => '...'], $autorizo);
*/
class Bitacora extends Model
{
    protected $table = 'bitacora';
    protected $primaryKey = 'idBitacora';
    public $timestamps = false;

    protected $fillable = ['fk_idTrabajador', 'fk_idTrabajadorAutoriza', 'accion', 'tabla', 'idRegistro', 'detalle'];

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
        return ['detalle' => 'array', 'fecha' => 'datetime'];
    }

    /* Guarda un renglón a nombre del trabajador con sesión iniciada */
    public static function registrar(string $accion, string $tabla, ?int $idRegistro, array $detalle = [], ?Trabajador $autorizo = null): self
    {
        return static::create([
            'fk_idTrabajador' => auth()->id(),
            'fk_idTrabajadorAutoriza' => $autorizo?->idTrabajador,
            'accion' => $accion,
            'tabla' => $tabla,
            'idRegistro' => $idRegistro,
            'detalle' => $detalle ?: null,
        ]);
    }
}
