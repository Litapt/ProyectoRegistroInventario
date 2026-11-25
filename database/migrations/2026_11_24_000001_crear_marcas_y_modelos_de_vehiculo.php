<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
    Vehículos compatibles con listas desplegables (pedido del equipo, 23/11/2026):
      marcasVehiculo  → Nissan, Chevrolet, Volkswagen...
      modelosVehiculo → Versa, Aveo, Jetta... (cada modelo es de una marca)
    Los años NO tienen tabla: se siguen escribiendo en productosCompatibilidades.

    productosCompatibilidades cambia: en vez de guardar la marca y el modelo como texto,
    guarda fk_idModeloVehiculo. Si ya había vehículos escritos a mano, se pasan solos
    a las tablas nuevas, así que no se pierde nada.
*/
return new class extends Migration
{
    // Marcas y modelos más comunes en México (después se podrán agregar más)
    private const VEHICULOS = [
        'Nissan' => ['Versa', 'Sentra', 'March', 'Tsuru', 'Tiida', 'Kicks', 'Altima', 'NP300', 'Frontier', 'X-Trail'],
        'Chevrolet' => ['Aveo', 'Spark', 'Beat', 'Onix', 'Cavalier', 'Sonic', 'Chevy', 'Silverado', 'S10', 'Tornado'],
        'Volkswagen' => ['Jetta', 'Vento', 'Gol', 'Polo', 'Virtus', 'Sedán', 'Golf', 'Tiguan', 'Saveiro', 'Amarok'],
        'Toyota' => ['Corolla', 'Yaris', 'Camry', 'Avanza', 'RAV4', 'Hilux', 'Tacoma', 'Hiace'],
        'Ford' => ['Fiesta', 'Focus', 'Figo', 'Ikon', 'Escape', 'Ranger', 'F-150', 'Lobo'],
        'Honda' => ['Civic', 'City', 'Fit', 'Accord', 'CR-V', 'HR-V'],
        'Kia' => ['Rio', 'Forte', 'Soul', 'Seltos', 'Sportage'],
        'Hyundai' => ['Grand i10', 'Accent', 'Elantra', 'Creta', 'Tucson'],
        'Mazda' => ['Mazda 2', 'Mazda 3', 'CX-3', 'CX-30', 'CX-5'],
        'Dodge' => ['Attitude', 'Neon', 'Journey', 'Durango'],
        'RAM' => ['RAM 700', 'RAM 1500', 'RAM 2500'],
        'Renault' => ['Kwid', 'Logan', 'Sandero', 'Stepway', 'Duster', 'Oroch'],
        'Mitsubishi' => ['Mirage', 'Outlander', 'L200'],
        'Suzuki' => ['Swift', 'Ertiga', 'Vitara'],
        'Jeep' => ['Wrangler', 'Cherokee', 'Compass'],
        'Seat' => ['Ibiza', 'León'],
        'Peugeot' => ['208', '301', '2008'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('marcasVehiculo')) {
            Schema::create('marcasVehiculo', function (Blueprint $t) {
                $t->increments('idMarcaVehiculo');
                $t->string('nombre', 60)->unique();
                $t->boolean('activo')->default(true);
            });
        }

        if (!Schema::hasTable('modelosVehiculo')) {
            Schema::create('modelosVehiculo', function (Blueprint $t) {
                $t->increments('idModeloVehiculo');
                $t->unsignedInteger('fk_idMarcaVehiculo');
                $t->string('nombre', 60);
                $t->boolean('activo')->default(true);
                $t->unique(['fk_idMarcaVehiculo', 'nombre'], 'modelosVehiculo_unico');
                $t->foreign('fk_idMarcaVehiculo')->references('idMarcaVehiculo')->on('marcasVehiculo');
            });
        }

        foreach (self::VEHICULOS as $marca => $modelos) {
            $this->modelo($marca, $modelos);
        }

        if (Schema::hasColumn('productosCompatibilidades', 'marcaVehiculo')) {
            $this->cambiarCompatibilidades();
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('productosCompatibilidades', 'marcaVehiculo')) {
            Schema::table('productosCompatibilidades', function (Blueprint $t) {
                $t->string('marcaVehiculo', 60)->default('');
                $t->string('modeloVehiculo', 60)->default('');
            });

            foreach (DB::table('productosCompatibilidades')->get() as $c) {
                $modelo = DB::table('modelosVehiculo')
                    ->join('marcasVehiculo', 'idMarcaVehiculo', '=', 'fk_idMarcaVehiculo')
                    ->where('idModeloVehiculo', $c->fk_idModeloVehiculo)
                    ->select('marcasVehiculo.nombre as marca', 'modelosVehiculo.nombre as modelo')->first();
                DB::table('productosCompatibilidades')->where('idCompatibilidad', $c->idCompatibilidad)
                    ->update(['marcaVehiculo' => $modelo->marca ?? '', 'modeloVehiculo' => $modelo->modelo ?? '']);
            }

            Schema::table('productosCompatibilidades', function (Blueprint $t) {
                $t->dropForeign(['fk_idModeloVehiculo']);
                $t->dropColumn('fk_idModeloVehiculo');
            });
        }

        Schema::dropIfExists('modelosVehiculo');
        Schema::dropIfExists('marcasVehiculo');
    }

    /* Crea (si no existen) la marca y sus modelos; regresa los ids de los modelos por nombre */
    private function modelo(string $marca, array $modelos): array
    {
        DB::table('marcasVehiculo')->insertOrIgnore(['nombre' => $marca]);
        $idMarca = DB::table('marcasVehiculo')->where('nombre', $marca)->value('idMarcaVehiculo');

        $ids = [];
        foreach ($modelos as $modelo) {
            DB::table('modelosVehiculo')->insertOrIgnore(['fk_idMarcaVehiculo' => $idMarca, 'nombre' => $modelo]);
            $ids[$modelo] = DB::table('modelosVehiculo')
                ->where('fk_idMarcaVehiculo', $idMarca)->where('nombre', $modelo)->value('idModeloVehiculo');
        }

        return $ids;
    }

    /* Pasa la marca y el modelo escritos a mano a las tablas nuevas y deja sólo el id */
    private function cambiarCompatibilidades(): void
    {
        Schema::table('productosCompatibilidades', function (Blueprint $t) {
            $t->unsignedInteger('fk_idModeloVehiculo')->nullable()->after('fk_idProducto');
        });

        foreach (DB::table('productosCompatibilidades')->get() as $c) {
            $marca = trim($c->marcaVehiculo) !== '' ? trim($c->marcaVehiculo) : 'Sin marca';
            $modelo = trim($c->modeloVehiculo) !== '' ? trim($c->modeloVehiculo) : 'Sin modelo';
            $id = $this->modelo($marca, [$modelo])[$modelo];

            DB::table('productosCompatibilidades')
                ->where('idCompatibilidad', $c->idCompatibilidad)
                ->update(['fk_idModeloVehiculo' => $id]);
        }

        Schema::table('productosCompatibilidades', function (Blueprint $t) {
            $t->dropColumn(['marcaVehiculo', 'modeloVehiculo']);
        });

        Schema::table('productosCompatibilidades', function (Blueprint $t) {
            $t->unsignedInteger('fk_idModeloVehiculo')->nullable(false)->change();
            $t->foreign('fk_idModeloVehiculo')->references('idModeloVehiculo')->on('modelosVehiculo');
        });
    }
};
