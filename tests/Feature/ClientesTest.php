<?php

/*
    Pruebas automáticas de Clientes del administrador: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus clientes reales.
*/

use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create(['correoElectronico' => 'admin@prueba.mx']);
});

function datosCliente(array $cambios = []): array
{
    return array_merge([
        'nombre' => '  María   López ',
        'telefono' => '613 123 4567',
        'correo' => 'Maria.Lopez@Correo.MX',
        'rfc' => 'lopm900412h21',
        'direccion' => 'Calle Hidalgo 125, Col. Centro',
        'notas' => 'Prefiere comprobantes por correo.',
    ], $cambios);
}

function autorizacion(string $correo = 'admin@prueba.mx', string $contrasena = 'password'): array
{
    return ['correo_autoriza' => $correo, 'contrasena_autoriza' => $contrasena];
}

// ---------- Consultar ----------

test('el administrador ve Clientes en el menú y la lista', function () {
    $cliente = Cliente::factory()->create(['nombres' => 'Taller Automotriz Rivera']);
    Cliente::factory()->desactivado()->create(['nombres' => 'Cliente viejo']);

    $this->actingAs($this->admin)->get(route('administrador.clientes.index'))
        ->assertOk()
        ->assertSee(route('administrador.clientes.index'), false)
        ->assertSee('Taller Automotriz Rivera')
        ->assertSee($cliente->codigo())
        ->assertDontSee('Cliente viejo');
});

test('buscar por nombre, teléfono, RFC o código', function () {
    $maria = Cliente::factory()->create(['nombres' => 'María López', 'telefono' => '613 123 4567', 'RFC' => 'LOPM900412H21']);
    Cliente::factory()->create(['nombres' => 'Jorge Hernández', 'telefono' => '613 999 0000']);

    $buscar = fn (string $texto) => $this->actingAs($this->admin)->get(route('administrador.clientes.index', ['buscar' => $texto]));

    $buscar('lópez')->assertSee('María López')->assertDontSee('Jorge Hernández');
    $buscar('6131234')->assertSee('María López')->assertDontSee('Jorge Hernández');   // sin espacios
    $buscar('LOPM900412')->assertSee('María López')->assertDontSee('Jorge Hernández');
    $buscar($maria->codigo())->assertSee('María López')->assertDontSee('Jorge Hernández');
});

// ---------- Registrar ----------

test('registrar un cliente limpia los datos y le da su código', function () {
    $this->actingAs($this->admin)->post(route('administrador.clientes.store'), datosCliente())
        ->assertRedirect(route('administrador.clientes.index'))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success' && str_contains($a[1], 'CLI-00001'));

    $cliente = Cliente::first();
    expect($cliente->nombre())->toBe('María López')
        ->and($cliente->correo)->toBe('maria.lopez@correo.mx')
        ->and($cliente->RFC)->toBe('LOPM900412H21')
        ->and($cliente->activo)->toBeTrue()
        ->and($cliente->fechaRegistro)->not->toBeNull()
        ->and(Bitacora::where('accion', 'CREAR')->where('tabla', 'clientes')->exists())->toBeTrue();
});

test('sólo nombre y teléfono son obligatorios', function () {
    $this->actingAs($this->admin)->post(route('administrador.clientes.store'), [
        'nombre' => 'Cliente de mostrador', 'telefono' => '6131234567',
    ])->assertSessionHasNoErrors();

    expect(Cliente::first()->correo)->toBeNull()->and(Cliente::first()->RFC)->toBeNull();
});

test('los datos inválidos se rechazan y la ventana se vuelve a abrir', function () {
    $this->actingAs($this->admin)->post(route('administrador.clientes.store'), datosCliente([
        'nombre' => '',
        'telefono' => '123',          // muy corto
        'correo' => 'no-es-correo',
        'rfc' => '123',
    ]))->assertSessionHasErrors(['nombre', 'telefono', 'correo', 'rfc'])
        ->assertSessionHas('modalCliente', 'registrar');

    expect(Cliente::count())->toBe(0);
});

test('no se puede repetir el RFC entre clientes', function () {
    Cliente::factory()->create(['RFC' => 'LOPM900412H21']);

    $this->actingAs($this->admin)->post(route('administrador.clientes.store'), datosCliente())
        ->assertSessionHasErrors('rfc');
});

// ---------- Editar (con autorización) ----------

test('editar con autorización guarda los cambios y la bitácora', function () {
    $cliente = Cliente::factory()->create(['nombres' => 'María López']);

    $this->actingAs($this->admin)->put(route('administrador.clientes.update', $cliente), datosCliente([
        'nombre' => 'María López Ruiz', 'rfc' => '',
    ]) + autorizacion())->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    expect($cliente->refresh()->nombre())->toBe('María López Ruiz');

    $bitacora = Bitacora::where('accion', 'EDITAR')->first();
    expect($bitacora->fk_idTrabajadorAutoriza)->toBe($this->admin->idTrabajador)
        ->and($bitacora->detalle['nombres']['despues'])->toBe('María López Ruiz');
});

test('editar sin autorización o con una mala no guarda y conserva lo escrito', function () {
    $cliente = Cliente::factory()->create(['nombres' => 'María López']);
    Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['correoElectronico' => 'cajero@prueba.mx']);

    foreach ([[], autorizacion('admin@prueba.mx', 'mala'), autorizacion('cajero@prueba.mx')] as $intento) {
        $this->actingAs($this->admin)->put(route('administrador.clientes.update', $cliente), datosCliente(['nombre' => 'Cambiado']) + $intento)
            ->assertSessionHasErrors('autorizacion')
            ->assertSessionHas('modalCliente', 'editar')
            ->assertSessionHasInput('nombre', 'Cambiado');
    }

    expect($cliente->refresh()->nombre())->toBe('María López');
});

test('al editar se puede dejar el mismo RFC', function () {
    $cliente = Cliente::factory()->create(['RFC' => 'LOPM900412H21']);

    $this->actingAs($this->admin)->put(route('administrador.clientes.update', $cliente), datosCliente() + autorizacion())
        ->assertSessionHasNoErrors();
});

// ---------- Eliminar (desactivar) y reactivar ----------

test('eliminar desactiva al cliente con autorización y se puede reactivar', function () {
    $cliente = Cliente::factory()->create(['nombres' => 'María López']);
    $gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);

    $this->actingAs($this->admin)->patch(route('administrador.clientes.desactivar', $cliente), autorizacion('gerente@prueba.mx'))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    expect($cliente->refresh()->activo)->toBeFalse()
        ->and(Cliente::count())->toBe(1)
        ->and(Bitacora::where('accion', 'DESACTIVAR')->value('fk_idTrabajadorAutoriza'))->toBe($gerente->idTrabajador);

    $this->actingAs($this->admin)->get(route('administrador.clientes.index', ['ver' => 'desactivados']))
        ->assertSee($cliente->codigo());

    $this->actingAs($this->admin)->patch(route('administrador.clientes.reactivar', $cliente), autorizacion());
    expect($cliente->refresh()->activo)->toBeTrue();
});

test('sin autorización no se desactiva', function () {
    $cliente = Cliente::factory()->create();

    $this->actingAs($this->admin)->patch(route('administrador.clientes.desactivar', $cliente), autorizacion('admin@prueba.mx', 'mala'))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'danger');

    expect($cliente->refresh()->activo)->toBeTrue();
});

test('un cliente desactivado no se puede editar', function () {
    $cliente = Cliente::factory()->desactivado()->create(['nombres' => 'Original']);

    $this->actingAs($this->admin)->put(route('administrador.clientes.update', $cliente), datosCliente() + autorizacion())
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'warning');

    expect($cliente->refresh()->nombre())->toBe('Original');
});

test('otros roles no entran a los clientes del administrador', function () {
    $this->actingAs(Trabajador::factory()->create())
        ->get(route('administrador.clientes.index'))
        ->assertRedirect(route('vendedor.inicio'));
});
