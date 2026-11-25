<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
    Más motivos de DEVOLUCIÓN (tabla motivos, categoriaMotivo = 'DEVOLUCION').

    ¿Quieres agregar otro? Escríbelo en la lista de abajo y corre:
        php artisan migrate:rollback --step=1
        php artisan migrate
    (o haz una migración nueva copiando ésta). insertOrIgnore salta los que ya existen,
    así que no se duplican ni se pierde nada.

    Máximo 80 letras por motivo. Si el nombre lleva "dañad", "defectuos", "falla",
    "garantía" o "incomplet", la ventana de devolución quita sola la palomita de
    "regresan al inventario" (porque esas piezas no se deben volver a vender).
*/
return new class extends Migration
{
    private const MOTIVOS = [
        'Pieza equivocada (no es la que pidió el cliente)',
        'Error del vendedor al surtir',
        'El cliente compró de más',
        'El cliente ya no la necesita',
        'Producto defectuoso',
        'Falla al instalar',
        'Llegó incompleto (le falta una parte)',
        'Garantía',
        'Otro (explicar en el comentario)',
    ];

    public function up(): void
    {
        DB::table('motivos')->insertOrIgnore(
            array_map(fn ($nombre) => ['categoriaMotivo' => 'DEVOLUCION', 'nombre' => $nombre], self::MOTIVOS)
        );
    }

    public function down(): void
    {
        // Sólo quita los que no se han usado en ninguna devolución ni movimiento
        DB::table('motivos')
            ->where('categoriaMotivo', 'DEVOLUCION')
            ->whereIn('nombre', self::MOTIVOS)
            ->whereNotIn('idMotivo', DB::table('solicitudes')->whereNotNull('fk_idMotivo')->select('fk_idMotivo'))
            ->whereNotIn('idMotivo', DB::table('movimientosInventario')->whereNotNull('fk_idMotivo')->select('fk_idMotivo'))
            ->delete();
    }
};
