<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
    Lugar del almacén donde está un producto, por ejemplo "A-01-03".
    Tabla: ubicaciones (llave idUbicacion). "SIN-ASIGNAR" es la de los productos sin lugar.
*/
class Ubicacion extends Model
{
    public const SIN_ASIGNAR = 'SIN-ASIGNAR';

    protected $table = 'ubicaciones';
    protected $primaryKey = 'idUbicacion';
    public $timestamps = false;

    protected $fillable = ['codigoUbicacion', 'pasillo', 'rack', 'anaquel', 'nivel', 'posicion', 'descripcion'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /*
        Busca la ubicación por su código; si no existe la crea.
        Texto vacío → "SIN-ASIGNAR". Ej.: Ubicacion::desdeTexto(' a-01-03 ') → A-01-03
    */
    public static function desdeTexto(?string $texto): self
    {
        $codigo = mb_strtoupper(trim((string) $texto));

        if ($codigo === '') {
            $codigo = self::SIN_ASIGNAR;
        }

        return static::firstOrCreate(
            ['codigoUbicacion' => $codigo],
            ['descripcion' => $codigo === self::SIN_ASIGNAR ? 'Sin asignar' : null]
        );
    }

    /* Para mostrar en pantalla: '' si no tiene lugar asignado */
    public function texto(): string
    {
        return $this->codigoUbicacion === self::SIN_ASIGNAR ? '' : $this->codigoUbicacion;
    }
}
