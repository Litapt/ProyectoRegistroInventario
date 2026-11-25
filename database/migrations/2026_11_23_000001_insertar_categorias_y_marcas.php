<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
    Categorías y marcas iniciales para poder registrar productos.
    (Las del Figma y las más comunes en una refaccionaria.)
    Más adelante el administrador las podrá cambiar desde Configuración.

    insertOrIgnore: si alguna ya existe, la salta sin dar error.
    No borra ni cambia nada de lo que ya tengas.
*/
return new class extends Migration
{
    private const CATEGORIAS = [
        'Frenos', 'Motor', 'Encendido', 'Suspensión', 'Eléctrico', 'Filtros',
        'Lubricantes', 'Transmisión', 'Enfriamiento', 'Dirección', 'Accesorios',
    ];

    private const MARCAS = [
        'Bosch', 'Mann', 'NGK', 'Denso', 'Monroe', 'LTH', 'Gates', 'SKF',
        'Champion', 'Fram', 'Castrol', 'Mobil', 'ACDelco', 'Motorcraft', 'Genérica',
    ];

    public function up(): void
    {
        DB::table('categorias')->insertOrIgnore(
            array_map(fn ($nombre) => ['nombre' => $nombre], self::CATEGORIAS)
        );

        DB::table('marcas')->insertOrIgnore(
            array_map(fn ($nombre) => ['nombre' => $nombre], self::MARCAS)
        );
    }

    public function down(): void
    {
        // Sólo quita las que no usa ningún producto
        DB::table('categorias')->whereIn('nombre', self::CATEGORIAS)
            ->whereNotIn('idCategoria', DB::table('productos')->select('fk_idCategoria'))
            ->delete();

        DB::table('marcas')->whereIn('nombre', self::MARCAS)
            ->whereNotIn('idMarca', DB::table('productos')->select('fk_idMarca'))
            ->delete();
    }
};
