<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
    Turnos de caja del CAJERO (Figma: "Cajero - Inicio" y "Cajero - Corte de caja").

    - Al empezar a trabajar, el cajero ABRE su turno contando el fondo (billetes y monedas).
      Si el fondo no es el autorizado (Configuración → Ventas), lo autoriza un gerente o administrador.
    - Al terminar, hace el CORTE: cuenta el efectivo, anota tarjeta y transferencia,
      y se compara con lo que el sistema esperaba (fondo + lo cobrado en su turno).

    También:
      - agrega la configuración "fondoInicialCaja" ($1,000 si no se cambia),
      - da sus permisos al rol CAJERO (tabla rolPermisos).
    Sólo AGREGA cosas: no borra ni cambia datos que ya existan.

    Lo mismo en SQL (Workbench), si prefieres:

      CREATE TABLE turnosCaja (
        idTurno INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        fk_idTrabajador INT UNSIGNED NOT NULL,
        numeroCaja TINYINT UNSIGNED NOT NULL DEFAULT 1,
        estado VARCHAR(10) NOT NULL DEFAULT 'ABIERTO',
        fechaApertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fondoInicial DECIMAL(12,2) NOT NULL,
        conteoApertura JSON NULL,
        fk_idTrabajadorAutorizaApertura INT UNSIGNED NULL,
        observacionesApertura VARCHAR(500) NULL,
        fechaCierre DATETIME NULL,
        conteoCierre JSON NULL,
        efectivoContado DECIMAL(12,2) NULL,
        tarjetaContado DECIMAL(12,2) NULL,
        transferenciaContado DECIMAL(12,2) NULL,
        efectivoEsperado DECIMAL(12,2) NULL,
        tarjetaEsperado DECIMAL(12,2) NULL,
        transferenciaEsperado DECIMAL(12,2) NULL,
        diferencia DECIMAL(12,2) NULL,
        fk_idTrabajadorAutorizaCierre INT UNSIGNED NULL,
        observacionesCierre VARCHAR(500) NULL,
        INDEX turnos_trabajador_estado (fk_idTrabajador, estado),
        FOREIGN KEY (fk_idTrabajador) REFERENCES trabajadores (idTrabajador),
        FOREIGN KEY (fk_idTrabajadorAutorizaApertura) REFERENCES trabajadores (idTrabajador),
        FOREIGN KEY (fk_idTrabajadorAutorizaCierre) REFERENCES trabajadores (idTrabajador)
      );

      INSERT IGNORE INTO configuraciones (clave, valor, descripcion)
      VALUES ('fondoInicialCaja', '1000', 'Fondo con el que abre la caja (MXN)');

      INSERT IGNORE INTO rolPermisos (fk_idRol, fk_idPermiso)
      SELECT r.idRol, p.idPermiso FROM roles r CROSS JOIN permisos p
      WHERE r.nombre = 'CAJERO' AND p.nombre IN ('ventas.registrar', 'ventas.consultar', 'pagos.registrar',
            'pedidos.gestionar', 'clientes.gestionar', 'productos.consultar', 'ventas.solicitarEdicion');
*/
return new class extends Migration
{
    private const PERMISOS_CAJERO = [
        'ventas.registrar', 'ventas.consultar', 'pagos.registrar', 'pedidos.gestionar',
        'clientes.gestionar', 'productos.consultar', 'ventas.solicitarEdicion',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('turnosCaja')) {
            Schema::create('turnosCaja', function (Blueprint $t) {
                $t->increments('idTurno');
                $t->unsignedInteger('fk_idTrabajador');
                $t->unsignedTinyInteger('numeroCaja')->default(1);
                $t->string('estado', 10)->default('ABIERTO');
                $t->dateTime('fechaApertura')->useCurrent();
                $t->decimal('fondoInicial', 12, 2);
                $t->json('conteoApertura')->nullable();
                $t->unsignedInteger('fk_idTrabajadorAutorizaApertura')->nullable();
                $t->string('observacionesApertura', 500)->nullable();
                $t->dateTime('fechaCierre')->nullable();
                $t->json('conteoCierre')->nullable();
                $t->decimal('efectivoContado', 12, 2)->nullable();
                $t->decimal('tarjetaContado', 12, 2)->nullable();
                $t->decimal('transferenciaContado', 12, 2)->nullable();
                $t->decimal('efectivoEsperado', 12, 2)->nullable();
                $t->decimal('tarjetaEsperado', 12, 2)->nullable();
                $t->decimal('transferenciaEsperado', 12, 2)->nullable();
                $t->decimal('diferencia', 12, 2)->nullable();
                $t->unsignedInteger('fk_idTrabajadorAutorizaCierre')->nullable();
                $t->string('observacionesCierre', 500)->nullable();

                $t->index(['fk_idTrabajador', 'estado'], 'turnos_trabajador_estado');
                $t->foreign('fk_idTrabajador')->references('idTrabajador')->on('trabajadores');
                $t->foreign('fk_idTrabajadorAutorizaApertura')->references('idTrabajador')->on('trabajadores');
                $t->foreign('fk_idTrabajadorAutorizaCierre')->references('idTrabajador')->on('trabajadores');
            });
        }

        DB::table('configuraciones')->insertOrIgnore([
            'clave' => 'fondoInicialCaja', 'valor' => '1000', 'descripcion' => 'Fondo con el que abre la caja (MXN)',
        ]);

        $idCajero = DB::table('roles')->where('nombre', 'CAJERO')->value('idRol');
        if ($idCajero) {
            $renglones = DB::table('permisos')->whereIn('nombre', self::PERMISOS_CAJERO)->pluck('idPermiso')
                ->map(fn ($idPermiso) => ['fk_idRol' => $idCajero, 'fk_idPermiso' => $idPermiso])
                ->all();
            DB::table('rolPermisos')->insertOrIgnore($renglones);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('turnosCaja');
        DB::table('configuraciones')->where('clave', 'fondoInicialCaja')->delete();

        $idCajero = DB::table('roles')->where('nombre', 'CAJERO')->value('idRol');
        DB::table('rolPermisos')->where('fk_idRol', $idCajero)
            ->whereIn('fk_idPermiso', DB::table('permisos')->whereIn('nombre', self::PERMISOS_CAJERO)->select('idPermiso'))
            ->delete();
    }
};
