<?php

use App\Http\Controllers\Administrador\AjusteStockController;
use App\Http\Controllers\Administrador\AlertaController;
use App\Http\Controllers\Administrador\ClienteController;
use App\Http\Controllers\Administrador\ConfiguracionController;
use App\Http\Controllers\Administrador\DevolucionVentaController;
use App\Http\Controllers\Administrador\InicioController as InicioAdministrador;
use App\Http\Controllers\Administrador\ProductoController;
use App\Http\Controllers\Administrador\SolicitudVentaController;
use App\Http\Controllers\Administrador\UsuarioController;
use App\Http\Controllers\Administrador\VentaController;
use App\Http\Controllers\Almacenista\InicioController as InicioAlmacenista;
use App\Http\Controllers\Cajero\ClienteController as ClienteCajero;
use App\Http\Controllers\Cajero\InicioController as InicioCajero;
use App\Http\Controllers\Cajero\PedidoController as PedidoCajero;
use App\Http\Controllers\Cajero\TurnoController as TurnoCajero;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Perfil\PerfilController;
use App\Http\Controllers\Vendedor\InicioController as InicioVendedor;
use App\Models\Trabajador;
use Illuminate\Support\Facades\Route;

/*
    Rutas: cada dirección de la aplicación y qué código la atiende.
    Para ver la lista completa: php artisan route:list

    Cada rol tiene su propia carpeta (/administrador, /gerente...),
    sus propios controladores (app/Http/Controllers/<Rol>) y sus propias vistas
    (resources/views/<rol>). Un rol no entra a la carpeta de otro.
*/

// Inicio de sesión: el mismo para todos los roles
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.entrar');
});

// Cerrar sesión: es POST (formulario con @csrf) para que otra página no pueda cerrarla a escondidas
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Mi perfil: igual para todos los roles (cada quien sólo ve y cambia lo suyo)
Route::middleware('auth')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil');
    Route::put('/perfil/datos', [PerfilController::class, 'datos'])->name('perfil.datos');
    Route::put('/perfil/contrasena', [PerfilController::class, 'contrasena'])->name('perfil.contrasena');
    Route::post('/perfil/foto', [PerfilController::class, 'foto'])->name('perfil.foto');
    Route::delete('/perfil/foto', [PerfilController::class, 'quitarFoto'])->name('perfil.foto.quitar');
});

// ---------- Administrador y Gerente: las MISMAS pantallas con menú lateral ----------
/*
    Se registran dos veces: /administrador/... (rutas "administrador.*") y /gerente/... ("gerente.*").
    Lo que cada rol puede hacer lo decide su permiso (tabla rolPermisos): por ejemplo, el gerente
    no tiene "usuarios.gestionar" ni "configuracion.editar", así que esas pantallas no le abren.
    Las vistas arman sus enlaces con App\Support\Ruta::a('ventas.index'), que usa la carpeta actual.
*/
$pantallasConMenuLateral = function () {
    Route::get('/', [InicioAdministrador::class, 'index'])->name('inicio');

    // Productos: cada acción revisa además el permiso del rol (tabla rolPermisos)
    Route::get('/productos', [ProductoController::class, 'index'])
        ->middleware('permiso:productos.consultar')->name('productos.index');
    Route::post('/productos', [ProductoController::class, 'store'])
        ->middleware('permiso:productos.gestionar')->name('productos.store');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])
        ->middleware('permiso:productos.gestionar')->name('productos.update');
    Route::patch('/productos/{producto}/desactivar', [ProductoController::class, 'desactivar'])
        ->middleware('permiso:productos.desactivar')->name('productos.desactivar');
    Route::patch('/productos/{producto}/reactivar', [ProductoController::class, 'reactivar'])
        ->middleware('permiso:productos.desactivar')->name('productos.reactivar');

    // Ajuste de stock (entrada / egreso) de un producto
    Route::get('/productos/{producto}/ajuste', [AjusteStockController::class, 'create'])
        ->middleware('permiso:inventario.ajustar')->name('productos.ajuste');
    Route::post('/productos/{producto}/ajuste', [AjusteStockController::class, 'store'])
        ->middleware('permiso:inventario.ajustar')->name('productos.ajuste.store');

    // Ventas: registrar, ticket y cotizaciones
    Route::middleware('permiso:ventas.registrar')->group(function () {
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
        Route::get('/ventas/buscar-clientes', [VentaController::class, 'buscarClientes'])->name('ventas.buscarClientes');
        Route::get('/ventas/buscar-productos', [VentaController::class, 'buscarProductos'])->name('ventas.buscarProductos');
        Route::post('/ventas/cotizacion/vista', [VentaController::class, 'vistaCotizacion'])->name('ventas.cotizacion.vista');
    });
    Route::get('/ventas/consultar', [VentaController::class, 'consultar'])
        ->middleware('permiso:ventas.consultar')->name('ventas.consultar');
    Route::post('/ventas/{venta}/abono', [VentaController::class, 'abonar'])
        ->middleware('permiso:pagos.registrar')->name('ventas.abono');
    Route::get('/ventas/cotizaciones', [VentaController::class, 'cotizaciones'])
        ->middleware('permiso:pedidos.gestionar')->name('ventas.cotizaciones');
    Route::patch('/ventas/{venta}/cancelar', [VentaController::class, 'cancelar'])
        ->middleware('permiso:ventas.cancelar')->name('ventas.cancelar');
    // Devoluciones (regresan dinero: piden autorización y el permiso de cancelar ventas)
    Route::post('/ventas/{venta}/devolucion', [DevolucionVentaController::class, 'store'])
        ->middleware('permiso:ventas.cancelar')->name('ventas.devolucion');
    Route::get('/ventas/devoluciones/{solicitud}/comprobante', [DevolucionVentaController::class, 'comprobante'])
        ->middleware('permiso:ventas.consultar')->name('ventas.devolucion.comprobante');

    // Editar una venta por solicitud (la acepta un gerente o administrador)
    Route::put('/ventas/{venta}/editar', [SolicitudVentaController::class, 'editar'])
        ->middleware('permiso:ventas.solicitarEdicion')->name('ventas.editar');
    Route::middleware('permiso:solicitudes.autorizar')->group(function () {
        Route::get('/ventas/solicitudes', [SolicitudVentaController::class, 'index'])->name('ventas.solicitudes');
        Route::patch('/ventas/solicitudes/{solicitud}/aprobar', [SolicitudVentaController::class, 'aprobar'])->name('ventas.solicitudes.aprobar');
        Route::patch('/ventas/solicitudes/{solicitud}/rechazar', [SolicitudVentaController::class, 'rechazar'])->name('ventas.solicitudes.rechazar');
    });
    Route::get('/ventas/{venta}/ticket', [VentaController::class, 'ticket'])
        ->middleware('permiso:ventas.consultar')->name('ventas.ticket');
    Route::post('/ventas/cotizaciones', [VentaController::class, 'guardarCotizacion'])
        ->middleware('permiso:pedidos.gestionar')->name('ventas.cotizacion.store');
    Route::get('/ventas/cotizaciones/{pedido}', [VentaController::class, 'cotizacion'])
        ->middleware('permiso:pedidos.gestionar')->name('ventas.cotizacion');

    // Alertas: stock bajo e incidencias
    Route::middleware('permiso:solicitudes.autorizar')->group(function () {
        Route::get('/alertas', [AlertaController::class, 'stock'])->name('alertas.index');
        Route::get('/alertas/incidencias', [AlertaController::class, 'incidencias'])->name('alertas.incidencias');
        Route::patch('/alertas/incidencias/{incidencia}/revision', [AlertaController::class, 'enRevision'])->name('alertas.incidencias.revision');
        Route::patch('/alertas/incidencias/{incidencia}/resolver', [AlertaController::class, 'resolver'])->name('alertas.incidencias.resolver');
    });
    Route::post('/alertas/incidencias', [AlertaController::class, 'registrar'])
        ->middleware('permiso:incidencias.reportar')->name('alertas.incidencias.store');

    // Configuración: cada sección se guarda aparte
    Route::middleware('permiso:configuracion.editar')->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::put('/configuracion/tienda', [ConfiguracionController::class, 'tienda'])->name('configuracion.tienda');
        Route::put('/configuracion/inventario', [ConfiguracionController::class, 'inventario'])->name('configuracion.inventario');
        Route::put('/configuracion/ventas', [ConfiguracionController::class, 'ventas'])->name('configuracion.ventas');
        Route::put('/configuracion/notificaciones', [ConfiguracionController::class, 'notificaciones'])->name('configuracion.notificaciones');
        Route::put('/configuracion/seguridad', [ConfiguracionController::class, 'seguridad'])->name('configuracion.seguridad');
    });

    // Clientes
    Route::get('/clientes', [ClienteController::class, 'index'])
        ->middleware('permiso:clientes.gestionar')->name('clientes.index');
    Route::post('/clientes', [ClienteController::class, 'store'])
        ->middleware('permiso:clientes.gestionar')->name('clientes.store');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permiso:clientes.gestionar')->name('clientes.update');
    Route::patch('/clientes/{cliente}/desactivar', [ClienteController::class, 'desactivar'])
        ->middleware('permiso:clientes.desactivar')->name('clientes.desactivar');
    Route::patch('/clientes/{cliente}/reactivar', [ClienteController::class, 'reactivar'])
        ->middleware('permiso:clientes.desactivar')->name('clientes.reactivar');

    // Usuarios (trabajadores que inician sesión)
    Route::get('/usuarios', [UsuarioController::class, 'index'])
        ->middleware('permiso:usuarios.gestionar')->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])
        ->middleware('permiso:usuarios.gestionar')->name('usuarios.store');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])
        ->middleware('permiso:usuarios.gestionar')->name('usuarios.update');
    Route::patch('/usuarios/{usuario}/desactivar', [UsuarioController::class, 'desactivar'])
        ->middleware('permiso:usuarios.gestionar')->name('usuarios.desactivar');
    Route::patch('/usuarios/{usuario}/reactivar', [UsuarioController::class, 'reactivar'])
        ->middleware('permiso:usuarios.gestionar')->name('usuarios.reactivar');
};

Route::middleware('rol:' . Trabajador::ROL_ADMINISTRADOR)
    ->prefix('administrador')->name('administrador.')
    ->group($pantallasConMenuLateral);

Route::middleware('rol:' . Trabajador::ROL_GERENTE)
    ->prefix('gerente')->name('gerente.')
    ->group($pantallasConMenuLateral);

// ---------- Cajero ----------
/*
    Pantallas con barra superior (sin menú lateral). Para vender o cobrar pedidos
    primero abre su turno de caja (middleware "turno"). Sólo hace pagos directos:
    descuentos, saldo pendiente y cancelaciones los autoriza un gerente o administrador.
*/
Route::middleware('rol:' . Trabajador::ROL_CAJERO)
    ->prefix('cajero')->name('cajero.')
    ->group(function () {
        // Nueva venta (su pantalla principal; ahí aparece "Abrir turno" si no lo ha abierto)
        Route::get('/', [InicioCajero::class, 'index'])->name('inicio');
        Route::post('/turno', [TurnoCajero::class, 'abrir'])->name('turno.abrir');

        Route::middleware('turno')->group(function () {
            Route::post('/ventas', [InicioCajero::class, 'store'])->name('ventas.store');

            // Pedidos que arman los vendedores: ver, editar antes de cobrar, cobrar o cancelar
            Route::get('/pedidos', [PedidoCajero::class, 'index'])->name('pedidos.index');
            Route::get('/pedidos/{pedido}/editar', [PedidoCajero::class, 'edit'])->name('pedidos.edit');
            Route::put('/pedidos/{pedido}', [PedidoCajero::class, 'update'])->name('pedidos.update');
            Route::get('/pedidos/{pedido}/cobrar', [PedidoCajero::class, 'cobrar'])->name('pedidos.cobrar');
            Route::post('/pedidos/{pedido}/cobrar', [PedidoCajero::class, 'pagar'])->name('pedidos.pagar');
            Route::patch('/pedidos/{pedido}/cancelar', [PedidoCajero::class, 'cancelar'])->name('pedidos.cancelar');

            Route::get('/buscar-clientes', [PedidoCajero::class, 'buscarClientes'])->name('buscarClientes');
            Route::get('/buscar-productos', [PedidoCajero::class, 'buscarProductos'])->name('buscarProductos');
            Route::post('/clientes', [ClienteCajero::class, 'store'])->name('clientes.store');
        });

        // Reimprimir (no necesitan turno abierto)
        Route::get('/ventas/{venta}/ticket', [PedidoCajero::class, 'ticket'])->name('ventas.ticket');
        Route::get('/pedidos/{pedido}/cancelacion', [PedidoCajero::class, 'comprobanteCancelacion'])->name('pedidos.cancelacion');
    });

// ---------- Vendedor ----------
Route::middleware('rol:' . Trabajador::ROL_VENDEDOR)
    ->prefix('vendedor')->name('vendedor.')
    ->group(function () {
        Route::get('/', [InicioVendedor::class, 'index'])->name('inicio');
    });

// ---------- Almacenista ----------
Route::middleware('rol:' . Trabajador::ROL_ALMACENISTA)
    ->prefix('almacenista')->name('almacenista.')
    ->group(function () {
        Route::get('/', [InicioAlmacenista::class, 'index'])->name('inicio');
    });
