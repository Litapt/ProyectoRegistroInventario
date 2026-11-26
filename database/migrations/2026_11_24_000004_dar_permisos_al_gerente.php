<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
    Permisos del GERENTE (tabla rolPermisos): todos menos "Usuarios y roles" y
    "Configuración de la tienda" (descripción del rol: "Autoriza solicitudes, todo menos Configuración").
    Sólo AGREGA renglones; no quita nada ni toca otros roles.

    Lo mismo en SQL (Workbench), si prefieres:
      INSERT IGNORE INTO rolPermisos (fk_idRol, fk_idPermiso)
      SELECT r.idRol, p.idPermiso FROM roles r CROSS JOIN permisos p
      WHERE r.nombre = 'GERENTE' AND p.nombre NOT IN ('usuarios.gestionar', 'configuracion.editar');
*/
return new class extends Migration
{
    private const SIN_PERMISO = ['usuarios.gestionar', 'configuracion.editar'];

    public function up(): void
    {
        $idGerente = DB::table('roles')->where('nombre', 'GERENTE')->value('idRol');
        if (!$idGerente) {
            return;
        }

        $renglones = DB::table('permisos')->whereNotIn('nombre', self::SIN_PERMISO)->pluck('idPermiso')
            ->map(fn ($idPermiso) => ['fk_idRol' => $idGerente, 'fk_idPermiso' => $idPermiso])
            ->all();

        DB::table('rolPermisos')->insertOrIgnore($renglones);
    }

    public function down(): void
    {
        $idGerente = DB::table('roles')->where('nombre', 'GERENTE')->value('idRol');
        DB::table('rolPermisos')->where('fk_idRol', $idGerente)
            ->whereIn('fk_idPermiso', DB::table('permisos')->whereNotIn('nombre', self::SIN_PERMISO)->select('idPermiso'))
            ->delete();
    }
};
