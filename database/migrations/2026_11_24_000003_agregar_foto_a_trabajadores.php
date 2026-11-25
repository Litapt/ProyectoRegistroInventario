<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
    Foto de perfil de cada trabajador ("Mi perfil").
    Agrega UNA columna a trabajadores; no toca ningún dato.
      foto: ruta de la imagen dentro de public/ (ej. imagenes/perfiles/foto-xxxx.jpg), o NULL

    Lo mismo en SQL (Workbench), si prefieres:
      ALTER TABLE trabajadores ADD COLUMN foto VARCHAR(255) NULL AFTER telefono;
*/
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('trabajadores', 'foto')) {
            Schema::table('trabajadores', function (Blueprint $t) {
                $t->string('foto', 255)->nullable()->after('telefono');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trabajadores', 'foto')) {
            Schema::table('trabajadores', function (Blueprint $t) {
                $t->dropColumn('foto');
            });
        }
    }
};
