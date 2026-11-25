<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/*
    Algo que se puede hacer en el sistema, por ejemplo "ventas.cancelar".
    Tabla: permisos (llave idPermiso). Qué rol tiene cuál: tabla rolPermisos.
*/
class Permiso extends Model
{
    protected $table = 'permisos';
    protected $primaryKey = 'idPermiso';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rolPermisos', 'fk_idPermiso', 'fk_idRol', 'idPermiso', 'idRol');
    }
}
