<?php

/*
    Pruebas automáticas del GERENTE: php artisan test
    Usa las mismas pantallas que el administrador en /gerente/..., menos Usuarios y Configuración.
*/

use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);
    $this->actingAs($this->gerente);
});

test('el gerente tiene todos los permisos menos Usuarios y Configuración', function () {
    expect($this->gerente->puede('ventas.registrar'))->toBeTrue()
        ->and($this->gerente->puede('solicitudes.autorizar'))->toBeTrue()
        ->and($this->gerente->puede('usuarios.gestionar'))->toBeFalse()
        ->and($this->gerente->puede('configuracion.editar'))->toBeFalse();
});

test('su menú tiene Inicio, Ventas, Clientes, Productos y Alertas, con enlaces a /gerente', function () {
    $this->get(route('gerente.inicio'))
        ->assertOk()
        ->assertSee('Productos por reponer')
        ->assertSee(route('gerente.ventas.index'), false)
        ->assertSee(route('gerente.clientes.index'), false)
        ->assertSee(route('gerente.productos.index'), false)
        ->assertSee(route('gerente.alertas.index'), false)
        ->assertDontSee(route('gerente.usuarios.index'), false)
        ->assertDontSee(route('gerente.configuracion.index'), false)
        ->assertDontSee(url('/administrador') . '/', false);   // (los CSS sí viven en css/administrador)
});

test('todas sus pantallas abren y sus enlaces se quedan en /gerente', function (string $ruta) {
    $html = $this->get(route($ruta))->assertOk()->getContent();
    expect($html)->not->toContain(url('/administrador') . '/')->and($html)->not->toContain(url('/administrador') . '"');
})->with([
    'gerente.ventas.index', 'gerente.ventas.consultar', 'gerente.ventas.solicitudes',
    'gerente.clientes.index', 'gerente.productos.index',
    'gerente.alertas.index', 'gerente.alertas.incidencias',
]);

test('no entra a Usuarios ni a Configuración, ni a la carpeta del administrador', function () {
    $this->get(route('gerente.usuarios.index'))->assertRedirect(route('gerente.inicio'));
    $this->get(route('gerente.configuracion.index'))->assertRedirect(route('gerente.inicio'));
    $this->get(route('administrador.ventas.index'))->assertRedirect(route('gerente.inicio'));
});

test('el gerente registra una venta y al guardar regresa a su carpeta', function () {
    $producto = Producto::factory()->create(['precioVentaActual' => 100]);
    $producto->existencia = 5;
    $producto->save();

    $this->post(route('gerente.ventas.store'), [
        'productos' => [['id' => $producto->idProducto, 'cantidad' => 2]],
        'metodoPago' => MetodoPago::value('idMetodoPago'),
    ])->assertRedirect(route('gerente.ventas.index'));

    expect(Venta::first()->fk_idTrabajador)->toBe($this->gerente->idTrabajador)
        ->and($producto->refresh()->existencia)->toBe(3);

    $this->get(route('gerente.ventas.index'))
        ->assertSee(route('gerente.ventas.ticket', Venta::first()), false);
});

test('el gerente registra un cliente y se autoriza a sí mismo para editarlo', function () {
    $this->post(route('gerente.clientes.store'), ['nombre' => 'Pedro Ruiz', 'telefono' => '6131112222'])
        ->assertRedirect(route('gerente.clientes.index'));
    $cliente = Cliente::first();

    $this->put(route('gerente.clientes.update', $cliente), [
        'nombre' => 'Pedro Ruiz López', 'telefono' => '6131112222',
        'correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password',
    ])->assertRedirect(route('gerente.clientes.index'));

    expect($cliente->refresh()->nombre())->toBe('Pedro Ruiz López');
});

test('el administrador sigue con sus enlaces en /administrador', function () {
    $this->actingAs(Trabajador::factory()->administrador()->create())
        ->get(route('administrador.ventas.consultar'))
        ->assertOk()->assertDontSee(url('/gerente'), false);
});
