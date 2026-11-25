<?php

/*
    Pruebas automáticas del inicio de sesión y de los roles: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus datos de MySQL.
*/

use App\Models\Configuracion;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function datosLogin(Trabajador $trabajador, string $contrasena = 'password'): array
{
    return ['correoElectronico' => $trabajador->correoElectronico, 'contrasena' => $contrasena];
}

// ---------- La base de datos ----------

test('la migración crea las 23 tablas y los catálogos', function () {
    foreach (['roles', 'permisos', 'rolPermisos', 'trabajadores', 'configuraciones', 'folios', 'categorias',
        'marcas', 'ubicaciones', 'productos', 'productosCompatibilidades', 'motivos', 'movimientosInventario',
        'ajustesInventario', 'clientes', 'metodosPago', 'ventas', 'detalleVentas', 'pedidos', 'detallePedidos',
        'pagosVenta', 'solicitudes', 'bitacora'] as $tabla) {
        expect(Schema::hasTable($tabla))->toBeTrue("Falta la tabla {$tabla}");
    }

    expect(DB::table('roles')->count())->toBe(5)
        ->and(DB::table('permisos')->count())->toBe(17)
        ->and(DB::table('rolPermisos')->count())->toBe(17)
        ->and(DB::table('motivos')->count())->toBe(26)   // 17 iniciales + 9 de devolución
        ->and(DB::table('folios')->count())->toBe(6)
        ->and(DB::table('configuraciones')->count())->toBe(16)
        ->and(Configuracion::valor('iva'))->toBe('16');
});

// ---------- Inicio de sesión ----------

test('la página principal muestra el inicio de sesión sin "Regístrate"', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Iniciar sesión')
        ->assertDontSee('Regístrate');
});

test('cada rol inicia sesión con el mismo formulario y llega a su pantalla', function (string $rol, string $ruta) {
    $trabajador = Trabajador::factory()->conRol($rol)->create(['nombres' => 'Laura']);

    $this->post('/login', datosLogin($trabajador))->assertRedirect(route($ruta));
    $this->assertAuthenticatedAs($trabajador);

    $this->get(route($ruta))
        ->assertOk()
        ->assertSee('Laura')   // su nombre en el menú de usuario
        ->assertSee('Cerrar sesión');
})->with([
    'administrador' => [Trabajador::ROL_ADMINISTRADOR, 'administrador.inicio'],
    'gerente' => [Trabajador::ROL_GERENTE, 'gerente.inicio'],
    'cajero' => [Trabajador::ROL_CAJERO, 'cajero.inicio'],
    'vendedor' => [Trabajador::ROL_VENDEDOR, 'vendedor.inicio'],
    'almacenista' => [Trabajador::ROL_ALMACENISTA, 'almacenista.inicio'],
]);

test('al entrar se guarda la fecha del último acceso', function () {
    $trabajador = Trabajador::factory()->administrador()->create();
    expect($trabajador->fechaUltimoAcceso)->toBeNull();

    $this->post('/login', datosLogin($trabajador));

    expect($trabajador->refresh()->fechaUltimoAcceso)->not->toBeNull();
});

test('con contraseña incorrecta no entra y ve la alerta', function () {
    $trabajador = Trabajador::factory()->administrador()->create();

    $this->post('/login', datosLogin($trabajador, 'incorrecta'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('alerta', ['danger', 'Usuario o contraseña incorrectos.']);

    $this->assertGuest();
});

test('con un correo que no existe no entra', function () {
    $this->post('/login', ['correoElectronico' => 'nadie@correo.com', 'contrasena' => 'password'])
        ->assertSessionHas('alerta', ['danger', 'Usuario o contraseña incorrectos.']);

    $this->assertGuest();
});

test('un trabajador desactivado no puede entrar', function () {
    $trabajador = Trabajador::factory()->administrador()->inactivo()->create();

    $this->post('/login', datosLogin($trabajador))
        ->assertSessionHas('alerta', ['warning', 'El usuario no está activo. Habla con el administrador.']);

    $this->assertGuest();
});

test('después de 5 intentos fallidos se bloquea aunque la contraseña sea correcta', function () {
    $trabajador = Trabajador::factory()->administrador()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', datosLogin($trabajador, 'incorrecta'));
    }

    $this->post('/login', datosLogin($trabajador));

    $this->assertGuest();
});

test('la contraseña se guarda cifrada', function () {
    $trabajador = Trabajador::factory()->create(['hashContrasena' => 'secreta123']);

    expect($trabajador->fresh()->hashContrasena)->not->toBe('secreta123');
});

// ---------- Acceso por rol ----------

test('sin sesión, cualquier pantalla manda al inicio de sesión', function () {
    $this->get(route('administrador.inicio'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('alerta', ['warning', 'Tu sesión expiró o no has iniciado sesión.']);
});

test('un vendedor no entra a la carpeta del administrador: regresa a la suya', function () {
    $vendedor = Trabajador::factory()->create();

    $this->actingAs($vendedor)
        ->get(route('administrador.inicio'))
        ->assertRedirect(route('vendedor.inicio'))
        ->assertSessionHas('alerta', ['warning', 'No tienes permiso para acceder a esa página.']);
});

test('si desactivan al trabajador con la sesión abierta, lo saca', function () {
    $trabajador = Trabajador::factory()->administrador()->create();
    $this->actingAs($trabajador);

    $trabajador->activo = false;
    $trabajador->save();

    $this->get(route('administrador.inicio'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('con la sesión abierta, el inicio de sesión manda a su pantalla', function () {
    $this->actingAs(Trabajador::factory()->conRol(Trabajador::ROL_ALMACENISTA)->create())
        ->get('/')
        ->assertRedirect(route('almacenista.inicio'));
});

test('cerrar sesión funciona', function () {
    $this->actingAs(Trabajador::factory()->administrador()->create())
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

// ---------- Permisos ----------

test('los permisos salen de la tabla rolPermisos', function () {
    $administrador = Trabajador::factory()->administrador()->create();
    $vendedor = Trabajador::factory()->create();

    expect($administrador->puede('ventas.cancelar'))->toBeTrue()
        ->and($administrador->puede('configuracion.editar'))->toBeTrue()
        ->and($vendedor->puede('ventas.cancelar'))->toBeFalse()
        ->and($administrador->puede('permiso.que.no.existe'))->toBeFalse();
});

test('el menú lateral sólo muestra las opciones de los permisos del rol', function () {
    $this->actingAs(Trabajador::factory()->administrador()->create())
        ->get(route('administrador.inicio'))
        ->assertSee('Configuración')
        ->assertSee('Usuarios');

    // El gerente todavía no tiene permisos asignados: sólo ve Inicio
    $this->actingAs(Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create())
        ->get(route('gerente.inicio'))
        ->assertDontSee('Configuración')
        ->assertDontSee('Usuarios');
});
