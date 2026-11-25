<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
    Un rol: ADMINISTRADOR, GERENTE, CAJERO, VENDEDOR o ALMACENISTA.
    Tabla: roles (llave idRol).
*/
class Rol extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'idRol';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /* Lo que puede hacer este rol (tabla rolPermisos) */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rolPermisos', 'fk_idRol', 'fk_idPermiso', 'idRol', 'idPermiso');
    }

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class, 'fk_idRol', 'idRol');
    }

    /* Busca el id de un rol por su nombre: Rol::idDe('GERENTE') */
    public static function idDe(string $nombre): ?int
    {
        return static::where('nombre', $nombre)->value('idRol');
    }
}
