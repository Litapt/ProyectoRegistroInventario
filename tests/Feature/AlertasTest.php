<?php

/*
    Pruebas automáticas de Alertas (stock e incidencias): php artisan test
    Usan una base de datos temporal en memoria; NO tocan tu inventario real.
*/

use App\Models\Motivo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create();
    $this->actingAs($this->admin);
    $this->tipo = Motivo::de(Motivo::INCIDENCIA)->value('idMotivo');
});

function producto(array $datos): Producto
{
    $p = Producto::factory()->create(array_diff_key($datos, ['existencia' => 1]));
    $p->existencia = $datos['existencia'] ?? 10;
    $p->save();

    return $p;
}

function datosIncidencia($test, Producto $p, array $cambios = []): array
{
    return array_merge([
        'producto' => $p->idProducto, 'tipo' => $test->tipo, 'cantidad' => 2,
        'prioridad' => 'ALTA', 'descripcion' => 'Carcasa golpeada al recibirla',
    ], $cambios);
}

// ---------- Stock ----------

test('Stock lista agotados primero y luego los bajos; no los que tienen suficiente ni los desactivados', function () {
    producto(['nombre' => 'Balatas', 'existencia' => 4, 'stockMinimo' => 10]);
    producto(['nombre' => 'Bobina', 'existencia' => 0, 'stockMinimo' => 4]);
    producto(['nombre' => 'Amortiguador', 'existencia' => 30, 'stockMinimo' => 5]);
    producto(['nombre' => 'Bujía', 'existencia' => 6, 'stockMinimo' => null]);   // usa el mínimo de Configuración (10)
    $viejo = producto(['nombre' => 'Viejo', 'existencia' => 0]);
    $viejo->activo = false;
    $viejo->save();

    $this->get(route('administrador.alertas.index'))
        ->assertOk()
        ->assertSeeInOrder(['Bobina', 'Balatas'])
        ->assertSee('Bujía')
        ->assertDontSee('Amortiguador')->assertDontSee('Viejo')
        ->assertSee('2 Bajo')->assertSee('1 Agotado');
});

test('el menú muestra cuántas alertas hay', function () {
    producto(['existencia' => 0, 'stockMinimo' => 3]);
    producto(['existencia' => 1, 'stockMinimo' => 3]);

    $this->get(route('administrador.inicio'))->assertSee('sidebar-contador', false)->assertSeeInOrder(['Alertas', '2']);
});

// ---------- Incidencias ----------

test('registrar una incidencia le da folio INC y aparece en Incidencias', function () {
    $p = producto(['nombre' => 'Batería 12 V', 'existencia' => 5]);

    $this->post(route('administrador.alertas.incidencias.store'), datosIncidencia($this, $p))
        ->assertRedirect(route('administrador.alertas.incidencias'))
        ->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'INC-0001'));

    $inc = Solicitud::where('tipoSolicitud', Solicitud::INCIDENCIA)->first();
    expect($inc->estado)->toBe(Solicitud::PENDIENTE)->and($inc->prioridad)->toBe('ALTA')->and($inc->cantidad)->toBe(2);

    $this->get(route('administrador.alertas.incidencias'))->assertSee('INC-0001')->assertSee('Batería 12 V')->assertSee('Abierta');
});

test('registrar sin producto, tipo o descripción no se guarda y la ventana se vuelve a abrir', function () {
    $this->from(route('administrador.alertas.incidencias'))
        ->post(route('administrador.alertas.incidencias.store'), ['cantidad' => 0, 'prioridad' => 'URGENTE'])
        ->assertSessionHasErrorsIn('registrar', ['producto', 'tipo', 'cantidad', 'prioridad', 'descripcion']);

    expect(Solicitud::count())->toBe(0);
});

test('en revisión y luego resolver dando de baja piezas dañadas', function () {
    $p = producto(['existencia' => 5]);
    $this->post(route('administrador.alertas.incidencias.store'), datosIncidencia($this, $p));
    $inc = Solicitud::first();

    $this->patch(route('administrador.alertas.incidencias.revision', $inc))->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');
    expect($inc->refresh()->estado)->toBe(Solicitud::EN_REVISION);

    $this->patch(route('administrador.alertas.incidencias.resolver', $inc), [
        'comentario' => 'Se mandaron a garantía', 'piezasDeBaja' => 2,
    ])->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    expect($inc->refresh()->estado)->toBe(Solicitud::RESUELTA)
        ->and($inc->comentario)->toBe('Se mandaron a garantía')
        ->and($p->refresh()->existencia)->toBe(3)
        ->and(MovimientoInventario::where('tipoMovimiento', MovimientoInventario::INCIDENCIA)->value('cantidad'))->toBe(2);

    // Ya no está en abiertas; sí en resueltas (el primer get se "come" el aviso verde que lleva el folio)
    $this->get(route('administrador.alertas.index'));
    $this->get(route('administrador.alertas.incidencias'))->assertDontSee('INC-0001');
    $this->get(route('administrador.alertas.incidencias', ['ver' => 'resueltas']))->assertSee('INC-0001')->assertSee('Resuelta');

    // No se resuelve dos veces
    $this->patch(route('administrador.alertas.incidencias.resolver', $inc), ['comentario' => 'Otra vez', 'piezasDeBaja' => 1])
        ->assertSessionHasErrorsIn('resolver');
    expect($p->refresh()->existencia)->toBe(3);
});

test('resolver sin comentario o dando de baja más piezas de las que hay: no se guarda', function () {
    $p = producto(['existencia' => 1]);
    $this->post(route('administrador.alertas.incidencias.store'), datosIncidencia($this, $p));
    $inc = Solicitud::first();

    $this->patch(route('administrador.alertas.incidencias.resolver', $inc), ['comentario' => ''])
        ->assertSessionHasErrorsIn('resolver')->assertSessionHas('modalIncidencia', $inc->idSolicitud);
    $this->patch(route('administrador.alertas.incidencias.resolver', $inc), ['comentario' => 'Se tiraron', 'piezasDeBaja' => 5])
        ->assertSessionHasErrorsIn('resolver');

    expect($inc->refresh()->estado)->toBe(Solicitud::PENDIENTE)->and($p->refresh()->existencia)->toBe(1);
});

test('filtros por texto y prioridad', function () {
    $a = producto(['nombre' => 'Batería', 'existencia' => 5]);
    $b = producto(['nombre' => 'Filtro', 'existencia' => 5]);
    $this->post(route('administrador.alertas.incidencias.store'), datosIncidencia($this, $a, ['prioridad' => 'ALTA']));
    $this->post(route('administrador.alertas.incidencias.store'), datosIncidencia($this, $b, ['prioridad' => 'BAJA']));
    $this->get(route('administrador.alertas.index'));   // se "come" el aviso verde que lleva el folio

    $this->get(route('administrador.alertas.incidencias', ['buscar' => 'filtro']))->assertSee('INC-0002')->assertDontSee('INC-0001');
    $this->get(route('administrador.alertas.incidencias', ['prioridad' => 'ALTA']))->assertSee('INC-0001')->assertDontSee('INC-0002');
});

test('otros roles no entran a las alertas del administrador', function () {
    $this->actingAs(Trabajador::factory()->conRol(Trabajador::ROL_ALMACENISTA)->create())
        ->get(route('administrador.alertas.index'))
        ->assertRedirect(route('almacenista.inicio'));
});
