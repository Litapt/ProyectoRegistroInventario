<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
    Las 23 tablas de la FASE 1 y sus catálogos iniciales.
    Es lo mismo que database/sql/2026_11_20_base_fase1.sql, pero en Laravel.

    - En refaccionaria2 (donde ya corriste el script SQL) NO hace nada:
      ve que la tabla "roles" ya existe y se salta todo.
    - En una base vacía (el servidor VPS o las pruebas automáticas)
      crea todo con: php artisan migrate
*/
return new class extends Migration
{
    // En el orden en que se crean (las que dependen de otras van después)
    private const TABLAS = [
        'roles', 'permisos', 'rolPermisos', 'trabajadores',
        'configuraciones', 'folios',
        'categorias', 'marcas', 'ubicaciones', 'productos', 'productosCompatibilidades', 'motivos',
        'movimientosInventario', 'ajustesInventario',
        'clientes', 'metodosPago', 'ventas', 'detalleVentas', 'pedidos', 'detallePedidos', 'pagosVenta',
        'solicitudes', 'bitacora',
    ];

    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            return; // ya existen (script SQL)
        }

        $this->crearTablas();
        $this->insertarCatalogos();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (array_reverse(self::TABLAS) as $tabla) {
            Schema::dropIfExists($tabla);
        }
        Schema::enableForeignKeyConstraints();
    }

    /* Columna de llave foránea INT UNSIGNED: fk_idRol → roles.idRol */
    private function llave(Blueprint $t, string $columna, string $tabla, string $id, bool $nula = false): void
    {
        $campo = $t->unsignedInteger($columna);
        if ($nula) {
            $campo->nullable();
        }
        $t->foreign($columna)->references($id)->on($tabla);
    }

    private function crearTablas(): void
    {
        // ---------- 1) Acceso ----------
        Schema::create('roles', function (Blueprint $t) {
            $t->increments('idRol');
            $t->string('nombre', 40)->unique();
            $t->string('descripcion', 255)->nullable();
            $t->boolean('activo')->default(true);
        });

        Schema::create('permisos', function (Blueprint $t) {
            $t->increments('idPermiso');
            $t->string('nombre', 60)->unique();
            $t->string('descripcion', 255)->nullable();
        });

        Schema::create('rolPermisos', function (Blueprint $t) {
            $t->increments('idRolPermiso');
            $this->llave($t, 'fk_idRol', 'roles', 'idRol');
            $this->llave($t, 'fk_idPermiso', 'permisos', 'idPermiso');
            $t->unique(['fk_idRol', 'fk_idPermiso'], 'rolPermisos_unico');
        });

        Schema::create('trabajadores', function (Blueprint $t) {
            $t->increments('idTrabajador');
            $t->string('nombres', 80);
            $t->string('apellidoPaterno', 60);
            $t->string('apellidoMaterno', 60)->nullable();
            $t->string('RFC', 13)->unique();
            $t->string('telefono', 20)->nullable();
            $t->string('correoElectronico', 120)->unique();
            $t->string('hashContrasena', 255);
            $this->llave($t, 'fk_idRol', 'roles', 'idRol');
            $t->dateTime('fechaCreacion')->useCurrent();
            $t->boolean('activo')->default(true);
            $t->dateTime('fechaUltimoAcceso')->nullable();
            $t->string('rememberToken', 100)->nullable();
        });

        // ---------- 2) Configuración y folios ----------
        Schema::create('configuraciones', function (Blueprint $t) {
            $t->increments('idConfiguracion');
            $t->string('clave', 60)->unique();
            $t->string('valor', 500)->nullable();
            $t->string('descripcion', 255)->nullable();
            $t->dateTime('fechaActualizacion')->nullable();
            $this->llave($t, 'fk_idTrabajadorActualizacion', 'trabajadores', 'idTrabajador', true);
        });

        Schema::create('folios', function (Blueprint $t) {
            $t->increments('idFolio');
            $t->string('tipoDocumento', 30)->unique();
            $t->string('prefijo', 20);
            $t->string('formato', 40);
            $t->unsignedSmallInteger('anio')->nullable();
            $t->unsignedInteger('ultimoNumero')->default(0);
            $t->unsignedTinyInteger('longitudNumero')->default(4);
            $t->boolean('activo')->default(true);
        });

        // ---------- 3) Catálogos y productos ----------
        Schema::create('categorias', function (Blueprint $t) {
            $t->increments('idCategoria');
            $t->string('nombre', 80)->unique();
            $t->string('descripcion', 255)->nullable();
            $t->boolean('activo')->default(true);
        });

        Schema::create('marcas', function (Blueprint $t) {
            $t->increments('idMarca');
            $t->string('nombre', 80)->unique();
            $t->string('descripcion', 255)->nullable();
            $t->boolean('activo')->default(true);
        });

        Schema::create('ubicaciones', function (Blueprint $t) {
            $t->increments('idUbicacion');
            $t->string('pasillo', 20)->nullable();
            $t->string('rack', 20)->nullable();
            $t->string('anaquel', 20)->nullable();
            $t->string('nivel', 20)->nullable();
            $t->string('posicion', 20)->nullable();
            $t->string('codigoUbicacion', 40)->unique();
            $t->string('descripcion', 255)->nullable();
            $t->boolean('activo')->default(true);
        });

        Schema::create('productos', function (Blueprint $t) {
            $t->increments('idProducto');
            $t->string('codigoInterno', 30)->unique();
            $t->string('codigoBarras', 50)->nullable()->unique();
            $t->string('nombre', 150);
            $t->text('descripcion')->nullable();
            $this->llave($t, 'fk_idCategoria', 'categorias', 'idCategoria');
            $this->llave($t, 'fk_idMarca', 'marcas', 'idMarca');
            $this->llave($t, 'fk_idUbicacion', 'ubicaciones', 'idUbicacion');
            $t->string('numeroParteFabricante', 60)->nullable();
            $t->boolean('requiereNumeroSerie')->default(false);
            $t->decimal('precioCompraActual', 10, 2);
            $t->decimal('precioVentaActual', 10, 2);
            $t->decimal('tasaIVA', 5, 2)->nullable();
            $t->unsignedInteger('existencia')->default(0);
            $t->unsignedInteger('stockMinimo')->nullable();
            $t->unsignedInteger('stockMaximo')->nullable();
            $t->string('imagen', 255)->nullable();
            $t->boolean('activo')->default(true);
            $t->dateTime('fechaCreacion')->useCurrent();
            $t->dateTime('fechaActualizacion')->nullable();
        });

        Schema::create('productosCompatibilidades', function (Blueprint $t) {
            $t->increments('idCompatibilidad');
            $this->llave($t, 'fk_idProducto', 'productos', 'idProducto');
            $t->string('marcaVehiculo', 60);
            $t->string('modeloVehiculo', 60);
            $t->unsignedSmallInteger('anioDesde');
            $t->unsignedSmallInteger('anioHasta')->nullable();
            $t->string('motor', 60)->nullable();
            $t->string('notas', 255)->nullable();
        });

        Schema::create('motivos', function (Blueprint $t) {
            $t->increments('idMotivo');
            $t->string('nombre', 80);
            $t->string('categoriaMotivo', 30);
            $t->string('descripcion', 255)->nullable();
            $t->boolean('activo')->default(true);
            $t->unique(['categoriaMotivo', 'nombre'], 'motivos_unico');
        });

        // ---------- 4) Movimientos y ajustes ----------
        Schema::create('movimientosInventario', function (Blueprint $t) {
            $t->increments('idMovimientoInventario');
            $this->llave($t, 'fk_idProducto', 'productos', 'idProducto');
            $t->string('tipoMovimiento', 20);
            $t->unsignedInteger('cantidad');
            $t->unsignedInteger('existenciaAnterior');
            $t->unsignedInteger('existenciaNueva');
            $t->decimal('costoUnitario', 10, 2)->nullable();
            $this->llave($t, 'fk_idTrabajador', 'trabajadores', 'idTrabajador');
            $t->dateTime('fechaMovimiento')->useCurrent();
            $t->string('referenciaTipo', 20)->nullable();
            $t->unsignedInteger('referenciaId')->nullable();
            $this->llave($t, 'fk_idMotivo', 'motivos', 'idMotivo', true);
            $t->string('observaciones', 500)->nullable();
            $t->index(['referenciaTipo', 'referenciaId'], 'movimientos_referencia');
        });

        Schema::create('ajustesInventario', function (Blueprint $t) {
            $t->increments('idAjuste');
            $t->string('folio', 30)->unique();
            $t->string('tipoAjuste', 10);
            $this->llave($t, 'fk_idProducto', 'productos', 'idProducto');
            $t->unsignedInteger('cantidad');
            $t->unsignedInteger('existenciaSistema');
            $t->unsignedInteger('existenciaContada')->nullable();
            $t->integer('diferencia')->nullable();
            $t->decimal('costoUnitario', 10, 2)->nullable();
            $this->llave($t, 'fk_idTrabajador', 'trabajadores', 'idTrabajador');
            $t->dateTime('fecha')->useCurrent();
            $t->string('estado', 12)->default('PENDIENTE');
            $this->llave($t, 'fk_idMotivo', 'motivos', 'idMotivo', true);
            $t->string('referencia', 60)->nullable();
            $t->string('observaciones', 500)->nullable();
            $this->llave($t, 'fk_idTrabajadorAutoriza', 'trabajadores', 'idTrabajador', true);
            $t->dateTime('fechaAutorizacion')->nullable();
        });

        // ---------- 5) Clientes, ventas, pedidos y pagos ----------
        Schema::create('clientes', function (Blueprint $t) {
            $t->increments('idCliente');
            $t->string('nombres', 80)->nullable();
            $t->string('apellidoPaterno', 60)->nullable();
            $t->string('apellidoMaterno', 60)->nullable();
            $t->string('nombreComercial', 120)->nullable();
            $t->string('razonSocial', 150)->nullable();
            $t->string('RFC', 13)->nullable();
            $t->string('telefono', 20);
            $t->string('correo', 120)->nullable();
            $t->string('direccion', 255)->nullable();
            $t->string('tipoCliente', 20)->default('NORMAL');
            $t->boolean('activo')->default(true);
            $t->dateTime('fechaRegistro')->useCurrent();
            $t->string('notas', 500)->nullable();
        });

        Schema::create('metodosPago', function (Blueprint $t) {
            $t->increments('idMetodoPago');
            $t->string('nombre', 40)->unique();
            $t->boolean('activo')->default(true);
        });

        Schema::create('ventas', function (Blueprint $t) {
            $t->increments('idVenta');
            $t->string('folio', 30)->unique();
            $this->llave($t, 'fk_idCliente', 'clientes', 'idCliente', true);
            $this->llave($t, 'fk_idTrabajador', 'trabajadores', 'idTrabajador');
            $t->dateTime('fechaVenta')->useCurrent();
            $t->string('estadoVenta', 12)->default('COMPLETADA');
            $t->string('condicionPago', 10)->default('CONTADO');
            $t->decimal('subtotal', 12, 2);
            $t->decimal('descuentoTotal', 12, 2)->default(0);
            $t->decimal('impuestosTotal', 12, 2);
            $t->decimal('total', 12, 2);
            $t->string('observaciones', 500)->nullable();
            $t->dateTime('fechaCancelacion')->nullable();
            $this->llave($t, 'fk_idTrabajadorCancelacion', 'trabajadores', 'idTrabajador', true);
            $this->llave($t, 'fk_idMotivoCancelacion', 'motivos', 'idMotivo', true);
            $t->string('motivoCancelacion', 500)->nullable();
        });

        Schema::create('detalleVentas', function (Blueprint $t) {
            $t->increments('idDetalleVenta');
            $this->llave($t, 'fk_idVenta', 'ventas', 'idVenta');
            $this->llave($t, 'fk_idProducto', 'productos', 'idProducto');
            $t->unsignedInteger('cantidad');
            $t->decimal('precioUnitarioVenta', 10, 2);
            $t->decimal('descuento', 10, 2)->default(0);
            $t->decimal('tasaIVA', 5, 2);
            $t->decimal('subtotal', 12, 2);
            $t->decimal('totalLinea', 12, 2);
        });

        Schema::create('pedidos', function (Blueprint $t) {
            $t->increments('idPedido');
            $t->string('folio', 30)->unique();
            $t->string('tipoPedido', 12)->default('PEDIDO');
            $this->llave($t, 'fk_idCliente', 'clientes', 'idCliente', true);
            $this->llave($t, 'fk_idTrabajador', 'trabajadores', 'idTrabajador');
            $t->dateTime('fechaPedido')->useCurrent();
            $t->date('fechaEntrega')->nullable();
            $t->date('vigencia')->nullable();
            $t->string('estadoPedido', 12)->default('PENDIENTE');
            $t->decimal('subtotal', 12, 2);
            $t->decimal('descuentoTotal', 12, 2)->default(0);
            $t->decimal('impuestosTotal', 12, 2);
            $t->decimal('total', 12, 2);
            $t->string('observaciones', 500)->nullable();
            $this->llave($t, 'fk_idVenta', 'ventas', 'idVenta', true);
        });

        Schema::create('detallePedidos', function (Blueprint $t) {
            $t->increments('idDetallePedido');
            $this->llave($t, 'fk_idPedido', 'pedidos', 'idPedido');
            $this->llave($t, 'fk_idProducto', 'productos', 'idProducto');
            $t->unsignedInteger('cantidad');
            $t->decimal('precioUnitario', 10, 2);
            $t->decimal('descuento', 10, 2)->default(0);
            $t->decimal('tasaIVA', 5, 2);
            $t->decimal('subtotal', 12, 2);
            $t->decimal('totalLinea', 12, 2);
        });

        Schema::create('pagosVenta', function (Blueprint $t) {
            $t->increments('idPagoVenta');
            $this->llave($t, 'fk_idVenta', 'ventas', 'idVenta', true);
            $this->llave($t, 'fk_idPedido', 'pedidos', 'idPedido', true);
            $this->llave($t, 'fk_idMetodoPago', 'metodosPago', 'idMetodoPago');
            $t->decimal('monto', 12, 2);
            $t->char('moneda', 3)->default('MXN');
            $t->decimal('tipoCambio', 10, 4)->default(1);
            $t->dateTime('fechaPago')->useCurrent();
            $this->llave($t, 'fk_idTrabajador', 'trabajadores', 'idTrabajador');
            $t->string('referencia', 60)->nullable();
            $t->string('observaciones', 255)->nullable();
        });

        // ---------- 6) Solicitudes y bitácora ----------
        Schema::create('solicitudes', function (Blueprint $t) {
            $t->increments('idSolicitud');
            $t->string('folio', 30)->unique();
            $t->string('tipoSolicitud', 30);
            $this->llave($t, 'fk_idCliente', 'clientes', 'idCliente', true);
            $this->llave($t, 'fk_idVenta', 'ventas', 'idVenta', true);
            $this->llave($t, 'fk_idProducto', 'productos', 'idProducto', true);
            $t->unsignedInteger('cantidad')->nullable();
            $this->llave($t, 'fk_idTrabajadorSolicitante', 'trabajadores', 'idTrabajador', true);
            $this->llave($t, 'fk_idTrabajadorResponsable', 'trabajadores', 'idTrabajador', true);
            $t->dateTime('fechaSolicitud')->useCurrent();
            $t->string('estado', 12)->default('PENDIENTE');
            $t->string('prioridad', 6)->nullable();
            $this->llave($t, 'fk_idMotivo', 'motivos', 'idMotivo', true);
            $t->string('motivo', 500)->nullable();
            $t->string('comentario', 500)->nullable();
            $t->json('datosPropuestos')->nullable();
            $t->dateTime('fechaResolucion')->nullable();
        });

        Schema::create('bitacora', function (Blueprint $t) {
            $t->increments('idBitacora');
            $this->llave($t, 'fk_idTrabajador', 'trabajadores', 'idTrabajador');
            $this->llave($t, 'fk_idTrabajadorAutoriza', 'trabajadores', 'idTrabajador', true);
            $t->string('accion', 30);
            $t->string('tabla', 40);
            $t->unsignedInteger('idRegistro')->nullable();
            $t->json('detalle')->nullable();
            $t->dateTime('fecha')->useCurrent();
            $t->index(['tabla', 'idRegistro'], 'bitacora_registro');
        });
    }

    private function insertarCatalogos(): void
    {
        DB::table('roles')->insert([
            ['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Acceso total'],
            ['nombre' => 'GERENTE', 'descripcion' => 'Autoriza solicitudes, todo menos Configuración'],
            ['nombre' => 'CAJERO', 'descripcion' => 'Cobra ventas'],
            ['nombre' => 'VENDEDOR', 'descripcion' => 'Pedidos y cotizaciones'],
            ['nombre' => 'ALMACENISTA', 'descripcion' => 'Inventario e incidencias'],
        ]);

        $permisos = [
            'ventas.registrar' => 'Registrar ventas',
            'ventas.consultar' => 'Consultar ventas',
            'ventas.cancelar' => 'Cancelar ventas',
            'ventas.solicitarEdicion' => 'Pedir que se edite una venta',
            'pagos.registrar' => 'Registrar pagos y abonos',
            'pedidos.gestionar' => 'Registrar y editar pedidos y cotizaciones',
            'clientes.gestionar' => 'Registrar y editar clientes',
            'clientes.desactivar' => 'Desactivar clientes',
            'productos.consultar' => 'Consultar productos',
            'productos.gestionar' => 'Registrar y editar productos',
            'productos.desactivar' => 'Desactivar productos',
            'inventario.ajustar' => 'Registrar ajustes de stock',
            'incidencias.reportar' => 'Reportar incidencias',
            'solicitudes.autorizar' => 'Aceptar o rechazar solicitudes',
            'usuarios.gestionar' => 'Usuarios y roles',
            'configuracion.editar' => 'Configuración de la tienda',
            'reportes.ver' => 'Inicio y reportes',
        ];
        foreach ($permisos as $nombre => $descripcion) {
            DB::table('permisos')->insert(['nombre' => $nombre, 'descripcion' => $descripcion]);
        }

        // El administrador tiene todos los permisos; los demás roles, cuando el equipo los defina
        $idAdministrador = DB::table('roles')->where('nombre', 'ADMINISTRADOR')->value('idRol');
        foreach (DB::table('permisos')->pluck('idPermiso') as $idPermiso) {
            DB::table('rolPermisos')->insert(['fk_idRol' => $idAdministrador, 'fk_idPermiso' => $idPermiso]);
        }

        DB::table('ubicaciones')->insert(['codigoUbicacion' => 'SIN-ASIGNAR', 'descripcion' => 'Sin asignar']);

        foreach (['Efectivo', 'Tarjeta', 'Transferencia'] as $metodo) {
            DB::table('metodosPago')->insert(['nombre' => $metodo]);
        }

        $motivos = [
            'AJUSTE' => ['Llegada de mercancía', 'Conteo físico', 'Merma', 'Uso interno'],
            'CANCELACION' => ['Error de captura', 'El cliente ya no la quiere'],
            'DESACTIVACION' => ['Producto descontinuado', 'Registro duplicado'],
            'INCIDENCIA' => ['Producto dañado', 'Producto defectuoso', 'Envase con fuga', 'Producto incorrecto',
                'Accesorio faltante', 'Etiqueta ilegible', 'Diferencia de inventario'],
            'DEVOLUCION' => ['No es compatible con el vehículo', 'Llegó dañado'],
        ];
        foreach ($motivos as $categoria => $nombres) {
            foreach ($nombres as $nombre) {
                DB::table('motivos')->insert(['categoriaMotivo' => $categoria, 'nombre' => $nombre]);
            }
        }

        DB::table('folios')->insert([
            ['tipoDocumento' => 'VENTA', 'prefijo' => 'V', 'formato' => 'V-{AAAA}-{0000}'],
            ['tipoDocumento' => 'PEDIDO', 'prefijo' => 'P', 'formato' => 'P-{AAAA}-{0000}'],
            ['tipoDocumento' => 'COTIZACION', 'prefijo' => 'COT', 'formato' => 'COT-{AAAA}-{0000}'],
            ['tipoDocumento' => 'AJUSTE', 'prefijo' => 'AJ', 'formato' => 'AJ-{AAAA}-{0000}'],
            ['tipoDocumento' => 'SOLICITUD', 'prefijo' => 'SOL', 'formato' => 'SOL-{AAAA}-{0000}'],
            ['tipoDocumento' => 'INCIDENCIA', 'prefijo' => 'INC', 'formato' => 'INC-{0000}'],
        ]);

        $configuraciones = [
            ['tienda.nombreComercial', null, 'Nombre de la tienda'],
            ['tienda.razonSocial', null, 'Razón social'],
            ['tienda.rfc', null, 'RFC de la tienda'],
            ['tienda.regimenFiscal', null, 'Régimen fiscal'],
            ['tienda.telefono', null, 'Teléfono'],
            ['tienda.correo', null, 'Correo'],
            ['tienda.direccion', null, 'Dirección'],
            ['tienda.logo', null, 'Ruta del logo (PNG/JPG hasta 2 MB)'],
            ['tienda.textoComprobante', null, 'Texto al pie del comprobante'],
            ['iva', '16', 'IVA general en %'],
            ['stockMinimoDefecto', '10', 'Para productos sin stock mínimo propio'],
            ['monedaBase', 'MXN', 'Moneda de precios y totales'],
            ['alertasStockBajo', '1', '1 = activadas'],
            ['notificacionesCorreo', '0', '1 = activadas (requiere servidor de correo)'],
            ['correoNotificaciones', null, 'A dónde llegan los avisos'],
            ['minutosCierreSesion', '15', 'Cierre de sesión automático'],
        ];
        foreach ($configuraciones as [$clave, $valor, $descripcion]) {
            DB::table('configuraciones')->insert(['clave' => $clave, 'valor' => $valor, 'descripcion' => $descripcion]);
        }
    }
};
